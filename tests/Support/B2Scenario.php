<?php

namespace Tests\Support;

use App\Models\DocumentaryRequirement;
use App\Models\EvidenceProviderType;
use App\Models\FormQuestion;
use App\Models\IssuanceDefinition;
use App\Services\Applications\ApplicationDecisionService;
use App\Services\Documents\CaseIssuanceService;
use App\Services\Questionnaires\QuestionnaireDraftService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use Illuminate\Support\Str;

class B2Scenario
{
    public static function create(string $module = 'registration', string $outcome = 'approved', bool $publish = true, bool $fieldEdges = false, int $evidenceMinimum = 0): array
    {
        $s = B1Scenario::create($module, true);
        if ($outcome !== 'screening') {
            foreach (['evaluator' => 'evaluated', 'chief' => 'recommended', 'director' => $outcome] as $role => $action) {
                $actor = $s['staff'][$role];
                auth()->login($actor);
                $case = $s['case']->fresh();
                app(ApplicationDecisionService::class)->act($actor, $case, $case->current_submission_id, $case->lock_version, $case->decisionStages()->max('id'), (string) Str::uuid(), $action, ['summary' => 'B2 synthetic prerequisite', 'attested' => true]);
            }
        }
        $manager = $s['author'];
        $manager->givePermissionTo(['issuance.definitions.manage', 'issuance.definitions.review', 'issuance.definitions.publish']);
        auth()->login($manager);
        foreach (IssuanceDefinition::where('module_id', $s['module']->id)->where('state', 'published')->get() as $old) {
            app(CaseIssuanceService::class)->transition($manager, $old, $old->lock_version, 'withdrawn');
        }
        $s['applicant']->givePermissionTo('issuance.requests.submit');
        auth()->login($manager);
        [$form,$requirements] = app(QuestionnaireDraftService::class)->createPair($manager, $s['module'], 'B2 supplementary '.$module);
        $section = $form->sections()->firstOrFail();
        FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'contact_note', 'type' => 'short_text', 'label' => 'Demonstration contact note', 'is_required' => true, 'sort_order' => 1, 'validation_rules' => []]);
        if ($fieldEdges) {
            $multi = FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'activities', 'type' => 'multiple_choice', 'label' => 'Optional activities', 'is_required' => false, 'sort_order' => 2, 'validation_rules' => []]);
            foreach (['track' => 'Track', 'ball' => 'Ball games'] as $key => $label) {
                $multi->options()->create(['key' => $key, 'label' => $label, 'sort_order' => $key === 'track' ? 1 : 2]);
            }
            FormQuestion::create(['form_version_id' => $form->id, 'form_section_id' => $section->id, 'key' => 'confirm', 'type' => 'declaration', 'label' => 'Optional declaration', 'is_required' => false, 'sort_order' => 3, 'validation_rules' => []]);
        }
        if ($evidenceMinimum > 0) {
            $requirement = DocumentaryRequirement::create(['requirement_set_version_id' => $requirements->id, 'key' => 'undertaking', 'label' => 'Approved undertaking evidence', 'is_required' => true, 'minimum_evidence' => $evidenceMinimum, 'maximum_evidence' => $evidenceMinimum, 'sort_order' => 1]);
            $requirement->providers()->attach(EvidenceProviderType::where('code', 'google_drive_file')->value('id'));
        }
        $publication = app(QuestionnairePublicationService::class);
        $review = $publication->submitForReview($manager, $form, $requirements);
        auth()->login($s['publisher']);
        $approval = $publication->review($s['publisher'], $review, true, 'B2 synthetic configuration review');
        $input = ['title' => 'B2 '.$module.' request', 'instructions' => 'Synthetic only. No certificate will be generated.', 'approval_id' => $approval->id, 'source_form_id' => $s['manifest']->form_version_id, 'name_question_id' => $s['question']->id, 'address_question_id' => $s['addressQuestion']->id];
        $definition = null;
        if ($publish) {
            auth()->login($manager);
            $service = app(CaseIssuanceService::class);
            $definition = $service->createDefinition($manager, $input);
            $definition = $service->transition($manager, $definition, $definition->lock_version, 'reviewed');
            $definition = $service->transition($manager, $definition, $definition->lock_version, 'published');
        }
        $s['case'] = $s['case']->fresh();

        return $s + compact('manager', 'form', 'requirements', 'approval', 'input', 'definition');
    }
}
