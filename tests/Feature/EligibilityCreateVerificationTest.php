<?php

namespace Tests\Feature;

use App\Filament\Resources\ApplicantSandboxEligibilityGrants\Pages\CreateApplicantSandboxEligibilityGrant;
use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EligibilityCreateVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $region = Region::create(['code' => 'K2', 'name' => 'Disposable K2 region']);
        $institution = Institution::create(['name' => 'Disposable K2 organization', 'primary_region_id' => $region->id, 'is_synthetic' => true]);
        $real = Institution::create(['name' => 'Disposable non-synthetic organization', 'primary_region_id' => $region->id, 'is_synthetic' => false]);
        $module = OsdsModule::create(['code' => 'k2-active', 'name' => 'Disposable K2 service', 'is_active' => true]);
        $inactive = OsdsModule::create(['code' => 'k2-inactive', 'name' => 'Disposable inactive service', 'is_active' => false]);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->givePermissionTo(['platform.admin.access', 'applicant.sandbox.publish']);
        $other = User::factory()->create(['is_synthetic' => true]);
        $this->actingAs($actor);
        $data = ['institution_id' => $institution->id, 'module_id' => $module->id, 'starts_at' => now()->subHour()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString(), 'reason' => 'Disposable K2 eligibility control'];

        return [$actor, $institution, $module, $data, $real, $inactive, $other];
    }

    private function record(string $case, array $data): void
    {
        $file = base_path('docs/evidence/phase-3d7-k2-eligibility-create.json');
        if (! is_dir(dirname($file))) {
            mkdir(dirname($file), 0777, true);
        }
        $all = $case === 'authorized' || ! is_file($file) ? [] : json_decode(file_get_contents($file), true);
        $all[$case] = $data;
        file_put_contents($file, json_encode($all, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    }

    public function test_authorized_actual_create_sets_organization_module_dates_creator_and_initial_state(): void
    {
        [$actor, $institution, $module, $data] = $this->fixture();
        $this->assertSame(0, $actor->regionAssignments()->count());
        $this->assertSame(0, $actor->representedInstitutions()->count());
        $before = AuditEvent::count();
        Livewire::actingAs($actor)->test(CreateApplicantSandboxEligibilityGrant::class)->fillForm($data)->call('create')->assertHasNoFormErrors()->assertRedirect();
        $row = ApplicantSandboxEligibilityGrant::sole();
        $this->assertSame($institution->id, $row->institution_id);
        $this->assertSame($module->id, $row->module_id);
        $this->assertSame($actor->id, $row->granted_by);
        $this->assertSame($data['starts_at'], $row->starts_at->toDateTimeString());
        $this->assertSame($data['ends_at'], $row->ends_at->toDateTimeString());
        $this->assertSame($data['reason'], $row->reason);
        $this->assertNull($row->revoked_at);
        $this->assertNull($row->revoked_by);
        $this->assertSame($before, AuditEvent::count());
        $this->record('authorized', ['row' => $row->toArray(), 'audit_before' => $before, 'audit_after' => AuditEvent::count(), 'actor_regional_grants' => 0, 'actor_representations' => 0]);
    }

    public static function deniedActors(): array
    {
        return [['permission'], ['inactive'], ['deactivated'], ['production'], ['panel']];
    }

    public function test_initial_permissionless_actor_and_guest_cannot_open_create_route(): void
    {
        [$actor] = $this->fixture();
        $actor->revokePermissionTo('applicant.sandbox.publish');
        $before = AuditEvent::count();
        $this->get('/admin/applicant-sandbox-eligibility-grants/create')->assertForbidden();
        $this->assertSame($before, AuditEvent::count());
        $lastAudit = AuditEvent::max('id');
        auth()->logout();
        // SessionGuard rotates the actor's remember token on logout. Core's
        // existing User.updated event is distinct from eligibility creation.
        $logoutAudit = AuditEvent::where('id', '>', $lastAudit)->sole();
        $this->assertSame(User::class.'.updated', $logoutAudit->action);
        $this->assertContains('remember_token', $logoutAudit->metadata['changed_fields']);
        $afterLogout = AuditEvent::count();
        $this->get('/admin/applicant-sandbox-eligibility-grants/create')->assertRedirect('/admin/login');
        $this->assertSame(0, ApplicantSandboxEligibilityGrant::count());
        $this->assertSame($afterLogout, AuditEvent::count());
        $this->record('initial-denials', ['permissionless_status' => 403, 'guest_status' => 302, 'grant_count' => 0, 'route_audit_delta' => 0, 'logout_event' => $logoutAudit->action, 'logout_audit_delta' => $afterLogout - $before]);
    }

    #[DataProvider('deniedActors')]
    public function test_existing_actor_and_environment_denials_leave_no_partial_grant(string $case): void
    {
        [$actor, , , $data] = $this->fixture();
        $page = Livewire::actingAs($actor)->test(CreateApplicantSandboxEligibilityGrant::class)->fillForm($data);
        if ($case === 'permission' || $case === 'panel') {
            $actor->revokePermissionTo($case === 'permission' ? 'applicant.sandbox.publish' : 'platform.admin.access');
        } elseif ($case === 'production') {
            $this->app->detectEnvironment(fn () => 'production');
        } else {
            DB::table('users')->where('id', $actor->id)->update($case === 'inactive' ? ['status' => 'inactive'] : ['deactivated_at' => now()]);
        }
        $before = AuditEvent::count();
        if ($case === 'panel') {
            $this->get('/admin/applicant-sandbox-eligibility-grants/create')->assertForbidden();
        } else {
            $page->call('create')->assertForbidden();
        }
        $this->assertSame(0, ApplicantSandboxEligibilityGrant::count());
        $this->assertSame($before, AuditEvent::count());
        $this->record('denied-'.$case, ['grant_count' => 0, 'audit_delta' => 0, 'status' => 403]);
    }

    public static function invalidFields(): array
    {
        return [['real-institution', 'institution_id'], ['missing-institution', 'institution_id'], ['inactive-module', 'module_id'], ['missing-module', 'module_id'], ['missing-start', 'starts_at'], ['end-before-start', 'ends_at'], ['missing-reason', 'reason'], ['long-reason', 'reason']];
    }

    #[DataProvider('invalidFields')]
    public function test_actual_form_rejects_unavailable_options_and_invalid_input(string $case, string $field): void
    {
        [$actor, , , $data, $real, $inactive] = $this->fixture();
        $data[$field] = match ($case) {
            'real-institution' => $real->id,
            'inactive-module' => $inactive->id,
            'missing-institution', 'missing-module' => 999999,
            'end-before-start' => now()->subDay()->toDateTimeString(),
            'long-reason' => str_repeat('X', 501),
            default => '',
        };
        $before = AuditEvent::count();
        Livewire::actingAs($actor)->test(CreateApplicantSandboxEligibilityGrant::class)->fillForm($data)->call('create')->assertHasFormErrors([$field]);
        $this->assertSame(0, ApplicantSandboxEligibilityGrant::count());
        $this->assertSame($before, AuditEvent::count());
        $this->record('invalid-'.$case, ['field' => $field, 'grant_count' => 0, 'audit_delta' => 0]);
    }

    public function test_forged_creator_applicant_revocation_and_timestamp_fields_are_not_accepted(): void
    {
        [$actor, , , $data, , , $other] = $this->fixture();
        $updates = collect($data)->mapWithKeys(fn ($v, $k) => ['data.'.$k => $v])->all();
        $updates += ['data.granted_by' => $other->id, 'data.applicant_id' => $other->id, 'data.revoked_by' => $other->id, 'data.revoked_at' => now()->toDateTimeString(), 'data.created_at' => '2000-01-01 00:00:00'];
        $before = AuditEvent::count();
        Livewire::actingAs($actor)->test(CreateApplicantSandboxEligibilityGrant::class)->update(updates: $updates, calls: [['method' => 'create', 'params' => [], 'path' => '']])->assertHasNoFormErrors()->assertRedirect();
        $row = ApplicantSandboxEligibilityGrant::sole();
        $this->assertSame($actor->id, $row->granted_by);
        $this->assertNull($row->revoked_at);
        $this->assertNull($row->revoked_by);
        $this->assertFalse($row->created_at->isSameDay('2000-01-01'));
        $this->assertArrayNotHasKey('applicant_id', $row->getAttributes());
        $this->assertSame($before, AuditEvent::count());
        $this->record('forged', ['row' => $row->toArray(), 'audit_delta' => 0]);
    }

    public function test_existing_unrevoked_pair_uniqueness_rejects_a_duplicate_without_partial_creation(): void
    {
        [$actor, , , $data] = $this->fixture();
        Livewire::actingAs($actor)->test(CreateApplicantSandboxEligibilityGrant::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $before = ApplicantSandboxEligibilityGrant::sole()->getRawOriginal();
        $audits = AuditEvent::count();
        $caught = false;
        try {
            // A savepoint keeps a genuine rejected SQL statement from poisoning
            // PHPUnit's wrapping fixture transaction. It changes no runtime page.
            DB::transaction(fn () => Livewire::actingAs($actor)->test(CreateApplicantSandboxEligibilityGrant::class)->fillForm($data)->call('create'));
        } catch (UniqueConstraintViolationException $error) {
            $caught = true;
            $this->assertStringContainsString('active_applicant_sandbox_eligibility_unique', $error->getMessage());
        }
        $this->assertTrue($caught);
        $this->assertSame(1, ApplicantSandboxEligibilityGrant::count());
        $this->assertSame($before, ApplicantSandboxEligibilityGrant::sole()->getRawOriginal());
        $this->assertSame($audits, AuditEvent::count());
        $this->record('duplicate', ['grant_count' => 1, 'original_unchanged' => true, 'constraint' => 'active_applicant_sandbox_eligibility_unique', 'audit_delta' => 0]);
    }
}
