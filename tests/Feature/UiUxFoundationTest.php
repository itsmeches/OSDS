<?php

namespace Tests\Feature;

use App\Filament\Pages\Questionnaires;
use App\Filament\Resources\ConfigurationReviewRequests\ConfigurationReviewRequestResource;
use App\Filament\Resources\ConfigurationReviewRequests\Pages\ViewConfigurationReviewRequest;
use App\Filament\Resources\FormVersions\FormVersionResource;
use App\Filament\Resources\RequirementSetVersions\RequirementSetVersionResource;
use App\Models\DocumentaryRequirement;
use App\Models\EvidenceProviderType;
use App\Models\FormDefinition;
use App\Models\FormQuestion;
use App\Models\FormSection;
use App\Models\FormVersion;
use App\Models\OsdsModule;
use App\Models\RequirementSetVersion;
use App\Models\User;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class UiUxFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
    }

    public function test_osds_can_open_task_based_questionnaire_workspace(): void
    {
        $osds = $this->user(AccountRole::Osds, 'OSDS Author');
        $module = OsdsModule::query()->where('code', 'registration')->firstOrFail();
        [$form, $requirements] = $this->configuration($osds, 'workspace');

        $this->actingAs($osds)
            ->get(Questionnaires::getUrl(['module' => $module->code]))
            ->assertOk()
            ->assertSee('Continue editing')
            ->assertSee('Required documents')
            ->assertSee('Version history and technical details')
            ->assertSee('Form Builder');

        $this->get(FormVersionResource::getUrl('edit', ['record' => $form]))->assertOk()->assertSee('Save draft');
        $this->get(RequirementSetVersionResource::getUrl('edit', ['record' => $requirements]))->assertOk()->assertSee('Save draft');
    }

    public function test_staff_panel_uses_the_light_first_responsive_foundation(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasDarkMode());
        $this->assertTrue($panel->hasThemeSwitcher());
        $this->assertSame(ThemeMode::System, $panel->getDefaultThemeMode());
        $this->assertSame('17rem', $panel->getSidebarWidth());
        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
    }

    public function test_authorized_osds_can_create_initial_drafts_under_an_existing_module(): void
    {
        $osds = $this->user(AccountRole::Osds, 'OSDS Creator');
        $module = OsdsModule::query()->where('code', 'registration')->firstOrFail();

        Livewire::actingAs($osds)
            ->test(Questionnaires::class)
            ->callAction('createQuestionnaire', data: [
                'module_id' => $module->id,
                'title' => 'New synthetic questionnaire',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('form_definitions', [
            'module_id' => $module->id,
            'name' => 'New synthetic questionnaire',
        ]);
        $this->assertDatabaseHas('form_versions', ['state' => 'draft']);
        $this->assertDatabaseHas('requirement_set_versions', [
            'module_id' => $module->id,
            'state' => 'draft',
        ]);
    }

    public function test_admin_and_unauthorized_roles_cannot_open_osds_authoring_workspace(): void
    {
        $this->actingAs($this->user(AccountRole::Admin, 'Admin Reviewer'))
            ->get(Questionnaires::getUrl())
            ->assertForbidden();

        $this->actingAs($this->user(AccountRole::Chedro, 'CHEDRO User'))
            ->get(Questionnaires::getUrl())
            ->assertForbidden();

        $this->actingAs($this->user(AccountRole::School, 'School User'))
            ->get(Questionnaires::getUrl())
            ->assertForbidden();
    }

    public function test_admin_can_inspect_complete_pending_configuration_before_decision(): void
    {
        $osds = $this->user(AccountRole::Osds, 'OSDS Author');
        $admin = $this->user(AccountRole::Admin, 'Admin Reviewer');
        [$form, $requirements] = $this->configuration($osds, 'inspect');
        $review = app(QuestionnairePublicationService::class)->submitForReview($osds, $form, $requirements);

        $this->actingAs($admin)
            ->get(ConfigurationReviewRequestResource::getUrl('view', ['record' => $review]))
            ->assertOk()
            ->assertSee('Inspect the complete questionnaire')
            ->assertSee('Organization name')
            ->assertSee('Certificate of undertaking')
            ->assertSee('Configuration reference')
            ->assertSee('What happens next?');
    }

    public function test_admin_can_publish_approved_review_through_filament_action(): void
    {
        $osds = $this->user(AccountRole::Osds, 'OSDS Author');
        $admin = $this->user(AccountRole::Admin, 'Admin Reviewer');
        [$form, $requirements] = $this->configuration($osds, 'publish-ui');
        $service = app(QuestionnairePublicationService::class);
        $review = $service->submitForReview($osds, $form, $requirements);
        $service->review($admin, $review, true, 'UI-PUBLISH');

        Livewire::actingAs($admin)
            ->test(ViewConfigurationReviewRequest::class, ['record' => $review->id])
            ->callAction('publish', data: ['effective_from' => now()->format('Y-m-d H:i:s'), 'effective_until' => null])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('publication_manifests', [
            'configuration_review_approval_id' => $review->approvals()->where('outcome', 'approved')->value('id'),
            'channel' => 'review_sandbox',
        ]);
    }

    public function test_osds_review_history_is_limited_to_own_submissions(): void
    {
        $first = $this->user(AccountRole::Osds, 'First OSDS Author');
        $second = $this->user(AccountRole::Osds, 'Second OSDS Author');
        [$firstForm, $firstRequirements] = $this->configuration($first, 'first');
        [$secondForm, $secondRequirements] = $this->configuration($second, 'second');
        $service = app(QuestionnairePublicationService::class);
        $service->submitForReview($first, $firstForm, $firstRequirements);
        $service->submitForReview($second, $secondForm, $secondRequirements);

        $this->actingAs($first)
            ->get(ConfigurationReviewRequestResource::getUrl())
            ->assertOk()
            ->assertSee('First OSDS Author')
            ->assertDontSee('Second OSDS Author');
    }

    public function test_school_cannot_access_technical_questionnaire_routes_or_review_details(): void
    {
        $osds = $this->user(AccountRole::Osds, 'OSDS Author');
        [$form, $requirements] = $this->configuration($osds, 'denied');
        $review = app(QuestionnairePublicationService::class)->submitForReview($osds, $form, $requirements);
        $school = $this->user(AccountRole::School, 'School User');

        $this->actingAs($school)->get(FormVersionResource::getUrl())->assertForbidden();
        $this->actingAs($school)->get(ConfigurationReviewRequestResource::getUrl('view', ['record' => $review]))->assertForbidden();
    }

    private function configuration(User $owner, string $suffix): array
    {
        $module = OsdsModule::query()->where('code', 'registration')->firstOrFail();
        $definition = FormDefinition::query()->create([
            'module_id' => $module->id,
            'key' => "ux_{$suffix}_".Str::lower(Str::random(5)),
            'name' => "Synthetic {$suffix} questionnaire",
            'owner_id' => $owner->id,
        ]);
        $form = FormVersion::query()->create(['form_definition_id' => $definition->id, 'version_number' => 1, 'state' => 'draft', 'created_by' => $owner->id]);
        $section = FormSection::query()->create(['form_version_id' => $form->id, 'key' => 'organization', 'title' => 'Organization details', 'sort_order' => 1]);
        FormQuestion::query()->create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'organization_name', 'type' => 'short_text', 'label' => 'Organization name', 'is_required' => true, 'sort_order' => 1, 'validation_rules' => []]);

        $requirements = RequirementSetVersion::query()->create(['module_id' => $module->id, 'version_number' => RequirementSetVersion::query()->where('module_id', $module->id)->max('version_number') + 1, 'state' => 'draft', 'created_by' => $owner->id]);
        $requirement = DocumentaryRequirement::query()->create(['requirement_set_version_id' => $requirements->id, 'key' => 'undertaking', 'label' => 'Certificate of undertaking', 'is_required' => true, 'minimum_evidence' => 1, 'maximum_evidence' => 1, 'sort_order' => 1]);
        $requirement->providers()->attach(EvidenceProviderType::query()->where('code', 'google_drive_file')->value('id'));

        return [$form, $requirements];
    }

    private function user(AccountRole $role, string $name): User
    {
        $user = User::factory()->create(['name' => $name, 'status' => 'active']);
        $user->assignRole($role->value);

        return $user;
    }
}
