<?php

namespace App\Filament\Pages;

use App\Models\ConfigurationReviewApproval;
use App\Models\FormVersion;
use App\Models\IssuanceDefinition;
use App\Models\OsdsModule;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Documents\CaseIssuanceService;
use App\Services\Questionnaires\QuestionnaireDraftService;
use App\Support\StaffActionFeedback;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Livewire\WithPagination;

class IssuanceDefinitions extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Issuance request definitions';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Demonstration Documents';

    use WithPagination;

    protected static ?string $title = 'Issuance request definitions';

    protected string $view = 'filament.pages.issuance-definitions';

    public string $titleInput = '';

    public string $instructions = '';

    public ?int $approvalId = null;

    public ?int $sourceFormId = null;

    public ?int $nameQuestionId = null;

    public ?int $addressQuestionId = null;

    public ?int $moduleId = null;

    public ?int $previewId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->isActive() && auth()->user()?->is_synthetic && app(ApplicantSandboxGuard::class)->allowed() && collect(['manage', 'review', 'publish'])->contains(fn ($p) => auth()->user()->can('issuance.definitions.'.$p));
    }

    public function updatedSourceFormId(): void
    {
        $this->nameQuestionId = $this->addressQuestionId = null;
    }

    public function createPair(): void
    {
        StaffActionFeedback::run(function () {
            app(CaseIssuanceService::class)->actor(auth()->user(), 'issuance.definitions.manage');
            validator(['module' => $this->moduleId, 'title' => $this->titleInput], ['module' => 'required|integer', 'title' => 'required|string|max:160'])->validate();
            $module = OsdsModule::whereIn('code', ['registration', 'acsa'])->findOrFail($this->moduleId);
            [$form,$requirements] = app(QuestionnaireDraftService::class)->createPair(auth()->user(), $module, $this->titleInput);
            $this->redirect(FormBuilder::getUrl(['form' => $form->id, 'requirements' => $requirements->id]));
        });
    }

    public function createDefinition(): void
    {
        try {
            StaffActionFeedback::run(fn () => app(CaseIssuanceService::class)->createDefinition(auth()->user(), ['title' => $this->titleInput, 'instructions' => $this->instructions, 'approval_id' => $this->approvalId, 'source_form_id' => $this->sourceFormId, 'name_question_id' => $this->nameQuestionId, 'address_question_id' => $this->addressQuestionId]));
        } catch (ValidationException $e) {
            $this->setErrorBag($e->errors());
        }
    }

    public function advanceDefinition(int $id, int $version, string $to): void
    {
        try {
            StaffActionFeedback::run(fn () => app(CaseIssuanceService::class)->transition(auth()->user(), IssuanceDefinition::findOrFail($id), $version, $to));
        } catch (ValidationException $e) {
            $this->setErrorBag($e->errors());
        }
    }

    protected function getViewData(): array
    {
        return ['definitions' => IssuanceDefinition::latest('id')->paginate(10), 'approvals' => ConfigurationReviewApproval::with('request.module', 'request.formVersion.definition')->where('outcome', 'approved')->latest('id')->get(), 'sources' => FormVersion::with('definition.module')->whereIn('state', ['published', 'in_review'])->get(), 'bindings' => $this->sourceFormId ? FormVersion::find($this->sourceFormId)?->questions()->whereIn('type', ['short_text', 'long_text'])->get() : collect(), 'modules' => OsdsModule::whereIn('code', ['registration', 'acsa'])->get(), 'preview' => $this->previewId ? IssuanceDefinition::find($this->previewId) : null];
    }
}
