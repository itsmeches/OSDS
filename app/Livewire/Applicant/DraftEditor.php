<?php

namespace App\Livewire\Applicant;

use App\Models\ApplicationDraft;
use App\Models\DocumentaryRequirement;
use App\Models\EvidenceReference;
use App\Models\SyntheticEvidenceReviewCase;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use App\Services\Questionnaires\ConditionalLogic;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class DraftEditor extends Component
{
    public ApplicationDraft $draft;

    public array $answers = [];

    public int $lockVersion;

    public string $saveState = 'Saved';

    public array $missing = [];

    public int $section = 0;

    public array $evidenceInputs = [];

    public array $documentMissing = [];

    public ?int $latestReviewCaseId = null;

    public string $reviewCopyKey;

    public function mount(ApplicationDraft $draft): void
    {
        abort_unless(auth()->user()->can('view', $draft), 404);
        $this->draft = $draft->load(['answers', 'evidenceReferences.latestRevision.provider', 'manifest.formVersion.sections.questions.options', 'manifest.requirementSetVersion.requirements.providers', 'manifest.retirement', 'institution', 'module']);
        $this->lockVersion = $draft->lock_version;
        $this->latestReviewCaseId = SyntheticEvidenceReviewCase::where('application_draft_id', $draft->id)->latest('id')->value('id');
        $this->reviewCopyKey = (string) Str::uuid();
        foreach ($draft->answers as $a) {
            $this->answers[$a->form_question_id] = $a->value;
        }
    }

    public function updatedAnswers(): void
    {
        $this->saveState = 'Unsaved changes';
    }

    public function save(ApplicantDraftService $service): void
    {
        $this->saveState = 'Saving';
        $byKey = [];
        foreach ($this->draft->manifest->formVersion->sections->flatMap->questions as $q) {
            if (array_key_exists($q->id, $this->answers)) {
                $byKey[$q->key] = $this->answers[$q->id];
            }
        }try {
            $this->lockVersion = $service->save(auth()->user(), $this->draft, $byKey, $this->lockVersion, (string) Str::uuid());
            $this->saveState = 'Saved';
            $this->draft->refresh();
        } catch (ValidationException $e) {
            $this->saveState = $e->errors()['conflict'] ?? false ? 'Updated in another tab' : 'Save failed';
            $this->addError('save', collect($e->errors())->flatten()->first());
        }
    }

    public function checkProgress(ApplicantDraftService $service): void
    {
        $this->missing = $service->missing($this->draft->fresh());
        $this->documentMissing = app(EvidenceReferenceService::class)->missing($this->draft->fresh());
    }

    public function addEvidence(int $requirementId, EvidenceReferenceService $service): void
    {
        $this->mutateEvidence(fn () => $service->add(auth()->user(), $this->draft, DocumentaryRequirement::findOrFail($requirementId), (string) ($this->evidenceInputs[$requirementId] ?? ''), $this->lockVersion, (string) Str::uuid()), $requirementId);
    }

    public function replaceEvidence(string $publicId, EvidenceReferenceService $service): void
    {
        $reference = EvidenceReference::where('public_id', $publicId)->firstOrFail();
        $rid = $reference->documentary_requirement_id;
        $this->mutateEvidence(fn () => $service->replace(auth()->user(), $this->draft, $reference, (string) ($this->evidenceInputs[$rid] ?? ''), $this->lockVersion, (string) Str::uuid()), $rid);
    }

    public function removeEvidence(string $publicId, EvidenceReferenceService $service): void
    {
        $reference = EvidenceReference::where('public_id', $publicId)->firstOrFail();
        $this->mutateEvidence(fn () => $service->remove(auth()->user(), $this->draft, $reference, $this->lockVersion, (string) Str::uuid()), null);
    }

    private function mutateEvidence(callable $callback, ?int $clear): void
    {
        $this->saveState = 'Saving';
        try {
            $result = $callback();
            $this->lockVersion = $result['lock_version'];
            if ($clear) {
                unset($this->evidenceInputs[$clear]);
            }$this->saveState = 'Saved';
            $this->draft->refresh()->load(['evidenceReferences.latestRevision.provider']);
            $this->resetErrorBag('evidence');
        } catch (ValidationException $e) {
            $this->saveState = isset($e->errors()['conflict']) ? 'Updated in another tab' : 'Save failed';
            $this->addError('evidence', collect($e->errors())->flatten()->first());
        }
    }

    public function sendDocumentsForTestReview(SyntheticEvidenceReviewService $service): void
    {
        try {
            $case = $service->createCopy(auth()->user(), $this->draft, $this->lockVersion, $this->reviewCopyKey);
            $this->latestReviewCaseId = $case->id;
            $this->reviewCopyKey = (string) Str::uuid();
            $this->dispatch('review-copy-created');
        } catch (ValidationException $e) {
            $this->addError('review', collect($e->errors())->flatten()->first());
        }
    }

    public function supersedeTestReview(SyntheticEvidenceReviewService $service): void
    {
        $prior = SyntheticEvidenceReviewCase::where('application_draft_id', $this->draft->id)->latest('id')->firstOrFail();
        try {
            $case = $service->createCopy(auth()->user(), $this->draft, $this->lockVersion, $this->reviewCopyKey, $prior);
            $this->latestReviewCaseId = $case->id;
            $this->reviewCopyKey = (string) Str::uuid();
        } catch (ValidationException $e) {
            $this->addError('review', collect($e->errors())->flatten()->first());
        }
    }

    public function evidenceFor(int $requirementId): array
    {
        return app(EvidenceReferenceService::class)->activeReferences($this->draft->id, $requirementId)->all();
    }

    public function requirementApplicable($requirement): bool
    {
        return app(EvidenceReferenceService::class)->applicable($this->draft, $requirement);
    }

    public function visible($question): bool
    {
        $prior = [];
        foreach ($this->draft->manifest->formVersion->sections->flatMap->questions as $candidate) {
            if ($candidate->id === $question->id) {
                break;
            }
            $prior[$candidate->key] = $this->answers[$candidate->id] ?? null;
        }

        return app(ConditionalLogic::class)->evaluate($question->condition_rules, $prior);
    }

    public function isReadOnly(): bool
    {
        return (bool) $this->draft->manifest->retirement || $this->draft->manifest->effective_until?->isPast();
    }

    public function render()
    {
        return view('livewire.applicant.draft-editor')->layout('layouts.applicant');
    }
}
