<?php

namespace App\Livewire\Applicant;

use App\Models\Application;
use App\Models\IssuanceDefinition;
use App\Models\IssuanceRequest;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Documents\CaseIssuanceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class IssuanceRequests extends Component
{
    #[Url]
    public ?string $application = null;

    #[Locked]
    public ?int $definitionId = null;

    #[Locked]
    public int $version = 0;

    #[Locked]
    public string $key = '';

    public array $answers = [];

    public string $feedback = '';

    public function mount(): void
    {
        $this->user();
        $this->key = (string) Str::uuid();
        if ($case = $this->case()) {
            $saved = IssuanceRequest::where('application_id', $case->id)->where('actor_id', auth()->id())->where('state', 'submitted')->first();
            $definition = IssuanceDefinition::where('module_id', $case->module_id)->where('state', 'published')->first();
            $saved ??= $definition ? IssuanceRequest::where('application_id', $case->id)->where('definition_id', $definition->id)->where('actor_id', auth()->id())->first() : null;
            $this->definitionId = $saved?->definition_id ?? $definition?->id;
            if ($saved) {
                $this->key = $saved->request_key;
                $this->version = $saved->lock_version;
                $this->answers = $saved->answers;
            }
            $form = $this->definitionId ? IssuanceDefinition::find($this->definitionId) : null;
            foreach ($form?->definition['questions'] ?? [] as $question) {
                if (! array_key_exists($question['key'], $this->answers)) {
                    $this->answers[$question['key']] = match ($question['type']) {
                        'multiple_choice' => [], 'declaration' => false, default => null,
                    };
                }
            }
        }
    }

    private function user(): User
    {
        $u = User::findOrFail(auth()->id());
        abort_unless(app(ApplicantSandboxGuard::class)->allowed() && $u->isActive() && $u->is_synthetic && $u->can('applicant.portal.access'), 403);

        return $u;
    }

    private function case(): ?Application
    {
        if (! $this->application) {
            return null;
        }
        $case = Application::where('public_id', $this->application)->firstOrFail();
        abort_unless(Gate::forUser($this->user())->allows('view', $case), 404);

        return $case;
    }

    public function save(bool $submit = false): void
    {
        $this->resetErrorBag();
        $this->feedback = '';
        try {
            $case = $this->case();
            abort_unless($case && $this->definitionId, 404);
            $saved = app(CaseIssuanceService::class)->persist($this->user(), $case, $this->definitionId, $this->version, $this->key, $this->answers, $submit);
            $this->version = $saved->lock_version;
            $this->answers = $saved->answers;
            $this->feedback = $saved->state === 'submitted' ? 'Demonstration request submitted. No certificate has been generated.' : 'Issuance draft saved.';
        } catch (ValidationException $e) {
            $this->setErrorBag($e->errors());
        } catch (AuthorizationException|HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            $reference = (string) Str::uuid();
            Log::error('Issuance request could not be confirmed', ['reference' => $reference, 'exception_class' => $e::class]);
            $this->addError('issuance', 'Result not confirmed. Keep your entries and inspect saved issuance requests before repeating. Support reference: '.$reference);
        }
    }

    public function render(): View
    {
        $user = $this->user();
        $case = $this->case();
        $definition = $this->definitionId ? IssuanceDefinition::find($this->definitionId) : null;
        $saved = $case ? IssuanceRequest::where('application_id', $case->id)->where('actor_id', $user->id)->where('definition_id', $this->definitionId)->first() : null;
        $identity = null;
        $reason = null;
        if ($case) {
            try {
                $identity = $saved?->state === 'submitted' ? $saved->identity_snapshot : app(CaseIssuanceService::class)->eligibility($case, $definition);
                if (! $user->can('issuance.requests.submit') && $saved?->state !== 'submitted') {
                    $reason = 'This account does not currently have issuance-request permission.';
                }
            } catch (ValidationException $e) {
                $reason = collect($e->errors())->flatten()->first();
            }
        }
        $cases = Application::with('module', 'institution')->where('applicant_id', $user->id)->latest('id')->get()->filter(fn ($a) => Gate::forUser($user)->allows('view', $a));

        return view('livewire.applicant.issuance-requests', compact('case', 'cases', 'definition', 'saved', 'identity', 'reason'))->layout('layouts.applicant', ['title' => 'Issuance requests']);
    }
}
