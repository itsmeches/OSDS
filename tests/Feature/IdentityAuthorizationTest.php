<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\ScopeAuthorizer;
use App\Support\AccountRole;
use App\Support\FoundationPermission;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class IdentityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_five_roles_are_explicit_and_have_no_inheritance(): void
    {
        $this->assertEqualsCanonicalizing(
            array_column(AccountRole::cases(), 'value'),
            Role::query()->pluck('name')->all(),
        );

        $school = $this->userWithRole(AccountRole::School);
        $this->assertTrue($school->can(FoundationPermission::ViewInstitutions->value));
        $this->assertFalse($school->can(FoundationPermission::AccessAdminPanel->value));
        $this->assertFalse($school->can(FoundationPermission::ManageInstitutions->value));
    }

    public function test_filament_requires_explicit_permission_and_active_account(): void
    {
        $staff = $this->userWithRole(AccountRole::Chedro);
        $this->actingAs($staff)->get('/admin')->assertOk();

        $school = $this->userWithRole(AccountRole::School);
        $this->actingAs($school)->get('/admin')->assertForbidden();

        DB::table('sessions')->insert([
            'id' => 'synthetic-session', 'user_id' => $staff->id, 'payload' => '', 'last_activity' => now()->timestamp,
        ]);
        $staff->update(['status' => 'inactive', 'deactivated_at' => now()]);
        $this->assertDatabaseMissing('sessions', ['id' => 'synthetic-session']);
        $this->actingAs($staff)->get('/admin')->assertForbidden();
        $this->assertGuest();
    }

    public function test_region_and_institution_access_are_isolated_and_revocable(): void
    {
        [$north, $south] = $this->regions();
        $staff = $this->userWithRole(AccountRole::Chedro);
        $northInstitution = Institution::query()->create(['name' => 'North Test Institute', 'primary_region_id' => $north->id]);
        $southInstitution = Institution::query()->create(['name' => 'South Test Institute', 'primary_region_id' => $south->id]);
        $assignment = UserRegionAssignment::query()->create(['user_id' => $staff->id, 'region_id' => $north->id, 'starts_at' => now()->subMinute()]);

        $this->actingAs($staff);
        $this->get('/admin/regions')->assertOk();
        $this->get('/admin/institutions')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/institution-representatives')->assertOk();
        $this->get('/admin/audit-events')->assertForbidden();

        $authorizer = app(ScopeAuthorizer::class);
        $this->assertTrue($authorizer->canAccessRegion($staff, $north->id));
        $this->assertFalse($authorizer->canAccessRegion($staff, $south->id));
        $this->assertTrue($authorizer->canAccessInstitution($staff, $northInstitution));
        $this->assertFalse($authorizer->canAccessInstitution($staff, $southInstitution));
        $this->assertFalse($staff->can('view', $southInstitution));

        $this->get("/admin/institutions/{$southInstitution->id}/edit")->assertNotFound();

        try {
            Institution::query()->create(['name' => 'Forged South Institute', 'primary_region_id' => $south->id]);
            $this->fail('Cross-region institution creation was accepted.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $assignment->update(['revoked_at' => now(), 'revoked_by' => $staff->id]);
        $this->assertFalse($authorizer->canAccessRegion($staff, $north->id));
    }

    public function test_school_representation_is_limited_to_its_institution(): void
    {
        [$north] = $this->regions();
        $school = $this->userWithRole(AccountRole::School);
        $represented = Institution::query()->create(['name' => 'Represented Institute', 'primary_region_id' => $north->id]);
        $other = Institution::query()->create(['name' => 'Other Institute', 'primary_region_id' => $north->id]);
        $grant = InstitutionRepresentative::query()->create(['user_id' => $school->id, 'institution_id' => $represented->id, 'starts_at' => now()->subMinute()]);

        $this->assertTrue(app(ScopeAuthorizer::class)->canAccessInstitution($school, $represented));
        $this->assertFalse(app(ScopeAuthorizer::class)->canAccessInstitution($school, $other));

        $grant->update(['revoked_at' => now(), 'revoked_by' => $school->id]);
        $this->assertFalse(app(ScopeAuthorizer::class)->canAccessInstitution($school, $represented));
    }

    public function test_sensitive_changes_create_sanitized_append_only_audit_events(): void
    {
        $actor = $this->userWithRole(AccountRole::Admin);
        $region = Region::query()->create(['code' => 'AUD', 'name' => 'Audit Region']);

        $this->actingAs($actor);
        $event = app(AuditRecorder::class)->record(
            action: 'scope.revoked', outcome: 'success', actor: $actor, resource: $region,
            regionId: $region->id, metadata: ['reason_code' => 'test', 'password' => 'must-not-be-stored'],
        );

        $this->assertSame('test', $event->metadata['reason_code']);
        $this->assertArrayNotHasKey('password', $event->metadata);
        $this->expectException(LogicException::class);
        $event->update(['outcome' => 'failure']);
    }

    public function test_postgresql_rejects_invalid_grant_ranges(): void
    {
        [$region] = $this->regions();
        $user = $this->userWithRole(AccountRole::Admin);

        $this->expectException(QueryException::class);
        UserRegionAssignment::query()->create([
            'user_id' => $user->id, 'region_id' => $region->id,
            'starts_at' => now(), 'ends_at' => now()->subDay(),
        ]);
    }

    public function test_postgresql_rejects_unscoped_authorization_grants(): void
    {
        $user = $this->userWithRole(AccountRole::Admin);
        $permissionId = DB::table('permissions')->value('id');
        $this->expectException(QueryException::class);
        DB::table('scoped_authorization_grants')->insert([
            'user_id' => $user->id, 'permission_id' => $permissionId,
            'starts_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function userWithRole(AccountRole $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function regions(): array
    {
        return [
            Region::query()->create(['code' => 'TEST-N', 'name' => 'Test North']),
            Region::query()->create(['code' => 'TEST-S', 'name' => 'Test South']),
        ];
    }
}
