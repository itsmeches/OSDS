<?php

namespace Tests\Feature;

use App\Filament\Resources\InstitutionRepresentatives\Pages\EditInstitutionRepresentative;
use App\Filament\Resources\UserRegionAssignments\Pages\EditUserRegionAssignment;
use App\Models\AuditEvent;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\NationwideAccessGrant;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Authorization\AccessGovernance;
use App\Support\AccountRole;
use Carbon\CarbonImmutable;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AssignmentEditSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        $this->seed(AuthorizationSeeder::class);
    }

    private function user(AccountRole $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function fixture(AccountRole $targetRole = AccountRole::Osds): array
    {
        $region = Region::create(['code' => 'SEC008-'.Region::count(), 'name' => 'Disposable assignment region']);
        $actor = $this->user(AccountRole::Admin);
        $target = $this->user($targetRole);
        $scope = UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $actor->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]));
        $grant = UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $target->id, 'region_id' => $region->id, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay()]));

        return [$actor, $target, $scope, $grant, $region];
    }

    private function state($grant): array
    {
        return [$grant->fresh()->getRawOriginal(), AuditEvent::count()];
    }

    private function save($page, array $values)
    {
        return $page->update(calls: [['method' => 'save', 'params' => [false], 'path' => '']], updates: collect($values)->mapWithKeys(fn ($v, $k) => ['data.'.$k => $v])->all());
    }

    public function test_forbidden_self_target_region_and_hidden_fields_cannot_persist(): void
    {
        [$actor, $target, $scope, $grant] = $this->fixture();
        $other = Region::create(['code' => 'UNASSIGNED', 'name' => 'Outside scope']);
        foreach ([['user_id' => $actor->id], ['region_id' => $other->id], ['granted_by' => $target->id], ['revoked_at' => now()->toDateTimeString()], ['status' => 'active'], ['revoked_by' => $actor->id]] as $attack) {
            $page = Livewire::actingAs($actor->fresh())->test(EditUserRegionAssignment::class, ['record' => $grant->id]);
            $before = $this->state($grant);
            $this->save($page, ['ends_at' => now()->addYear()->toDateTimeString(), ...$attack])->assertForbidden();
            $this->assertSame($before, $this->state($grant));
        }
        foreach ([['region_id' => $other->id], ['ends_at' => now()->addMinutes(10)->toDateTimeString()], ['ends_at' => now()->addYear()->toDateTimeString()]] as $attack) {
            $page = Livewire::actingAs($actor->fresh())->test(EditUserRegionAssignment::class, ['record' => $scope->id]);
            $before = $this->state($scope);
            $this->save($page, $attack)->assertForbidden();
            $this->assertSame($before, $this->state($scope));
        }
        [$actor, , , $grant] = $this->fixture(AccountRole::SuperUser);
        $page = Livewire::actingAs($actor)->test(EditUserRegionAssignment::class, ['record' => $grant->id]);
        $before = $this->state($grant);
        $this->save($page, ['ends_at' => now()->addYear()->toDateTimeString()])->assertForbidden();
        $this->assertSame($before, $this->state($grant));
    }

    public function test_authorized_dates_repeat_save_and_validation_preserve_bindings(): void
    {
        [$actor, $target, , $grant] = $this->fixture();
        $page = Livewire::actingAs($actor)->test(EditUserRegionAssignment::class, ['record' => $grant->id]);
        $end = now()->addDays(2)->startOfSecond();
        $this->save($page, ['ends_at' => $end->toDateTimeString()])->assertHasNoFormErrors();
        $this->assertSame($end->timestamp, $grant->fresh()->ends_at->timestamp);
        $this->assertSame($target->id, $grant->fresh()->user_id);
        $before = $this->state($grant);
        $page->call('save', false)->assertHasNoFormErrors();
        $this->assertSame($before, $this->state($grant));
        $this->save($page, ['ends_at' => now()->subDays(3)->toDateTimeString()])->assertHasFormErrors(['ends_at']);
        $this->assertSame($before, $this->state($grant));
    }

    public function test_post_mount_permission_scope_identity_and_target_changes_deny_save(): void
    {
        foreach (['permission', 'expired', 'future', 'revoked', 'inactive', 'target'] as $change) {
            [$actor, $target, $scope, $grant] = $this->fixture();
            $page = Livewire::actingAs($actor)->test(EditUserRegionAssignment::class, ['record' => $grant->id]);
            match ($change) {
                'permission' => (function () use ($actor) {
                    $permissions = $actor->getAllPermissions()->pluck('name')->reject(fn ($p) => $p === 'region_scopes.assign');
                    $actor->syncRoles([]);
                    $actor->syncPermissions($permissions);
                })(),
                'expired' => DB::table('user_region_assignments')->where('id', $scope->id)->update(['ends_at' => now()->subMinute()->toIso8601String()]),
                'future' => $scope->update(['starts_at' => now()->addDay()->toIso8601String()]),
                'revoked' => $scope->update(['revoked_at' => now()]),
                'inactive' => $actor->update(['status' => 'inactive']),
                'target' => $target->syncRoles([AccountRole::SuperUser->value]),
            };
            $before = $this->state($grant);
            $response = $this->save($page, ['ends_at' => now()->addYear()->toDateTimeString()]);
            if (in_array($change, ['target'], true)) {
                $response->assertForbidden();
            } else {
                $response->assertNotFound();
            }
            $this->assertSame($before, $this->state($grant), $change);
        }
    }

    public function test_stale_and_revoked_or_expired_assignment_cannot_be_reopened(): void
    {
        foreach (['modified', 'revoked', 'expired'] as $change) {
            [$actor, , , $grant] = $this->fixture();
            $page = Livewire::actingAs($actor)->test(EditUserRegionAssignment::class, ['record' => $grant->id]);
            match ($change) {
                'modified' => $grant->update(['ends_at' => now()->addDays(3)]),
                'revoked' => $grant->update(['revoked_at' => now()]),
                'expired' => $grant->update(['ends_at' => now()->subMinute()->toIso8601String()]),
            };
            $before = $this->state($grant);
            $result = $this->save($page, ['ends_at' => now()->addYear()->toDateTimeString()]);
            if ($change === 'modified') {
                $result->assertHasFormErrors(['starts_at']);
            } else {
                $result->assertForbidden();
            }
            $this->assertSame($before, $this->state($grant));
        }
    }

    public function test_direct_routes_helpers_and_explicit_offset_scope_boundaries(): void
    {
        [$actor, , $scope, $grant] = $this->fixture();
        $this->actingAs($actor)->get('/admin/user-region-assignments/'.$grant->id.'/edit')->assertOk();
        $page = Livewire::actingAs($actor)->test(EditUserRegionAssignment::class, ['record' => $grant->id]);
        $page->call('getRecord');
        $this->assertStringNotContainsString($grant->user()->firstOrFail()->email, json_encode($page->effects['returns']));
        $this->assertStringNotContainsString($actor->password, json_encode($page->effects['returns']));
        DB::table('user_region_assignments')->where('id', $scope->id)->update(['ends_at' => now()->subMinute()->setTimezone('Asia/Manila')->toIso8601String()]);
        $this->actingAs($actor->fresh())->get('/admin/user-region-assignments/'.$grant->id.'/edit')->assertNotFound();
        $page->call('getRecord')->assertNotFound();
        $this->assertStringNotContainsString('Disposable assignment region', json_encode($page->effects));
        $this->assertStringNotContainsString($actor->password, json_encode($page->effects));
    }

    public function test_effective_nationwide_scope_retains_delegation_exception(): void
    {
        [$actor, , $scope, $grant] = $this->fixture();
        $scope->update(['revoked_at' => now()]);
        NationwideAccessGrant::create(['user_id' => $actor->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay(), 'reason' => 'Isolated nationwide boundary']);
        $end = now()->addDays(4)->startOfSecond();
        $page = Livewire::actingAs($actor->fresh())->test(EditUserRegionAssignment::class, ['record' => $grant->id]);
        $this->save($page, ['ends_at' => $end->toDateTimeString()])->assertHasNoFormErrors();
        $this->assertSame($end->timestamp, $grant->fresh()->ends_at->timestamp);
    }

    public function test_representation_editor_uses_same_delegation_and_stale_boundary(): void
    {
        [$actor, $target, , , $region] = $this->fixture();
        $organization = Institution::create(['name' => 'Disposable representation', 'primary_region_id' => $region->id]);
        $grant = InstitutionRepresentative::withoutEvents(fn () => InstitutionRepresentative::create(['user_id' => $target->id, 'institution_id' => $organization->id, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay()]));
        $page = Livewire::actingAs($actor)->test(EditInstitutionRepresentative::class, ['record' => $grant->id]);
        $end = now()->addDays(3)->startOfSecond();
        $this->save($page, ['ends_at' => $end->toDateTimeString()])->assertHasNoFormErrors();
        $this->assertSame($end->timestamp, $grant->fresh()->ends_at->timestamp);
        $page = Livewire::actingAs($actor)->test(EditInstitutionRepresentative::class, ['record' => $grant->id]);
        $before = $this->state($grant);
        $this->save($page, ['verified_at' => now()->toDateTimeString(), 'ends_at' => now()->addYear()->toDateTimeString()])->assertForbidden();
        $this->assertSame($before, $this->state($grant));
        $self = InstitutionRepresentative::withoutEvents(fn () => InstitutionRepresentative::create(['user_id' => $actor->id, 'institution_id' => $organization->id, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDay()]));
        $page = Livewire::actingAs($actor)->test(EditInstitutionRepresentative::class, ['record' => $self->id]);
        $before = $this->state($self);
        $this->save($page, ['ends_at' => $end->toDateTimeString()])->assertForbidden();
        $this->assertSame($before, $this->state($self));
    }

    public function test_two_real_editors_reject_stale_save_and_keep_local_dates(): void
    {
        [$actor, , , $grant] = $this->fixture();
        $first = Livewire::actingAs($actor)->test(EditUserRegionAssignment::class, ['record' => $grant->id]);
        $second = Livewire::actingAs($actor)->test(EditUserRegionAssignment::class, ['record' => $grant->id]);
        $this->save($first, ['ends_at' => now()->addDays(3)->toDateTimeString()])->assertHasNoFormErrors();
        $before = $this->state($grant);
        $local = now()->addDays(4)->toDateTimeString();
        $this->save($second, ['ends_at' => $local])->assertHasFormErrors(['starts_at'])->assertSet('data.ends_at', $local);
        $this->assertSame($before, $this->state($grant));
    }

    public function test_service_checks_raw_delegation_and_normalizes_explicit_offset_instants(): void
    {
        [$actor, , $scope, $grant] = $this->fixture();
        $service = app(AccessGovernance::class);
        $grant = $grant->fresh();
        $end = now()->addDays(3)->startOfSecond()->setTimezone('Asia/Manila');
        $service->amendAssignment($actor, $grant, ['starts_at' => $grant->starts_at->toIso8601String(), 'ends_at' => $end->toIso8601String()], $service->assignmentVersion($grant));
        $this->assertSame($end->timestamp, $grant->fresh()->ends_at->timestamp);
        $before = $this->state($scope);
        try {
            $service->amendAssignment($actor, $scope, ['starts_at' => $scope->starts_at, 'ends_at' => $end], $service->assignmentVersion($scope));
            $this->fail('Self-delegation must be denied at the service boundary.');
        } catch (AuthorizationException) {
            $this->assertSame($before, $this->state($scope));
        }
        $this->assertSame('UTC', DB::selectOne('SHOW timezone')->TimeZone);
    }

    public function test_save_acknowledges_only_its_own_version_when_a_later_writer_commits(): void
    {
        [$actor, , , $grant] = $this->fixture();
        $page = Livewire::actingAs($actor)->test(InterleavedAssignmentEditor::class, ['record' => $grant->id]);
        $end = now()->addDays(3)->startOfSecond();
        $this->save($page, ['ends_at' => $end->toDateTimeString()])->assertHasNoFormErrors();
        $before = $this->state($grant);
        $this->assertSame($end->copy()->addDay()->timestamp, $grant->fresh()->ends_at->timestamp);
        $this->save($page, ['ends_at' => now()->addDays(5)->toDateTimeString()])->assertHasFormErrors(['starts_at']);
        $this->assertSame($before, $this->state($grant));
    }
}

// Deterministic interleaving after the actual authorized service write and
// before the rest of Filament's Save lifecycle. No authorization is mocked.
class InterleavedAssignmentEditor extends EditUserRegionAssignment
{
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $saved = parent::handleRecordUpdate($record, $data);
        $saved->newQuery()->whereKey($saved->id)->update(['ends_at' => CarbonImmutable::parse($data['ends_at'])->addDay()->utc()]);

        return $saved;
    }
}
