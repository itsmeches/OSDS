<?php

namespace App\Filament\Pages;

use App\Models\DemoIssuanceFormRevision;
use App\Models\DemoOfficeDirectoryRevision;
use App\Services\Documents\DemoIssuanceFormService;
use App\Services\Documents\DemonstrationCertificateRenderer;
use App\Services\Documents\DemoOfficeDirectoryService;
use App\Support\StaffActionFeedback;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class DemonstrationDocuments extends Page
{
    public ?string $officeKey = null;

    public ?int $expectedDirectory = null;

    public string $officeLabel = '';

    public string $director = '';

    public string $designation = '';

    public ?int $expectedForm = null;

    public string $issuanceTitle = 'Demonstration issuance form';

    public string $issuanceInstructions = 'Use fictional applicant details only. This produces a watermarked sample, not an issued certificate.';

    public bool $issuanceEnabled = false;

    public array $issuanceLabels = DemoIssuanceFormService::LABELS;

    public array $issuanceExtras = [];

    public function addIssuanceQuestion(): void
    {
        abort_unless(static::canAccess(), 403);
        if (count($this->issuanceExtras) < 12) {
            $this->issuanceExtras[] = ['key' => (string) Str::uuid(), 'label' => '', 'type' => 'text', 'required' => false];
        }
    }

    public function removeIssuanceQuestion(int $index): void
    {
        abort_unless(static::canAccess(), 403);
        unset($this->issuanceExtras[$index]);
        $this->issuanceExtras = array_values($this->issuanceExtras);
    }

    public function publishIssuanceForm(): void
    {
        abort_unless(static::canAccess(), 403);
        try {
            StaffActionFeedback::run(fn () => app(DemoIssuanceFormService::class)->save(auth()->user(), $this->expectedForm, [
                'title' => $this->issuanceTitle, 'instructions' => $this->issuanceInstructions,
                'enabled' => $this->issuanceEnabled, 'labels' => $this->issuanceLabels, 'extras' => $this->issuanceExtras,
            ]));
        } catch (Halt) {
            $this->addError('issuanceTitle', 'Publication could not be confirmed. Inspect the saved form in another tab before trying again.');

            return;
        }
        $this->expectedForm = DemoIssuanceFormRevision::max('id');
        Notification::make()->title('Applicant demonstration form saved')->success()->send();
    }

    public function mount(): void
    {
        $this->expectedDirectory = DemoOfficeDirectoryRevision::max('id');
        $form = DemoIssuanceFormRevision::latest('id')->first();
        if ($form) {
            $this->expectedForm = $form->id;
            $this->issuanceTitle = $form->definition['title'];
            $this->issuanceInstructions = $form->definition['instructions'] ?? '';
            $this->issuanceEnabled = (bool) $form->definition['enabled'];
            $this->issuanceLabels = $form->definition['labels'];
            $this->issuanceExtras = $form->definition['extras'];
        }
    }

    public function editOffice(string $key): void
    {
        abort_unless(static::canAccess(), 403);
        $revision = DemoOfficeDirectoryRevision::latest('id')->firstOrFail();
        abort_unless(isset($revision->offices[$key]), 404);
        $this->officeKey = $key;
        $this->expectedDirectory = $revision->id;
        $this->officeLabel = $revision->offices[$key]['label'];
        $this->director = $revision->offices[$key]['director'];
        $this->designation = $revision->offices[$key]['designation'];
        $this->resetValidation();
    }

    public function newOffice(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->reset(['officeKey', 'officeLabel', 'director', 'designation']);
        $this->expectedDirectory = DemoOfficeDirectoryRevision::max('id');
        $this->resetValidation();
    }

    public function saveOffice(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->validate(['officeLabel' => 'required|string|max:100', 'director' => 'required|string|max:160', 'designation' => 'required|string|max:160']);
        try {
            StaffActionFeedback::run(fn () => app(DemoOfficeDirectoryService::class)->save(auth()->user(), $this->expectedDirectory, $this->officeKey, [
                'label' => $this->officeLabel, 'director' => $this->director, 'designation' => $this->designation,
            ]));
        } catch (Halt) {
            $this->addError('officeLabel', 'Save could not be confirmed. Inspect the directory in another tab before trying again.');

            return;
        }
        $this->newOffice();
        Notification::make()->title('Demonstration office directory saved')->success()->send();
    }

    protected function getViewData(): array
    {
        return ['directory' => DemoOfficeDirectoryRevision::latest('id')->first()];
    }

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Demonstration documents';

    protected static ?string $title = 'Demonstration documents';

    protected string $view = 'filament.pages.demonstration-documents';

    public static function canAccess(): bool
    {
        return app(DemonstrationCertificateRenderer::class)->canPreview(auth()->user());
    }
}
