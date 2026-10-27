<?php

namespace Tests\Feature;

use App\Filament\Pages\SyntheticApplicationIntake;
use App\Filament\Widgets\TaskOrientation;
use App\Filament\Widgets\WorkflowOverview;
use App\Models\User;
use App\Support\FoundationPermission;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

// Created for separately authorized PostgreSQL verification; not run by the
// database-free Phase 3 checks. Guard database identity before RefreshDatabase.
class DashboardGuidanceTest extends TestCase
{
    use RefreshDatabase {
        refreshDatabase as protected refreshApprovedDatabase;
    }

    public function refreshDatabase(): void
    {
        if (! app()->environment('testing')
            || config('database.default') !== 'pgsql'
            || DB::connection()->getDatabaseName() !== 'osds_test'
            || DB::selectOne('select current_database() as name')->name !== 'osds_test') {
            throw new \RuntimeException('Dashboard tests require explicitly authorized osds_test.');
        }

        $this->refreshApprovedDatabase();
    }

    private function staff(array $permissions): User
    {
        foreach (FoundationPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $user = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $user->givePermissionTo($permissions);
        $this->actingAs($user);

        return $user;
    }

    public function test_combined_stage_permissions_share_one_destination(): void
    {
        $this->staff(['platform.admin.access', 'applications.intake.view', 'applications.evaluation.record', 'applications.recommendations.record', 'applications.decisions.record']);
        $groups = app(TaskOrientation::class)->taskGroups();
        $this->assertCount(1, $groups);
        $this->assertCount(1, $groups['Applications and Reviews']);
        $this->assertCount(3, $groups['Applications and Reviews'][0]['responsibilities']);
        $this->assertSame(SyntheticApplicationIntake::getUrl(), $groups['Applications and Reviews'][0]['url']);
        Livewire::test(TaskOrientation::class)
            ->assertSee('Review the evaluation, recommend approval or return the case for reevaluation when the current stage permits it.')
            ->assertSee('technical evaluation')
            ->assertSee('final demonstration approval');
    }

    public function test_stage_permission_without_destination_access_does_not_expose_a_task(): void
    {
        $this->staff(['platform.admin.access', 'applications.decisions.record']);
        $this->assertSame([], app(TaskOrientation::class)->taskGroups());
        Livewire::test(TaskOrientation::class)->assertSee('No dashboard shortcuts available')->assertDontSee('Applications and decisions');
        $this->get(SyntheticApplicationIntake::getUrl())->assertForbidden();
    }

    public function test_revoked_permission_removes_the_task_and_inactive_users_cannot_view_widget(): void
    {
        $user = $this->staff(['platform.admin.access', 'applications.intake.view']);
        $this->assertNotEmpty(app(TaskOrientation::class)->taskGroups());
        $user->revokePermissionTo('applications.intake.view');
        $this->assertSame([], app(TaskOrientation::class)->taskGroups());
        $user->status = 'inactive';
        $this->assertFalse(TaskOrientation::canView());
    }

    public function test_configuration_view_only_does_not_invent_statistics_or_tasks(): void
    {
        $this->staff(['platform.admin.access', 'questionnaires.view']);
        $this->assertTrue(WorkflowOverview::canView());
        $widget = app(WorkflowOverview::class);
        $stats = new \ReflectionMethod($widget, 'getStats');
        $description = new \ReflectionMethod($widget, 'getDescription');
        $this->assertSame([], $stats->invoke($widget));
        $this->assertStringContainsString('No configuration statistics', $description->invoke($widget));
        $this->assertSame([], app(TaskOrientation::class)->taskGroups());
        Livewire::withoutLazyLoading()->test(WorkflowOverview::class)->assertSee('No configuration statistics');
    }
}
