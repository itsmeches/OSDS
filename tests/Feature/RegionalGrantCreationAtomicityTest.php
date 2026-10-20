<?php

namespace Tests\Feature;

use App\Filament\Resources\UserRegionAssignments\Pages\CreateUserRegionAssignment;
use App\Models\AuditEvent;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Auditing\AuditRecorder;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RegionalGrantCreationAtomicityTest extends TestCase
{
    use RefreshDatabase;

    public function test_actual_regional_grant_creation_rolls_back_when_mandatory_audit_fails(): void
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        $first = Region::create(['code' => 'G006-A', 'name' => 'Disposable first region']);
        $second = Region::create(['code' => 'G006-B', 'name' => 'Disposable second region']);
        $actor = User::factory()->create();
        $actor->assignRole(AccountRole::Admin->value);
        $target = User::factory()->create();
        $target->assignRole(AccountRole::Osds->value);
        UserRegionAssignment::withoutEvents(function () use ($actor, $target, $first, $second) {
            foreach ([[$actor, $first], [$actor, $second], [$target, $first]] as [$user, $region]) {
                UserRegionAssignment::create(['user_id' => $user->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]);
            }
        });
        $page = Livewire::actingAs($actor)->test(CreateUserRegionAssignment::class)
            ->fillForm(['user_id' => $target->id, 'region_id' => $second->id, 'starts_at' => now()->subHour()->toDateTimeString(), 'ends_at' => now()->addDay()->toDateTimeString()]);
        $before = AuditEvent::count();
        $this->assertFalse(UserRegionAssignment::where('user_id', $target->id)->where('region_id', $second->id)->exists());
        $audit = Mockery::mock(AuditRecorder::class);
        $audit->shouldReceive('record')->once()->withArgs(fn ($action, ...$rest) => $action === (new UserRegionAssignment)->getMorphClass().'.created')
            ->andThrow(new RuntimeException('Isolated regional creation audit fault'));
        $this->app->instance(AuditRecorder::class, $audit);
        $page->call('create')->assertHasFormErrors(['starts_at'])->assertNotified('Action could not be confirmed');
        $this->assertSame($before, AuditEvent::count());
        $this->assertFalse(UserRegionAssignment::where('user_id', $target->id)->where('region_id', $second->id)->exists(), 'A failed mandatory audit must roll back the regional authority grant.');
    }
}
