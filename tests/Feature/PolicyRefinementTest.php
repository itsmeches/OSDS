<?php

namespace Tests\Feature;

use App\Models\AccountStatusEvent;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\AccessGovernance;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Identity\AccountGovernance;
use App\Support\AccountRole;
use App\Support\AccountStatus;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PolicyRefinementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_account_creation_chain_is_permission_driven_and_scoped(): void
    {
        $region = Region::query()->create(['code' => 'CHAIN', 'name' => 'Chain Region']);
        $super = $this->userWithRole(AccountRole::SuperUser);
        $this->bootstrapRegion($super, $region);

        $this->actingAs($super);
        $admin = app(AccountGovernance::class)->create($super, AccountRole::Admin, $this->attributes('admin'), $region->id);
        $this->assertTrue($admin->hasRole(AccountRole::Admin->value));

        $this->expectException(AuthorizationException::class);
        app(AccountGovernance::class)->create($super, AccountRole::Osds, $this->attributes('forged-osds'), $region->id);
    }

    public function test_each_account_creator_can_create_only_the_configured_next_type(): void
    {
        $region = Region::query()->create(['code' => 'CHAIN2', 'name' => 'Second Chain Region']);
        $super = $this->userWithRole(AccountRole::SuperUser);
        $this->bootstrapRegion($super, $region);
        $governance = app(AccountGovernance::class);

        $this->actingAs($super);
        $admin = $governance->create($super, AccountRole::Admin, $this->attributes('chain-admin'), $region->id);
        $this->actingAs($admin);
        $osds = $governance->create($admin, AccountRole::Osds, $this->attributes('chain-osds'), $region->id);
        $this->actingAs($osds);
        $chedro = $governance->create($osds, AccountRole::Chedro, $this->attributes('chain-chedro'), $region->id);
        $institution = Institution::query()->create(['name' => 'Chain School', 'primary_region_id' => $region->id]);
        $this->actingAs($chedro);
        $school = $governance->create($chedro, AccountRole::School, $this->attributes('chain-school'), institutionId: $institution->id);

        $this->assertSame(AccountStatus::PendingVerification->value, $school->status);
        $this->assertFalse($school->canAccessPanel(filament()->getPanel('admin')));
        $this->assertDatabaseHas('audit_events', ['action' => 'account.created', 'resource_id' => (string) $school->id]);
        $this->assertDatabaseHas('institution_representatives', ['user_id' => $school->id, 'verified_at' => null]);
    }

    public function test_one_primary_role_is_enforced_by_postgresql(): void
    {
        $user = $this->userWithRole(AccountRole::Admin);
        $secondRoleId = DB::table('roles')->where('name', AccountRole::Osds->value)->value('id');

        $this->expectException(QueryException::class);
        DB::table('model_has_roles')->insert(['role_id' => $secondRoleId, 'model_type' => User::class, 'model_id' => $user->id]);
    }

    public function test_self_escalation_and_delegation_beyond_authority_are_denied(): void
    {
        $region = Region::query()->create(['code' => 'DENY', 'name' => 'Denied Region']);
        $admin = $this->userWithRole(AccountRole::Admin);
        $this->bootstrapRegion($admin, $region);
        $this->actingAs($admin);

        $this->expectException(AuthorizationException::class);
        app(AccessGovernance::class)->grantRegion($admin, $admin, $region, 'Self escalation attempt');
    }

    public function test_school_verification_is_separate_and_creator_rule_is_configurable(): void
    {
        config()->set('account-governance.allow_school_creator_to_verify', false);
        $region = Region::query()->create(['code' => 'VERIFY', 'name' => 'Verification Region']);
        $creator = $this->userWithRole(AccountRole::Chedro);
        $verifier = $this->userWithRole(AccountRole::Chedro);
        $this->bootstrapRegion($creator, $region);
        $this->bootstrapRegion($verifier, $region);
        $institution = Institution::query()->create(['name' => 'Verification School', 'primary_region_id' => $region->id]);

        $this->actingAs($creator);
        $school = app(AccountGovernance::class)->create($creator, AccountRole::School, $this->attributes('pending-school'), institutionId: $institution->id);
        $representation = InstitutionRepresentative::query()->where('user_id', $school->id)->firstOrFail();

        try {
            app(AccountGovernance::class)->verifySchool($creator, $school, $representation);
            $this->fail('The creator verified their own SCHOOL account while the policy disabled it.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $this->actingAs($verifier);
        app(AccountGovernance::class)->verifySchool($verifier, $school, $representation);
        $this->assertSame(AccountStatus::Active->value, $school->refresh()->status);
        $this->assertDatabaseHas('audit_events', ['action' => 'account.verified', 'resource_id' => (string) $school->id]);
        $this->assertDatabaseHas('account_status_events', ['user_id' => $school->id, 'to_status' => AccountStatus::Active->value]);
    }

    public function test_revocation_is_immediate_invalidates_sessions_and_is_audited(): void
    {
        $region = Region::query()->create(['code' => 'REVOKE', 'name' => 'Revocation Region']);
        $admin = $this->userWithRole(AccountRole::Admin);
        $osds = $this->userWithRole(AccountRole::Osds);
        $this->bootstrapRegion($admin, $region);
        $grant = $this->bootstrapRegion($osds, $region, $admin);
        DB::table('sessions')->insert(['id' => 'revoked-session', 'user_id' => $osds->id, 'payload' => '', 'last_activity' => now()->timestamp]);

        $this->actingAs($admin);
        app(AccessGovernance::class)->revokeRegion($admin, $grant, 'Personnel reassigned');

        $this->assertFalse(app(ScopeAuthorizer::class)->canAccessRegion($osds, $region->id));
        $this->assertDatabaseMissing('sessions', ['id' => 'revoked-session']);
        $this->assertDatabaseHas('audit_events', ['action' => 'region_scope.revoked', 'resource_id' => (string) $grant->id]);
    }

    public function test_nationwide_access_requires_an_explicit_grant_and_can_be_revoked(): void
    {
        $region = Region::query()->create(['code' => 'NAT', 'name' => 'National Test Region']);
        $super = $this->userWithRole(AccountRole::SuperUser);
        $admin = $this->userWithRole(AccountRole::Admin);
        $this->assertFalse(app(ScopeAuthorizer::class)->canAccessRegion($admin, $region->id));

        $this->actingAs($super);
        $grant = app(AccessGovernance::class)->grantNationwide($super, $admin, 'Temporary national support');
        $this->assertTrue(app(ScopeAuthorizer::class)->canAccessRegion($admin, $region->id));
        app(AccessGovernance::class)->revokeNationwide($super, $grant, 'Support complete');
        $this->assertFalse(app(ScopeAuthorizer::class)->canAccessRegion($admin, $region->id));
    }

    public function test_account_lifecycle_is_transactional_audited_and_invalidates_sessions(): void
    {
        $region = Region::query()->create(['code' => 'LIFE', 'name' => 'Lifecycle Region']);
        $admin = $this->userWithRole(AccountRole::Admin);
        $osds = $this->userWithRole(AccountRole::Osds);
        $this->bootstrapRegion($admin, $region);
        $this->bootstrapRegion($osds, $region, $admin);
        DB::table('sessions')->insert(['id' => 'lifecycle-session', 'user_id' => $osds->id, 'payload' => '', 'last_activity' => now()->timestamp]);

        $this->actingAs($admin);
        app(AccountGovernance::class)->transition($admin, $osds, AccountStatus::Suspended, 'Security review');
        $this->assertSame(AccountStatus::Suspended->value, $osds->refresh()->status);
        $this->assertDatabaseMissing('sessions', ['id' => 'lifecycle-session']);
        $this->assertDatabaseHas('audit_events', ['action' => 'account.suspended', 'resource_id' => (string) $osds->id]);
        $this->assertDatabaseHas('account_status_events', ['user_id' => $osds->id, 'to_status' => AccountStatus::Suspended->value]);

        app(AccountGovernance::class)->transition($admin, $osds, AccountStatus::Active, 'Review completed');
        $this->assertSame(AccountStatus::Active->value, $osds->refresh()->status);
    }

    public function test_append_only_lifecycle_and_audit_integrity(): void
    {
        $actor = $this->userWithRole(AccountRole::Admin);
        $event = AccountStatusEvent::query()->create([
            'user_id' => $actor->id, 'from_status' => 'active', 'to_status' => 'suspended',
            'changed_by' => $actor->id, 'reason' => 'Test', 'correlation_id' => fake()->uuid(),
        ]);
        $this->expectException(\LogicException::class);
        $event->delete();
    }

    public function test_postgresql_rejects_direct_audit_mutation(): void
    {
        $actor = $this->userWithRole(AccountRole::Admin);
        $event = app(AuditRecorder::class)->record('integrity.test', 'success', $actor);

        $this->expectException(QueryException::class);
        DB::table('audit_events')->where('id', $event->id)->update(['outcome' => 'failure']);
    }

    public function test_competing_account_creation_cannot_duplicate_identity_or_leave_partial_audit(): void
    {
        $region = Region::query()->create(['code' => 'RACE', 'name' => 'Concurrency Region']);
        $super = $this->userWithRole(AccountRole::SuperUser);
        $this->bootstrapRegion($super, $region);
        $this->actingAs($super);
        $attributes = $this->attributes('concurrent-admin');

        app(AccountGovernance::class)->create($super, AccountRole::Admin, $attributes, $region->id);
        $auditCount = AuditEvent::query()->where('action', 'account.created')->count();

        try {
            app(AccountGovernance::class)->create($super, AccountRole::Admin, $attributes, $region->id);
            $this->fail('A competing duplicate account creation was accepted.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }

        $this->assertSame(1, User::query()->where('email', $attributes['email'])->count());
        $this->assertSame($auditCount, AuditEvent::query()->where('action', 'account.created')->count());
    }

    public function test_stale_duplicate_lifecycle_change_is_rejected(): void
    {
        $region = Region::query()->create(['code' => 'STALE', 'name' => 'Stale Change Region']);
        $admin = $this->userWithRole(AccountRole::Admin);
        $osds = $this->userWithRole(AccountRole::Osds);
        $this->bootstrapRegion($admin, $region);
        $this->bootstrapRegion($osds, $region, $admin);
        $this->actingAs($admin);

        app(AccountGovernance::class)->transition($admin, $osds, AccountStatus::Suspended, 'First request');
        $this->expectException(AuthorizationException::class);
        app(AccountGovernance::class)->transition($admin, $osds, AccountStatus::Suspended, 'Stale competing request');
    }

    public function test_account_creation_endpoint_requires_an_explicit_creation_permission(): void
    {
        $region = Region::query()->create(['code' => 'HTTP', 'name' => 'Endpoint Region']);
        $admin = $this->userWithRole(AccountRole::Admin);
        $this->bootstrapRegion($admin, $region);
        $this->actingAs($admin)->get('/admin/users/create')->assertOk();

        $school = $this->userWithRole(AccountRole::School);
        $this->actingAs($school)->get('/admin/users/create')->assertForbidden();
    }

    private function userWithRole(AccountRole $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function bootstrapRegion(User $user, Region $region, ?User $grantor = null): UserRegionAssignment
    {
        return UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::query()->create([
            'user_id' => $user->id, 'region_id' => $region->id, 'granted_by' => $grantor?->id, 'starts_at' => now()->subMinute(),
        ]));
    }

    private function attributes(string $prefix): array
    {
        return ['name' => ucfirst($prefix), 'email' => "{$prefix}@example.invalid", 'password' => 'Synthetic-password-123!'];
    }
}
