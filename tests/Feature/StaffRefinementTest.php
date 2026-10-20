<?php

namespace Tests\Feature;

use App\Filament\Resources\ConfigurationReviewRequests\ConfigurationReviewRequestResource;
use App\Filament\Resources\ConfigurationReviewRequests\Pages\ViewConfigurationReviewRequest;
use App\Filament\Resources\InstitutionRepresentatives\Pages\EditInstitutionRepresentative;
use App\Filament\Resources\InstitutionRepresentatives\Pages\ListInstitutionRepresentatives;
use App\Filament\Resources\PublicationManifests\Pages\ListPublicationManifests;
use App\Filament\Resources\Users\UserResource;
use App\Models\AuditEvent;
use App\Models\ConfigurationReviewApproval;
use App\Models\EvidenceProviderType;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\OsdsModule;
use App\Models\PublicationManifest;
use App\Models\Region;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Identity\AccountGovernance;
use App\Services\Questionnaires\QuestionnaireAuthoringService;
use App\Services\Questionnaires\QuestionnaireDraftService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class StaffRefinementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.debug' => false]);
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
    }

    private function user(AccountRole $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function candidate(): array
    {
        $author = $this->user(AccountRole::Osds);
        $admin = $this->user(AccountRole::Admin);
        $module = OsdsModule::where('code', 'registration')->firstOrFail();
        [$form, $requirements] = app(QuestionnaireDraftService::class)->createPair($author, $module, 'Isolated staff review');
        app(QuestionnaireAuthoringService::class)->save($author, $form, $requirements, [
            'title' => 'Isolated staff review',
            'sections' => [['key' => 'main', 'title' => 'Applicant details', 'description' => '', 'questions' => [
                ['key' => 'name', 'label' => 'Original candidate question', 'help_text' => '', 'type' => 'short_text', 'is_required' => false, 'options' => [], 'validation_rules' => [], 'condition_rules' => null],
            ]]],
            'requirements' => [['key' => 'support', 'label' => 'Supporting document', 'instructions' => '', 'is_required' => false, 'minimum_evidence' => 0, 'maximum_evidence' => 1, 'provider_ids' => [EvidenceProviderType::where('code', 'google_docs')->value('id')], 'condition_rules' => null]],
        ], 0, 0);
        $review = app(QuestionnairePublicationService::class)->submitForReview($author, $form->fresh(), $requirements->fresh());

        return [$admin, $review, $form, $requirements];
    }

    public function test_real_review_publish_filter_and_retire_actions_preserve_exact_pair(): void
    {
        [$admin, $review, $form, $requirements] = $this->candidate();
        Livewire::actingAs($admin)->test(ViewConfigurationReviewRequest::class, ['record' => $review->id])
            ->callAction('approve', data: ['reference' => 'SYNTHETIC-STAFF-REVIEW'])->assertHasNoActionErrors();
        $this->assertSame('approved', $review->fresh()->state);
        $this->assertSame(1, ConfigurationReviewApproval::count());
        Livewire::actingAs($admin)->test(ViewConfigurationReviewRequest::class, ['record' => $review->id])
            ->callAction('publishApplicantSandbox', data: ['effective_from' => now()->subMinute()->toDateTimeString()])->assertHasNoActionErrors();
        $applicant = PublicationManifest::where('channel', 'applicant_sandbox')->sole();
        $this->assertSame($form->id, $applicant->form_version_id);
        $this->assertSame($requirements->id, $applicant->requirement_set_version_id);
        Livewire::actingAs($admin)->test(ListPublicationManifests::class)
            ->filterTable('channel', 'applicant_sandbox')->assertCanSeeTableRecords([$applicant])
            ->assertTableActionHidden('preview', $applicant)
            ->callTableAction('retire', $applicant, data: ['reason' => 'Isolated retirement verification'])->assertHasNoTableActionErrors()->assertSee('Retired');
        $this->assertNotNull($applicant->fresh()->retirement);
        $this->assertSame($review->combined_hash, $applicant->fresh()->combined_hash);
        // Existing applicant republication remains supported after retirement;
        // hiding duplicate controls must not hide this legitimate next action.
        Livewire::actingAs($admin)->test(ViewConfigurationReviewRequest::class, ['record' => $review->id])
            ->assertActionVisible('publishApplicantSandbox')
            ->callAction('publishApplicantSandbox', data: ['effective_from' => now()->toDateTimeString()])->assertHasNoActionErrors();
        $this->assertSame(2, PublicationManifest::count());
        $this->assertNotNull($applicant->fresh()->retirement);
    }

    public function test_publication_does_not_redirect_to_a_list_without_its_separate_permission(): void
    {
        [$admin, $review] = $this->candidate();
        app(QuestionnairePublicationService::class)->review($admin, $review, true, 'APPROVED');
        $publisher = User::factory()->create();
        $publisher->givePermissionTo(['platform.admin.access', 'questionnaires.review', 'applicant.sandbox.publish']);
        $this->assertFalse($publisher->can('questionnaires.publication.view'));
        Livewire::actingAs($publisher)->test(ViewConfigurationReviewRequest::class, ['record' => $review->id])
            ->callAction('publishApplicantSandbox', data: ['effective_from' => now()->toDateTimeString()])
            ->assertHasNoActionErrors()
            ->assertRedirect(ConfigurationReviewRequestResource::getUrl('view', ['record' => $review]));
        $this->assertSame(1, PublicationManifest::count());
    }

    public function test_stale_candidate_cannot_be_approved_and_returned_review_does_not_display_later_draft_as_original(): void
    {
        [$admin, $review, $form] = $this->candidate();
        $page = Livewire::actingAs($admin)->test(ViewConfigurationReviewRequest::class, ['record' => $review->id]);
        DB::table('form_questions')->where('form_version_id', $form->id)->update(['label' => 'Changed candidate fixture']);
        $page->callAction('approve', data: ['reference' => 'STALE'])->assertHasActionErrors();
        $this->assertSame('pending', $review->fresh()->state);
        $this->assertSame(0, ConfigurationReviewApproval::count());
        DB::table('form_questions')->where('form_version_id', $form->id)->update(['label' => 'Original candidate question']);
        app(QuestionnairePublicationService::class)->review($admin, $review, false, 'RETURNED', 'Clarify the instructions.');
        $form->questions()->firstOrFail()->update(['label' => 'Later mutable draft marker']);
        Livewire::actingAs($admin)->test(ViewConfigurationReviewRequest::class, ['record' => $review->id])
            ->assertSee('current draft content is not an unchanged copy')->assertSee('Clarify the instructions.')
            ->assertDontSee('Later mutable draft marker');
    }

    public function test_unexpected_publication_failure_is_redacted_in_real_livewire_feedback(): void
    {
        [$admin, $review] = $this->candidate();
        app(QuestionnairePublicationService::class)->review($admin, $review, true, 'APPROVED');
        $this->mock(QuestionnairePublicationService::class)->shouldReceive('publishApplicantSandbox')->once()->andThrow(new RuntimeException('SYNTHETIC-PRIVATE-FAILURE-MARKER'));
        Log::shouldReceive('error')->once()->withArgs(fn ($message, $metadata): bool => $message === 'Staff action could not be confirmed'
            && array_keys($metadata) === ['support_reference', 'exception_class'] && $metadata['exception_class'] === RuntimeException::class);
        $availableFrom = now()->toDateTimeString();
        $page = Livewire::actingAs($admin)->test(ViewConfigurationReviewRequest::class, ['record' => $review->id])
            ->callAction('publishApplicantSandbox', data: ['effective_from' => $availableFrom])
            ->assertNotified('Action could not be confirmed')
            ->assertActionDataSet(['effective_from' => $availableFrom]);
        $this->assertStringNotContainsString('SYNTHETIC-PRIVATE-FAILURE-MARKER', json_encode($page->effects));
        $this->assertSame(0, PublicationManifest::count());
    }

    public function test_regional_verifier_uses_existing_representation_action_without_broadening_user_list(): void
    {
        $region = Region::create(['code' => 'F-VERIFY', 'name' => 'Isolated verification region']);
        $creator = $this->user(AccountRole::Chedro);
        $verifier = $this->user(AccountRole::Chedro);
        foreach ([$creator, $verifier] as $staff) {
            UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $staff->id, 'region_id' => $region->id, 'starts_at' => now()->subDay()]));
        }
        $organization = Institution::create(['name' => 'Isolated SCHOOL organization', 'primary_region_id' => $region->id, 'is_synthetic' => true]);
        $this->actingAs($creator);
        $school = app(AccountGovernance::class)->create($creator, AccountRole::School, ['name' => 'Isolated SCHOOL account', 'email' => 'school-f@example.invalid', 'password' => 'Disposable-test-only!', 'is_synthetic' => true], institutionId: $organization->id);
        $grant = InstitutionRepresentative::where('user_id', $school->id)->sole();
        $this->actingAs($verifier);
        $this->assertFalse(UserResource::getEloquentQuery()->whereKey($school->id)->exists());
        Livewire::actingAs($creator)->test(ListInstitutionRepresentatives::class)->assertTableActionHidden('verify_school', $grant);
        $beforeAudit = AuditEvent::count();
        Livewire::actingAs($creator)->test(ListInstitutionRepresentatives::class)->update(
            calls: [['method' => 'callMountedAction', 'params' => [], 'path' => '']],
            updates: ['mountedActions' => [['name' => 'verify_school', 'arguments' => [], 'context' => ['table' => true, 'recordKey' => (string) $grant->id]]]],
        );
        $this->assertSame('pending_verification', $school->fresh()->status);
        $this->assertNull($grant->fresh()->verified_at);
        $this->assertSame($beforeAudit, AuditEvent::count());
        Livewire::actingAs($verifier)->test(ListInstitutionRepresentatives::class)
            ->callTableAction('verify_school', $grant)->assertHasNoTableActionErrors();
        $this->assertSame('active', $school->fresh()->status);
        $this->assertSame($verifier->id, $grant->fresh()->verified_by);
        Livewire::actingAs($verifier)->test(EditInstitutionRepresentative::class, ['record' => $grant->id])
            ->assertFormFieldExists('user_id', fn ($field): bool => $field->getOptionLabel() === $school->name);
    }
}
