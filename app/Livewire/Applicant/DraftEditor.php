<?php

namespace App\Livewire\Applicant;

use App\Models\ApplicationCorrectionRequest;
use App\Models\ApplicationDraft;
use App\Models\ApplicationWorkingRevision;
use App\Models\DocumentaryRequirement;
use App\Models\EvidenceReference;
use App\Models\SyntheticEvidenceReviewCase;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use App\Services\Questionnaires\ConditionalLogic;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class DraftEditor extends Component
{
    public ApplicationDraft $draft;

    public array $answers = [];

    public int $lockVersion;

    public string $saveState = 'Saved';

    public array $missing = [];

    public int $section = 0;

    public array $evidenceInputs = [];

    public array $evidenceStates = [];

    public array $evidenceErrors = [];

    public array $documentMissing = [];

    public ?int $latestReviewCaseId = null;

    #[Locked]
    public string $reviewCopyKey;

    #[Locked]
    public string $submissionKey;

    #[Locked]
    public string $saveKey;

    #[Locked]
    public string $evidenceKey;

    public string $reviewCopyState = '';

    public function mount(ApplicationDraft $draft): void
    {
        $this->draft = $draft;
        $this->authorizeDraftAccess();
        $this->draft = $draft->load(['answers', 'evidenceReferences.latestRevision.provider', 'manifest.formVersion.sections.questions.options', 'manifest.requirementSetVersion.requirements.providers', 'manifest.retirement', 'institution', 'module']);
        $this->lockVersion = $draft->lock_version;
        $this->latestReviewCaseId = SyntheticEvidenceReviewCase::where('application_draft_id', $draft->id)->latest('id')->value('id');
        $this->reviewCopyKey = (string) Str::uuid();
        $this->submissionKey = (string) Str::uuid();
        $this->saveKey = (string) Str::uuid();
        $this->evidenceKey = (string) Str::uuid();
        foreach ($draft->answers as $a) {
            $this->answers[$a->form_question_id] = $a->value;
        }
        foreach ($draft->manifest->formVersion->sections->flatMap->questions as $question) {
            if ($question->type === 'multiple_choice' && ! array_key_exists($question->id, $this->answers)) {
                $this->answers[$question->id] = [];
            }
        }
    }

    public function hydrate(): void
    {
        // Livewire 4 calls this before property updates, actions, rendering, and dehydration.
        $this->authorizeDraftAccess();
    }

    private function authorizeDraftAccess(): void
    {
        $user = auth()->user()?->fresh();
        abort_unless($user && $user->can('view', $this->draft), 404);
    }

    public function updatedAnswers(): void
    {
        $this->saveState = 'Unsaved changes';
    }

    public function updatedEvidenceInputs(mixed $value, string $requirementId): void
    {
        unset($this->evidenceErrors[$requirementId]);
        $this->evidenceStates[$requirementId] = trim((string) $value) === '' ? 'Waiting for link' : 'Ready to add';
    }

    public function goToStep(int $step): void
    {
        $lastStep = $this->draft->manifest->formVersion->sections->count() + 1;
        $this->section = max(0, min($step, $lastStep));
    }

    public function save(ApplicantDraftService $service): void
    {
        $this->resetErrorBag('save');
        $this->saveState = 'Saving';
        $byKey = [];
        foreach ($this->draft->manifest->formVersion->sections->flatMap->questions as $q) {
            if (array_key_exists($q->id, $this->answers)) {
                $byKey[$q->key] = $this->answers[$q->id];
            }
        }

        try {
            // Unconfirmed retries of the same payload retain the same operation key.
            $key = (string) Uuid::uuid5($this->saveKey, hash('sha256', json_encode([$this->lockVersion, $byKey], JSON_THROW_ON_ERROR)));
            $this->lockVersion = $service->save(auth()->user(), $this->draft, $byKey, $this->lockVersion, $key);
            $this->saveState = 'Saved';
            $this->saveKey = (string) Str::uuid();
            $this->missing = [];
            $this->documentMissing = [];
            $this->resetErrorBag(['save', 'progress', 'submission']);
            $this->draft->refresh();
        } catch (ValidationException $e) {
            $this->saveState = $e->errors()['conflict'] ?? false ? 'Updated in another tab' : 'Save failed';
            $this->addError('save', collect($e->errors())->flatten()->first());
        } catch (AuthorizationException) {
            $this->saveState = 'Save failed';
            $this->addError('save', 'Your access has changed. Keep this page open and check your account before continuing.');
        } catch (Throwable $e) {
            $this->saveState = 'Save failed';
            $this->addError('save', $this->safeFailure($e));
        }
    }

    public function checkProgress(ApplicantDraftService $service): void
    {
        $this->resetErrorBag('progress');
        if ($this->isReadOnly() || $this->saveState !== 'Saved') {
            $this->addError('progress', $this->isReadOnly() ? 'This copy is read-only. Your saved information is preserved.' : 'Save your questionnaire answers before checking progress.');

            return;
        }
        $this->missing = $service->missing($this->draft->fresh());
        $this->documentMissing = app(EvidenceReferenceService::class)->missing($this->draft->fresh());
    }

    public function addEvidence(int $requirementId, EvidenceReferenceService $service): void
    {
        $this->mutateEvidence($requirementId, 'Link added', fn () => $service->add(auth()->user(), $this->draft, DocumentaryRequirement::findOrFail($requirementId), (string) ($this->evidenceInputs[$requirementId] ?? ''), $this->lockVersion, $this->evidenceOperationKey('add', (string) $requirementId)), true);
    }

    public function replaceEvidence(string $publicId, EvidenceReferenceService $service): void
    {
        $reference = EvidenceReference::where('public_id', $publicId)->firstOrFail();
        $rid = $reference->documentary_requirement_id;
        $this->mutateEvidence($rid, 'Link replaced', fn () => $service->replace(auth()->user(), $this->draft, $reference, (string) ($this->evidenceInputs[$rid] ?? ''), $this->lockVersion, $this->evidenceOperationKey('replace', $publicId, $rid)), true);
    }

    public function removeEvidence(string $publicId, EvidenceReferenceService $service): void
    {
        $reference = EvidenceReference::where('public_id', $publicId)->firstOrFail();
        $this->mutateEvidence($reference->documentary_requirement_id, 'Link removed', fn () => $service->remove(auth()->user(), $this->draft, $reference, $this->lockVersion, $this->evidenceOperationKey('remove', $publicId)));
    }

    private function mutateEvidence(int $requirementId, string $successState, callable $callback, bool $clearInput = false): void
    {
        $this->evidenceStates[$requirementId] = 'Saving link';
        unset($this->evidenceErrors[$requirementId]);
        if ($this->saveState !== 'Saved') {
            $this->evidenceStates[$requirementId] = 'Save answers first';
            $this->evidenceErrors[$requirementId] = 'Save your questionnaire answers first. Your pasted link is still here.';

            return;
        }
        try {
            $result = $callback();
            $this->lockVersion = $result['lock_version'];
            $this->evidenceKey = (string) Str::uuid();
            if ($clearInput) {
                unset($this->evidenceInputs[$requirementId]);
            }
            $this->evidenceStates[$requirementId] = $successState;
            $this->draft->refresh()->load(['evidenceReferences.latestRevision.provider']);
        } catch (ValidationException $e) {
            $conflict = isset($e->errors()['conflict']);
            $this->evidenceStates[$requirementId] = $conflict ? 'Updated in another tab' : 'Link not added';
            $this->evidenceErrors[$requirementId] = (string) collect($e->errors())->flatten()->first();
            if ($conflict) {
                $this->saveState = 'Updated in another tab';
            }
        } catch (AuthorizationException) {
            $this->evidenceStates[$requirementId] = 'Access changed';
            $this->evidenceErrors[$requirementId] = 'Your access to this draft has changed. Refresh the page before trying again.';
        } catch (Throwable $e) {
            $this->evidenceStates[$requirementId] = 'Save failed';
            $this->evidenceErrors[$requirementId] = $this->safeFailure($e);
        }
    }

    public function sendDocumentsForTestReview(SyntheticEvidenceReviewService $service): void
    {
        $this->resetErrorBag('review');
        if ($this->saveState !== 'Saved') {
            $this->addError('review', 'Save your questionnaire answers before sending a test-review copy.');

            return;
        }
        try {
            $case = $service->createCopy(auth()->user(), $this->draft, $this->lockVersion, $this->reviewCopyKey);
            $this->latestReviewCaseId = $case->id;
            $this->reviewCopyKey = (string) Str::uuid();
            $this->dispatch('review-copy-created');
            $this->reviewCopyState = 'Documents sent for separate test review. Your application has not been submitted.';
        } catch (ValidationException $e) {
            $this->addError('review', collect($e->errors())->flatten()->first());
        } catch (AuthorizationException) {
            $this->addError('review', 'Your access has changed. Check your account before continuing.');
        } catch (Throwable $e) {
            $this->addError('review', $this->safeFailure($e));
        }
    }

    public function supersedeTestReview(SyntheticEvidenceReviewService $service): void
    {
        $this->resetErrorBag('review');
        if ($this->saveState !== 'Saved') {
            $this->addError('review', 'Save your questionnaire answers before sending a newer copy.');

            return;
        }
        $prior = SyntheticEvidenceReviewCase::where('application_draft_id', $this->draft->id)->latest('id')->firstOrFail();
        try {
            $case = $service->createCopy(auth()->user(), $this->draft, $this->lockVersion, $this->reviewCopyKey, $prior);
            $this->latestReviewCaseId = $case->id;
            $this->reviewCopyKey = (string) Str::uuid();
            $this->reviewCopyState = 'A newer document test-review copy has been sent. Earlier copies are preserved.';
        } catch (ValidationException $e) {
            $this->addError('review', collect($e->errors())->flatten()->first());
        } catch (AuthorizationException) {
            $this->addError('review', 'Your access has changed. Check your account before continuing.');
        } catch (Throwable $e) {
            $this->addError('review', $this->safeFailure($e));
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

    public function submitTestApplication(
        ApplicationLifecycleService $service,
        ApplicantDraftService $draftService,
        EvidenceReferenceService $evidenceService,
    ): void {
        $this->resetErrorBag(['submission', 'progress']);
        if ($this->saveState !== 'Saved') {
            $this->addError('submission', 'Save your questionnaire answers before submitting. Your changes are still here.');

            return;
        }
        $latestDraft = $this->draft->fresh();
        $this->missing = $draftService->missing($latestDraft);
        $this->documentMissing = $evidenceService->missing($latestDraft);

        if ($this->missing !== [] || $this->documentMissing !== []) {
            $this->section = $this->missing !== []
                ? 0
                : $this->draft->manifest->formVersion->sections->count();
            $this->addError('progress', 'Your test application is still incomplete. Complete the listed items, then check progress again.');

            return;
        }

        try {
            $application = $this->draft->application()->firstOrFail();
            $service->submit(auth()->user(), $application, $application->lock_version, $this->lockVersion, $this->submissionKey);
            $this->redirect(route('applicant.applications.show', $application));
        } catch (ValidationException $e) {
            $this->addError('submission', collect($e->errors())->flatten()->first());
        } catch (AuthorizationException) {
            $this->addError('submission', 'Your access has changed. Check your account before trying again.');
        } catch (Throwable $e) {
            $this->addError('submission', $this->safeFailure($e));
        }
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
        return (bool) $this->draft->manifest->retirement || $this->draft->manifest->effective_until?->isPast() || ! ApplicationWorkingRevision::where(['application_draft_id' => $this->draft->id, 'state' => 'open'])->exists();
    }

    private function evidenceOperationKey(string $operation, string $target, ?int $requirementId = null): string
    {
        return (string) Uuid::uuid5($this->evidenceKey, hash('sha256', json_encode([$operation, $target, $this->lockVersion, $requirementId ? ($this->evidenceInputs[$requirementId] ?? '') : ($this->evidenceInputs[$target] ?? '')], JSON_THROW_ON_ERROR)));
    }

    private function safeFailure(Throwable $error): string
    {
        if ($error instanceof HttpExceptionInterface) {
            throw $error;
        }
        $reference = (string) Str::uuid();
        // Deliberately omit exception messages, SQL bindings, answers and evidence inputs.
        Log::error('Applicant operation failed', ['reference' => $reference, 'exception_type' => $error::class]);

        return 'We could not confirm this action. Your input is still here. Retry the same action, or contact support with reference '.$reference.'.';
    }

    public function render()
    {
        $this->authorizeDraftAccess();

        $correctionId = ApplicationWorkingRevision::where('application_draft_id', $this->draft->id)->value('correction_request_id');
        $correction = $correctionId ? ApplicationCorrectionRequest::with('items')->find($correctionId) : null;

        return view('livewire.applicant.draft-editor', compact('correction'))->layout('layouts.applicant');
    }
}
