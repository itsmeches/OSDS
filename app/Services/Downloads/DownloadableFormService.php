<?php

namespace App\Services\Downloads;

use App\Models\DownloadableForm;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;
use ZipArchive;

class DownloadableFormService
{
    public function importZip(User $actor, UploadedFile $archive, string $versionLabel): int
    {
        if (auth()->id() !== $actor->id || ! $this->canManage($actor)) {
            throw new AuthorizationException;
        }
        validator(['archive' => $archive, 'version_label' => $versionLabel], [
            'archive' => 'required|file|max:10240', 'version_label' => 'required|string|max:80',
        ])->validate();
        $zip = new ZipArchive;
        $temporary = [];
        $paths = [];
        $invalid = fn () => ValidationException::withMessages(['archive' => 'Choose a ZIP containing 1–20 PDF, DOCX or XLSX forms, at most 10 MB each and 50 MB total. Unsafe paths, duplicate filenames and nested archives are not accepted.']);
        if (strtolower($archive->getClientOriginalExtension()) !== 'zip' || $zip->open($archive->getRealPath()) !== true) {
            throw $invalid();
        }
        try {
            if ($zip->numFiles > 100) {
                throw $invalid();
            }
            $files = [];
            $names = [];
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $name = str_replace('\\', '/', $entry['name']);
                if (str_starts_with($name, '/') || preg_match('/(^|\/)\.\.(\/|$)|[:\x00-\x1f]/', $name)) {
                    throw $invalid();
                }
                if (str_ends_with($name, '/')) {
                    continue;
                }
                $base = basename($name);
                $total += $entry['size'];
                if (! in_array(strtolower(pathinfo($base, PATHINFO_EXTENSION)), ['pdf', 'docx', 'xlsx'], true)
                    || $entry['size'] > 10 * 1024 * 1024 || $total > 50 * 1024 * 1024
                    || isset($names[strtolower($base)]) || count($files) >= 20 || ($entry['encryption_method'] ?? 0) !== 0) {
                    throw $invalid();
                }
                $names[strtolower($base)] = true;
                $path = tempnam(sys_get_temp_dir(), 'osds-form-');
                $temporary[] = $path;
                $bytes = $zip->getFromIndex($i, 10 * 1024 * 1024 + 1);
                if ($bytes === false || strlen($bytes) !== $entry['size'] || file_put_contents($path, $bytes) === false) {
                    throw $invalid();
                }
                $files[] = new UploadedFile($path, $base, null, null, true);
            }
            if (! $files) {
                throw $invalid();
            }

            return DB::transaction(function () use ($actor, $files, $versionLabel, &$paths): int {
                $this->manager($actor);
                foreach ($files as $file) {
                    $form = $this->publish($actor, [
                        'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                        'description' => null, 'module_id' => null, 'version_label' => $versionLabel,
                    ], $file);
                    $paths[] = $form->currentVersion->path;
                }

                return count($files);
            }, 1);
        } catch (Throwable $error) {
            Storage::disk('local')->delete($paths);
            throw $error;
        } finally {
            $zip->close();
            foreach ($temporary as $path) {
                @unlink($path);
            }
        }
    }

    public function canRead(?User $user): bool
    {
        // Blank source downloads are part of the existing local application,
        // not the synthetic application-submission workflow. Production stays
        // disabled; no existing applicant/evidence sandbox guard is changed.
        if (! $user || ! app()->environment(['local', 'testing', 'sandbox']) || app()->environment('production')) {
            return false;
        }
        $user = User::find($user->id);

        return $user?->isActive() && (($user->can('platform.admin.access') && ($user->can('forms.downloads.manage') || $user->can('forms.downloads.view')))
            || $user->can('applicant.portal.access'));
    }

    public function canManage(?User $user): bool
    {
        $fresh = $user ? User::find($user->id) : null;

        return $this->canRead($fresh) && $fresh->can('platform.admin.access')
            && $fresh->can('forms.downloads.manage');
    }

    private function manager(User $actor): User
    {
        $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
        $roles = DB::table('model_has_roles')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)->orderBy('role_id')->sharedLock()->pluck('role_id');
        DB::table('model_has_permissions')->where('model_type', $actor->getMorphClass())->where('model_id', $actor->id)->orderBy('permission_id')->sharedLock()->get();
        DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();
        if (auth()->id() !== $actor->id || ! $this->canManage($actor)) {
            throw new AuthorizationException;
        }

        return $actor;
    }

    public function publish(User $actor, array $attributes, UploadedFile $file, ?int $id = null, ?int $expectedVersion = null): DownloadableForm
    {
        // Never trust client filenames or MIME alone. Only non-macro Office
        // packages and PDF are accepted; archives cannot be downloaded as forms.
        if (auth()->id() !== $actor->id || ! $this->canManage($actor)) {
            throw new AuthorizationException;
        }
        $data = validator($attributes, [
            'title' => 'required|string|max:160', 'description' => 'nullable|string|max:2000',
            'module_id' => ['nullable', 'integer', Rule::exists('modules', 'id')],
            'version_label' => 'required|string|max:80',
        ])->validate();
        validator(['upload' => $file], ['upload' => 'required|file|max:10240'])->validate();
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = match ($extension) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            default => null,
        };
        $valid = false;
        if ($extension === 'pdf') {
            $valid = $file->getMimeType() === 'application/pdf' && file_get_contents($file->getRealPath(), false, null, 0, 5) === '%PDF-';
        } elseif (in_array($extension, ['docx', 'xlsx'], true)) {
            $zip = new ZipArchive;
            if ($zip->open($file->getRealPath()) === true) {
                $part = $extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml';
                $typesEntry = $zip->statName('[Content_Types].xml');
                $types = $typesEntry && $typesEntry['size'] <= 262144 ? $zip->getFromName('[Content_Types].xml') : false;
                $contentType = $extension === 'docx' ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'
                    : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml';
                $valid = $zip->locateName($part) !== false && is_string($types)
                    && str_contains($types, $contentType) && ! str_contains(strtolower($types), 'macroenabled');
                $total = 0;
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->statIndex($i);
                    $total += $entry['size'];
                    if (str_contains(strtolower($entry['name']), 'vbaproject') || $total > 50 * 1024 * 1024 || $zip->numFiles > 2000) {
                        $valid = false;
                        break;
                    }
                }
                $zip->close();
            }
        }
        if (! $mime || ! $valid) {
            throw ValidationException::withMessages(['upload' => 'Choose a valid PDF, DOCX or XLSX file without macros (maximum 10 MB).']);
        }
        $path = 'downloadable-forms/'.Str::uuid().'.'.$extension;
        $stream = fopen($file->getRealPath(), 'rb');
        try {
            if (! Storage::disk('local')->put($path, $stream)) {
                throw new RuntimeException('Form storage unavailable.');
            }
        } finally {
            fclose($stream);
        }
        try {
            return DB::transaction(function () use ($actor, $data, $id, $expectedVersion, $file, $extension, $mime, $path): DownloadableForm {
                $actor = $this->manager($actor);
                $form = $id ? DownloadableForm::query()->lockForUpdate()->findOrFail($id) : new DownloadableForm(['public_id' => (string) Str::uuid()]);
                if ($id && $form->current_version_id !== $expectedVersion) {
                    throw ValidationException::withMessages(['title' => 'This form changed. Reload the catalogue before replacing it.']);
                }
                $form->fill(collect($data)->except('version_label')->all());
                $form->save();
                $version = $form->versions()->create([
                    'version_number' => (int) $form->versions()->max('version_number') + 1,
                    'version_label' => $data['version_label'], 'path' => $path,
                    'original_name' => Str::limit(preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($file->getClientOriginalName())), 240, ''),
                    'extension' => $extension, 'mime_type' => $mime, 'size' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath()), 'uploaded_by' => $actor->id,
                ]);
                $form->update(['current_version_id' => $version->id, 'withdrawn_at' => null]);
                app(AuditRecorder::class)->record($id ? 'downloadable_form.replaced' : 'downloadable_form.published', 'success', $actor, $form, metadata: ['version_id' => $version->id, 'version_number' => $version->version_number]);

                return $form;
            }, 1);
        } catch (Throwable $error) {
            // Only the newly staged, uncommitted upload is removed. Historical
            // version bytes are never deleted by replacement or withdrawal.
            Storage::disk('local')->delete($path);
            throw $error;
        }
    }

    public function withdraw(User $actor, int $id, int $expectedVersion): void
    {
        DB::transaction(function () use ($actor, $id, $expectedVersion): void {
            $actor = $this->manager($actor);
            $form = DownloadableForm::query()->lockForUpdate()->findOrFail($id);
            if ($form->current_version_id !== $expectedVersion) {
                throw ValidationException::withMessages(['title' => 'This form changed. Reload the catalogue before withdrawing it.']);
            }
            if ($form->withdrawn_at) {
                return;
            }
            $form->update(['withdrawn_at' => now()]);
            app(AuditRecorder::class)->record('downloadable_form.withdrawn', 'success', $actor, $form, metadata: ['version_id' => $form->current_version_id]);
        }, 1);
    }
}
