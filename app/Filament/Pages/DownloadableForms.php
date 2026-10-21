<?php

namespace App\Filament\Pages;

use App\Models\DownloadableForm;
use App\Models\OsdsModule;
use App\Services\Downloads\DownloadableFormService;
use App\Support\StaffActionFeedback;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Icons\Heroicon;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class DownloadableForms extends Page
{
    use WithFileUploads;
    use WithPagination;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?string $navigationLabel = 'Downloadable forms';

    protected static ?string $title = 'Downloadable forms';

    protected string $view = 'filament.pages.downloadable-forms';

    public ?int $editing = null;

    public ?int $expectedVersion = null;

    public string $formTitle = '';

    public string $description = '';

    public string $moduleId = '';

    public string $versionLabel = '';

    public $upload;

    public $archive;

    public string $archiveVersion = '';

    public function importArchive(): void
    {
        abort_unless(app(DownloadableFormService::class)->canManage(auth()->user()), 403);
        $this->validate(['archive' => 'required|file|max:10240', 'archiveVersion' => 'required|string|max:80']);
        try {
            $count = 0;
            StaffActionFeedback::run(function () use (&$count): void {
                $count = app(DownloadableFormService::class)->importZip(auth()->user(), $this->archive, $this->archiveVersion);
            });
        } catch (Halt) {
            $this->addError('archive', 'Import could not be confirmed. Inspect the catalogue in another tab before trying again.');

            return;
        }
        $this->reset(['archive', 'archiveVersion']);
        $this->resetPage();
        Notification::make()->title($count.' forms imported')->success()->send();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('platform.admin.access')
            && app(DownloadableFormService::class)->canRead(auth()->user());
    }

    public function edit(int $id): void
    {
        abort_unless(app(DownloadableFormService::class)->canManage(auth()->user()), 403);
        $form = DownloadableForm::findOrFail($id);
        $this->editing = $form->id;
        $this->expectedVersion = $form->current_version_id;
        $this->formTitle = $form->title;
        $this->description = $form->description ?? '';
        $this->moduleId = (string) $form->module_id;
        $this->versionLabel = '';
        $this->upload = null;
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset(['editing', 'expectedVersion', 'formTitle', 'description', 'moduleId', 'versionLabel', 'upload']);
        $this->resetValidation();
    }

    public function save(): void
    {
        abort_unless(app(DownloadableFormService::class)->canManage(auth()->user()), 403);
        $this->validate(['upload' => 'required|file|max:10240']);
        try {
            StaffActionFeedback::run(fn () => app(DownloadableFormService::class)->publish(auth()->user(), [
                'title' => $this->formTitle, 'description' => $this->description,
                'module_id' => $this->moduleId === '' ? null : $this->moduleId,
                'version_label' => $this->versionLabel,
            ], $this->upload, $this->editing, $this->expectedVersion));
        } catch (Halt) {
            $this->addError('upload', 'The change could not be confirmed. Keep this page open and inspect the catalogue in another tab before trying again.');

            return;
        }
        $this->cancel();
        Notification::make()->title('Downloadable form published')->success()->send();
    }

    public function withdraw(int $id, int $version): void
    {
        try {
            StaffActionFeedback::run(fn () => app(DownloadableFormService::class)->withdraw(auth()->user(), $id, $version));
        } catch (Halt) {
            $this->addError('upload', 'Withdrawal could not be confirmed. Inspect the catalogue before trying again.');

            return;
        }
        Notification::make()->title('Form withdrawn from downloads')->success()->send();
    }

    protected function getViewData(): array
    {
        $manager = app(DownloadableFormService::class)->canManage(auth()->user());

        return [
            'manager' => $manager,
            'forms' => DownloadableForm::with(['currentVersion', 'module'])->when($manager, fn ($query) => $query->with('versions'))->withCount('versions')->when(! $manager, fn ($query) => $query->whereNull('withdrawn_at')->whereNotNull('current_version_id'))->orderBy('title')->orderBy('id')->paginate(10),
            'modules' => OsdsModule::orderBy('name')->get(),
        ];
    }
}
