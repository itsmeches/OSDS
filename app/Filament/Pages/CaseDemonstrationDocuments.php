<?php

namespace App\Filament\Pages;

use App\Models\DemonstrationDocument;
use App\Models\DemonstrationDocumentVersion;
use App\Models\DemoOfficeDirectoryRevision;
use App\Models\IssuanceRequest;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Documents\DemonstrationDocumentService;
use App\Support\StaffActionFeedback;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class CaseDemonstrationDocuments extends Page
{
    use WithFileUploads, WithPagination;

    protected static ?string $title = 'Case demonstration PDFs';

    protected string $view = 'filament.pages.case-demonstration-documents';

    public ?int $moduleId = null;

    public string $wording = DemonstrationDocumentService::WORDING;

    public string $fields = '';

    public ?int $regionId = null;

    public ?int $directoryId = null;

    public string $officeKey = '';

    public string $provenance = '';

    public array $templateIds = [];

    public array $personnelIds = [];

    public $backup = null;

    public static function canAccess(): bool
    {
        $u = auth()->user();

        return $u?->isActive() && $u->is_synthetic && app(ApplicantSandboxGuard::class)->allowed() && $u->can('platform.admin.access') && collect(['documents.view', 'documents.templates.manage', 'documents.templates.review', 'documents.templates.publish', 'documents.personnel.manage', 'documents.personnel.review', 'documents.personnel.publish'])->contains(fn ($p) => $u->can($p));
    }

    private function perform(callable $action): void
    {
        try {
            StaffActionFeedback::run($action);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->errors());
        }
    }

    public function createTemplate(): void
    {
        $this->perform(fn () => app(DemonstrationDocumentService::class)->createVersion(auth()->user(), 'template', (int) $this->moduleId, ['wording' => $this->wording, 'fields' => array_values(array_filter(array_map('trim', explode(',', $this->fields))))]));
    }

    public function createPersonnel(): void
    {
        $this->perform(fn () => app(DemonstrationDocumentService::class)->createVersion(auth()->user(), 'personnel', (int) $this->moduleId, ['region_id' => $this->regionId, 'directory_revision_id' => $this->directoryId, 'office_key' => $this->officeKey, 'provenance' => $this->provenance]));
    }

    public function advance(int $id, int $version, string $to): void
    {
        $this->perform(fn () => app(DemonstrationDocumentService::class)->advanceVersion(auth()->user(), $id, $version, $to));
    }

    public function generate(int $request): void
    {
        $this->perform(fn () => app(DemonstrationDocumentService::class)->generate(auth()->user(), $request, (int) ($this->templateIds[$request] ?? 0), (int) ($this->personnelIds[$request] ?? 0)));
    }

    public function recover(int $document): void
    {
        $this->perform(fn () => app(DemonstrationDocumentService::class)->recover(auth()->user(), $document));
    }

    public function restore(int $document): void
    {
        $this->validate(['backup' => 'required|file|max:20480']);
        $this->perform(fn () => app(DemonstrationDocumentService::class)->restore(auth()->user(), $document, $this->backup->getRealPath()));
        $this->backup = null;
    }

    protected function getViewData(): array
    {
        $service = app(DemonstrationDocumentService::class);
        $caseIds = $service->visibleCases(auth()->user())->select('id');
        $requests = IssuanceRequest::where('state', 'submitted')->whereIn('application_id', $caseIds)->orderByDesc('id')->paginate(10);

        return ['requests' => $requests, 'versions' => DemonstrationDocumentVersion::latest('id')->paginate(10, pageName: 'versionsPage'), 'publishedVersions' => DemonstrationDocumentVersion::where('state', 'published')->get(), 'documents' => DemonstrationDocument::whereIn('request_id', $requests->pluck('id'))->get()->keyBy('request_id'), 'modules' => OsdsModule::whereIn('code', ['registration', 'acsa'])->get(), 'regions' => Region::where('is_active', true)->get(), 'directories' => DemoOfficeDirectoryRevision::latest('id')->limit(10)->get()];
    }
}
