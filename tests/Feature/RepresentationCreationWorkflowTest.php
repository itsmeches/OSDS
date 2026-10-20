<?php

namespace Tests\Feature;

use App\Filament\Resources\InstitutionRepresentatives\Pages\CreateInstitutionRepresentative;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\NationwideAccessGrant;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\RepresentationCreationService;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RepresentationCreationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $region = Region::create(['code' => 'G7-W', 'name' => 'Synthetic representation region']);
        $institution = Institution::create(['name' => 'Synthetic institution', 'primary_region_id' => $region->id, 'is_synthetic' => true]);
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->assignRole(AccountRole::Chedro->value);
        $target = User::factory()->create(['is_synthetic' => true]);
        $target->assignRole(AccountRole::School->value);
        $scope = UserRegionAssignment::withoutEvents(function () use ($actor, $target, $region) {
            UserRegionAssignment::create(['user_id' => $target->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]);

            return UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]);
        });
        $data = ['user_id' => $target->id, 'institution_id' => $institution->id, 'starts_at' => now()->subHour()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString()];
        $page = Livewire::actingAs($actor)->test(CreateInstitutionRepresentative::class)->fillForm($data);

        return [$actor, $target, $institution, $scope, $data, $page];
    }

    private function state(): array
    {
        return [InstitutionRepresentative::orderBy('id')->get()->toArray(), AuditEvent::count()];
    }

    public static function faults(): array
    {
        return [['after'], ['database']];
    }

    #[DataProvider('faults')]
    public function test_audit_insert_faults_roll_back_both_writes_and_redact_feedback(string $fault): void
    {
        [$actor, , , , $data, $page] = $this->fixture();
        $before = $this->state();
        $level = DB::transactionLevel();
        $inserted = false;
        $rejected = false;
        Log::spy();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($fault, $level, &$inserted, &$rejected) {
            $this->assertSame(InstitutionRepresentative::class.'.created', $args[0]);
            $this->assertSame($level + 1, DB::transactionLevel());
            $this->assertSame((new AuditEvent)->getConnection()->getPdo(), (new InstitutionRepresentative)->getConnection()->getPdo());
            if ($fault === 'database') {
                $missing = new User;
                $missing->id = -1;
                $args[2] = $missing;
                try {
                    return (new AuditRecorder)->record(...$args);
                } catch (QueryException $exception) {
                    $rejected = true;
                    throw $exception;
                }
            }
            (new AuditRecorder)->record(...$args);
            $inserted = true;
            throw new \RuntimeException('ISOLATED_REPRESENTATION_AUDIT_FAILURE');
        });
        $page->call('create')->assertHasFormErrors(['starts_at'])->assertNotified('Action could not be confirmed');
        $this->assertSame($fault === 'after', $inserted);
        $this->assertSame($fault === 'database', $rejected);
        $this->assertSame($level, DB::transactionLevel());
        $this->assertSame($before, $this->state());
        $page->assertFormSet($data);
        $response = json_encode([$page->snapshot, $page->effects, $page->html(false)], JSON_THROW_ON_ERROR);
        foreach (['ISOLATED_REPRESENTATION_AUDIT_FAILURE', 'SQLSTATE', 'Foreign key violation', $actor->password] as $secret) {
            $this->assertStringNotContainsString($secret, $response);
        }
        Log::shouldHaveReceived('error')->once()->withArgs(fn ($message, $context) => $message === 'Staff action could not be confirmed'
            && array_keys($context) === ['support_reference', 'exception_class']);
    }

    public static function losses(): array
    {
        return [['permission'], ['panel'], ['delegation'], ['inactive'], ['deactivated'], ['revoked'], ['expired'], ['future'], ['target-role'], ['institution-moved']];
    }

    #[DataProvider('losses')]
    public function test_current_authority_and_target_binding_are_required_after_mount(string $loss): void
    {
        [$actor, $target, $institution, $scope, $data, $page] = $this->fixture();
        if (in_array($loss, ['permission', 'panel', 'delegation'], true)) {
            $removed = match ($loss) {
                'panel' => 'platform.admin.access',
                'delegation' => 'accounts.create.school',
                default => 'institution_representatives.assign',
            };
            $permissions = $actor->getAllPermissions()->pluck('name')->reject(fn ($p) => $p === $removed)->all();
            $actor->syncRoles([]);
            $actor->syncPermissions($permissions);
        } elseif ($loss === 'target-role') {
            $target->syncRoles([AccountRole::SuperUser->value]);
        } elseif ($loss === 'institution-moved') {
            $outside = Region::create(['code' => 'G7-OUT', 'name' => 'Outside scope']);
            DB::table('institutions')->where('id', $institution->id)->update(['primary_region_id' => $outside->id]);
        } elseif (in_array($loss, ['inactive', 'deactivated'], true)) {
            DB::table('users')->where('id', $actor->id)->update($loss === 'inactive' ? ['status' => 'inactive'] : ['deactivated_at' => now()]);
        } else {
            DB::table('user_region_assignments')->where('id', $scope->id)->update(match ($loss) {
                'revoked' => ['revoked_at' => now()],
                'expired' => ['ends_at' => now()->subMinute()],
                'future' => ['starts_at' => now()->addDay()],
            });
        }
        $before = $this->state();
        $page->call('create');
        $this->assertSame($before, $this->state());
        try {
            app(RepresentationCreationService::class)->create($actor, $data);
            $this->fail('Current authority must be required.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->state());
        }
    }

    public static function invalidFields(): array
    {
        return [['user_id', -1], ['institution_id', -1], ['starts_at', ''], ['ends_at', '2000-01-01 00:00:00']];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_fields_are_rejected_without_mutation(string $field, mixed $value): void
    {
        [$actor, , , , $data, $page] = $this->fixture();
        $before = $this->state();
        $page->fillForm([$field => $value])->call('create')->assertHasFormErrors([$field]);
        $this->assertSame($before, $this->state());
        try {
            app(RepresentationCreationService::class)->create($actor, array_replace($data, [$field => $value]));
            $this->fail('Invalid service input must be rejected.');
        } catch (ValidationException) {
            $this->assertSame($before, $this->state());
        }
    }

    public function test_forged_verification_revocation_and_attribution_are_not_creation_fields(): void
    {
        [$actor, $target, , , $data, $page] = $this->fixture();
        foreach (['granted_by', 'verified_by', 'revoked_by'] as $field) {
            $page->set('data.'.$field, $target->id);
        }
        $page->set('data.verified_at', now()->toDateTimeString())->set('data.revoked_at', now()->toDateTimeString())->call('create')->assertHasNoFormErrors();
        $row = InstitutionRepresentative::where('user_id', $target->id)->sole();
        $this->assertSame($actor->id, $row->granted_by);
        foreach (['verified_at', 'verified_by', 'revoked_at', 'revoked_by'] as $field) {
            $this->assertNull($row->$field);
        }
    }

    public function test_self_cross_institution_and_detached_actor_are_denied(): void
    {
        [$actor, $target, , , $data] = $this->fixture();
        $outside = Region::create(['code' => 'G7-OUT', 'name' => 'Outside region']);
        $institution = Institution::withoutEvents(fn () => Institution::forceCreate(['public_id' => (string) Str::uuid(), 'name' => 'Outside institution', 'primary_region_id' => $outside->id]));
        foreach ([['user_id' => $actor->id], ['institution_id' => $institution->id]] as $forged) {
            $before = $this->state();
            Livewire::actingAs($actor)->test(CreateInstitutionRepresentative::class)->fillForm(array_replace($data, $forged))->call('create');
            $this->assertSame($before, $this->state());
            try {
                app(RepresentationCreationService::class)->create($actor, array_replace($data, $forged));
                $this->fail('Forbidden target must be rejected.');
            } catch (AuthorizationException) {
                $this->assertSame($before, $this->state());
            }
        }
        auth()->login($target);
        try {
            app(RepresentationCreationService::class)->create($actor, $data);
            $this->fail('Detached identity must be rejected.');
        } catch (AuthorizationException) {
            $this->assertSame(0, InstitutionRepresentative::count());
        }
    }

    public function test_existing_duplicate_and_revoked_history_rules_are_preserved(): void
    {
        [$actor, $target, , , $data, $page] = $this->fixture();
        $page->call('create')->assertHasNoFormErrors();
        $row = InstitutionRepresentative::sole();
        $before = $this->state();
        Livewire::actingAs($actor)->test(CreateInstitutionRepresentative::class)->fillForm($data)->call('create')->assertHasFormErrors(['starts_at']);
        $this->assertSame($before, $this->state());
        DB::table('institution_representatives')->where('id', $row->id)->update(['starts_at' => now()->subDays(2), 'ends_at' => now()->subDay()]);
        $before = $this->state();
        Livewire::actingAs($actor)->test(CreateInstitutionRepresentative::class)->fillForm($data)->call('create')->assertHasFormErrors(['starts_at']);
        $this->assertSame($before, $this->state());
        DB::table('institution_representatives')->where('id', $row->id)->update(['revoked_at' => now()]);
        Livewire::actingAs($actor)->test(CreateInstitutionRepresentative::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $this->assertSame(2, InstitutionRepresentative::where('user_id', $target->id)->count());
        $this->assertSame(2, AuditEvent::where('action', InstitutionRepresentative::class.'.created')->count());
    }

    public function test_effective_nationwide_and_verified_representation_scope_alternatives_are_preserved(): void
    {
        [$actor, , $institution, $scope, $data] = $this->fixture();
        DB::table('user_region_assignments')->where('id', $scope->id)->update(['revoked_at' => now()]);
        $existing = InstitutionRepresentative::withoutEvents(fn () => InstitutionRepresentative::create(['user_id' => $actor->id, 'institution_id' => $institution->id, 'granted_by' => $actor->id, 'verified_at' => now()->subHour(), 'starts_at' => now()->subDay()]));
        // Service scope alternative; selector user visibility remains separate.
        $first = app(RepresentationCreationService::class)->create($actor, $data);
        $this->assertSame($actor->id, $first->granted_by);
        DB::table('institution_representatives')->whereIn('id', [$first->id, $existing->id])->update(['revoked_at' => now()]);
        NationwideAccessGrant::withoutEvents(fn () => NationwideAccessGrant::create(['user_id' => $actor->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay(), 'reason' => 'Isolated effective nationwide authority']));
        $data['starts_at'] = now()->toDateTimeString();
        Livewire::actingAs($actor)->test(CreateInstitutionRepresentative::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $this->assertSame(3, InstitutionRepresentative::count());
        $actor->syncRoles([]);
        $this->actingAs($actor->fresh())->get('/admin/institution-representatives/create')->assertForbidden();
    }
}
