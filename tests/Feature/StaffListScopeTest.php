<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditEvents\AuditEventResource;
use App\Filament\Resources\InstitutionRepresentatives\InstitutionRepresentativeResource;
use App\Filament\Resources\InstitutionRepresentatives\Pages\CreateInstitutionRepresentative;
use App\Filament\Resources\Institutions\InstitutionResource;
use App\Filament\Resources\Institutions\Pages\CreateInstitution;
use App\Filament\Resources\Institutions\Pages\ListInstitutions;
use App\Filament\Resources\Regions\RegionResource;
use App\Filament\Resources\UserRegionAssignments\Pages\CreateUserRegionAssignment;
use App\Filament\Resources\UserRegionAssignments\UserRegionAssignmentResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\NationwideAccessGrant;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Authorization\StaffListScope;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Filament\Forms\Components\Select;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class StaffListScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 19)->startOfDay()->addHours(8));
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_every_staff_resource_query_checks_effective_grants_and_permissions(): void
    {
        $a = $this->records('A');
        $b = $this->records('B');
        foreach (['none', 'expired', 'future', 'revoked', 'end_boundary', 'wrong_region', 'inactive', 'permissionless', 'valid', 'start_boundary'] as $state) {
            $actor = $this->staff();
            if ($state !== 'none') {
                $this->regionGrant($actor, $state === 'wrong_region' ? $b['region'] : $a['region'], [
                    'starts_at' => $state === 'future' ? now()->addSecond() : ($state === 'start_boundary' ? now() : now()->subDay()),
                    'ends_at' => $state === 'expired' ? now()->subSecond() : ($state === 'end_boundary' ? now() : null),
                    'revoked_at' => $state === 'revoked' ? now() : null,
                ]);
            }
            if ($state === 'inactive') {
                $actor->forceFill(['status' => 'suspended'])->save();
            }
            if ($state === 'permissionless') {
                $actor->removeRole(AccountRole::Admin->value);
            }
            $this->actingAs($actor);
            foreach ($this->resources() as $key => [$resource, $route]) {
                $expected = in_array($state, ['valid', 'start_boundary'], true) ? 1 : 0;
                $query = $resource::getEloquentQuery()->whereKey($a[$key]->id);
                $this->assertSame($expected, (clone $query)->count(), "$key / $state count");
                $this->assertSame($expected, (clone $query)->paginate(1)->total(), "$key / $state pagination");
                $this->assertCount($expected, (clone $query)->where('id', $a[$key]->id)->get(), "$key / $state filter");
                if ($state === 'none') {
                    $this->get($route.'?search=SEC004&page=2')->assertOk()->assertDontSee('SEC004-A');
                }
                if ($state === 'permissionless' || $state === 'inactive') {
                    $this->actingAs($actor)->get($route)->assertForbidden()->assertDontSee('SEC004-A');
                }
            }
            if ($state === 'none') {
                $this->get('/admin/institutions/'.$a['institution']->id.'/edit')->assertNotFound();
                $this->get('/admin/regions/'.$a['region']->id.'/edit')->assertNotFound();
                $this->get('/admin/institution-representatives/'.$a['representative']->id.'/edit')->assertNotFound();
                $this->get('/admin/user-region-assignments/'.$a['assignment']->id.'/edit')->assertNotFound();
                $this->get('/admin/users/'.$a['user']->id.'/edit')->assertNotFound();
            }
            auth()->forgetGuards();
        }
    }

    public function test_valid_region_union_overlapping_history_and_livewire_expiration(): void
    {
        $a = $this->records('A');
        $b = $this->records('B');
        $c = $this->records('C');
        $actor = $this->staff();
        $this->regionGrant($actor, $a['region'], ['starts_at' => now()->subDays(3), 'revoked_at' => now()->subHour()]);
        $first = $this->regionGrant($actor, $a['region']);
        $second = $this->regionGrant($actor, $b['region']);
        $this->regionGrant($actor, $c['region'], ['ends_at' => now()->subSecond()]);
        $this->actingAs($actor);
        foreach ($this->resources() as $key => [$resource]) {
            $ids = [$a[$key]->id, $b[$key]->id, $c[$key]->id];
            $this->assertEqualsCanonicalizing([$a[$key]->id, $b[$key]->id], $resource::getEloquentQuery()->whereKey($ids)->pluck('id')->all());
        }
        $this->get('/admin/institutions/'.$a['institution']->id.'/edit')->assertOk();
        $component = Livewire::actingAs($actor)->test(ListInstitutions::class)
            ->assertCanSeeTableRecords([$a['institution'], $b['institution']])
            ->assertCanNotSeeTableRecords([$c['institution']])
            ->searchTable('SEC004-C')->assertCanNotSeeTableRecords([$c['institution']]);
        DB::table('user_region_assignments')->whereIn('id', [$first->id, $second->id])->update(['ends_at' => now()]);
        $component->searchTable('')->call('$refresh')->assertCanNotSeeTableRecords([$a['institution'], $b['institution'], $c['institution']]);
        $this->get('/admin/institutions/'.$a['institution']->id.'/edit')->assertNotFound();
        $this->assertFalse(app(ScopeAuthorizer::class)->canAccessRegion($actor, $a['region']->id));
    }

    public function test_effective_representation_and_audit_exceptions_match_existing_policy(): void
    {
        $a = $this->records('A');
        $actor = $this->staff();
        $ownEvent = app(AuditRecorder::class)->record('SEC004-own', 'success', $actor);
        $foreignEvent = app(AuditRecorder::class)->record('SEC004-foreign', 'success', $a['user']);
        $organizationEvent = app(AuditRecorder::class)->record('SEC004-institution', 'success', $a['user'], institutionId: $a['institution']->id);
        $grant = InstitutionRepresentative::withoutEvents(fn () => InstitutionRepresentative::create([
            'user_id' => $actor->id, 'institution_id' => $a['institution']->id, 'starts_at' => now()->subDay(), 'verified_at' => now(),
        ]));
        $this->actingAs($actor);
        $this->assertTrue(InstitutionResource::getEloquentQuery()->whereKey($a['institution']->id)->exists());
        $this->assertTrue(InstitutionRepresentativeResource::getEloquentQuery()->whereKey($a['representative']->id)->exists());
        $this->assertTrue(AuditEventResource::getEloquentQuery()->whereKey($organizationEvent->id)->exists());
        $this->assertTrue(AuditEventResource::getEloquentQuery()->whereKey($ownEvent->id)->exists());
        $this->assertFalse(AuditEventResource::getEloquentQuery()->whereKey($foreignEvent->id)->exists());
        foreach ([['verified_at' => null], ['ends_at' => now()], ['revoked_at' => now()], ['starts_at' => now()->addSecond()]] as $change) {
            DB::table('institution_representatives')->where('id', $grant->id)->update($change);
            $this->assertFalse(InstitutionResource::getEloquentQuery()->whereKey($a['institution']->id)->exists());
            $this->assertFalse(AuditEventResource::getEloquentQuery()->whereKey($organizationEvent->id)->exists());
            DB::table('institution_representatives')->where('id', $grant->id)->update(['verified_at' => now(), 'starts_at' => now()->subDay(), 'ends_at' => null, 'revoked_at' => null]);
        }
    }

    public function test_nationwide_access_requires_effective_explicit_grant_and_national_audit_permission(): void
    {
        $a = $this->records('A');
        $b = $this->records('B');
        $actor = $this->staff();
        $nationwide = NationwideAccessGrant::create(['user_id' => $actor->id, 'granted_by' => $actor->id, 'starts_at' => now()->subDay(), 'reason' => 'Isolated scope regression']);
        $foreignEvent = app(AuditRecorder::class)->record('SEC004-national', 'success', $a['user']);
        $this->actingAs($actor);
        foreach ($this->resources() as $key => [$resource]) {
            $this->assertSame(2, $resource::getEloquentQuery()->whereKey([$a[$key]->id, $b[$key]->id])->count());
        }
        $this->assertFalse(AuditEventResource::getEloquentQuery()->whereKey($foreignEvent->id)->exists());
        $actor->givePermissionTo('audit.view.national');
        $this->assertTrue(AuditEventResource::getEloquentQuery()->whereKey($foreignEvent->id)->exists());
        DB::table('nationwide_access_grants')->where('id', $nationwide->id)->update(['ends_at' => now()]);
        $this->assertFalse(AuditEventResource::getEloquentQuery()->whereKey($foreignEvent->id)->exists());
        $this->assertSame(0, RegionResource::getEloquentQuery()->whereKey([$a['region']->id, $b['region']->id])->count());
    }

    public function test_record_selector_queries_deny_out_of_scope_options(): void
    {
        $a = $this->records('A');
        $b = $this->records('B');
        $actor = $this->staff();
        $grant = $this->regionGrant($actor, $a['region']);
        $this->actingAs($actor);
        $scope = app(StaffListScope::class);
        foreach (['institutions.manage', 'institution_representatives.assign', 'region_scopes.assign'] as $permission) {
            $this->assertEquals([$a['region']->id], $scope->regions(Region::query(), $actor, $permission)->pluck('id')->all());
        }
        $this->assertEquals([$a['institution']->id], $scope->institutions(Institution::query(), $actor, 'institution_representatives.assign')->pluck('id')->all());
        foreach (['institution_representatives.assign', 'region_scopes.assign'] as $permission) {
            $this->assertFalse($scope->users(User::query(), $actor, $permission)->whereKey($b['user']->id)->exists());
            $this->assertTrue($scope->users(User::query(), $actor, $permission)->whereKey($a['user']->id)->exists());
        }
        $forms = [
            [CreateInstitution::class, 'primary_region_id', $a['region']->id, $b['region']->id],
            [CreateInstitutionRepresentative::class, 'institution_id', $a['institution']->id, $b['institution']->id],
            [CreateInstitutionRepresentative::class, 'user_id', $a['user']->id, $b['user']->id],
            [CreateUserRegionAssignment::class, 'region_id', $a['region']->id, $b['region']->id],
            [CreateUserRegionAssignment::class, 'user_id', $a['user']->id, $b['user']->id],
        ];
        foreach ($forms as [$page, $field, $visible, $hidden]) {
            Livewire::actingAs($actor)->test($page)->assertFormFieldExists($field, fn (Select $select) => array_key_exists($visible, $select->getOptions()) && ! array_key_exists($hidden, $select->getOptions()));
        }
        DB::table('user_region_assignments')->where('id', $grant->id)->update(['revoked_at' => now()]);
        $this->assertSame(0, $scope->regions(Region::query(), $actor, 'institutions.manage')->count());
        $this->assertSame(0, $scope->institutions(Institution::query(), $actor, 'institution_representatives.assign')->count());
        foreach ($forms as [$page, $field, $visible, $hidden]) {
            Livewire::actingAs($actor)->test($page)->assertFormFieldExists($field, fn (Select $select) => ! array_key_exists($visible, $select->getOptions()) && ! array_key_exists($hidden, $select->getOptions()));
        }
    }

    private function resources(): array
    {
        return [
            'region' => [RegionResource::class, '/admin/regions'],
            'institution' => [InstitutionResource::class, '/admin/institutions'],
            'representative' => [InstitutionRepresentativeResource::class, '/admin/institution-representatives'],
            'assignment' => [UserRegionAssignmentResource::class, '/admin/user-region-assignments'],
            'audit' => [AuditEventResource::class, '/admin/audit-events'],
            'user' => [UserResource::class, '/admin/users'],
        ];
    }

    private function records(string $suffix): array
    {
        $region = Region::create(['code' => 'SEC004-'.$suffix, 'name' => 'SEC004-'.$suffix]);
        $institution = Institution::create(['name' => 'SEC004-'.$suffix, 'primary_region_id' => $region->id]);
        $user = User::factory()->create(['name' => 'SEC004-'.$suffix]);
        $assignment = $this->regionGrant($user, $region);
        $representative = InstitutionRepresentative::withoutEvents(fn () => InstitutionRepresentative::create(['user_id' => $user->id, 'institution_id' => $institution->id, 'starts_at' => now()->subDay(), 'verified_at' => now()->subDay()]));
        $audit = app(AuditRecorder::class)->record('SEC004-'.$suffix, 'success', $user, regionId: $region->id, institutionId: $institution->id);

        return compact('region', 'institution', 'user', 'assignment', 'representative', 'audit');
    }

    private function staff(): User
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole(AccountRole::Admin->value);

        return $user;
    }

    private function regionGrant(User $user, Region $region, array $overrides = []): UserRegionAssignment
    {
        return UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(array_merge([
            'user_id' => $user->id, 'region_id' => $region->id, 'starts_at' => now()->subDays(2),
        ], $overrides)));
    }
}
