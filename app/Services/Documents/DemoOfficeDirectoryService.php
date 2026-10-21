<?php

namespace App\Services\Documents;

use App\Models\DemoOfficeDirectoryRevision;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DemoOfficeDirectoryService
{
    public function save(User $actor, ?int $expectedRevision, ?string $key, array $attributes): DemoOfficeDirectoryRevision
    {
        return DB::transaction(function () use ($actor, $expectedRevision, $key, $attributes) {
            $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
            $roles = DB::table('model_has_roles')->where('model_type', $fresh->getMorphClass())->where('model_id', $fresh->id)->orderBy('role_id')->sharedLock()->pluck('role_id');
            DB::table('model_has_permissions')->where('model_type', $fresh->getMorphClass())->where('model_id', $fresh->id)->orderBy('permission_id')->sharedLock()->get();
            DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();
            // Low-volume directory configuration: serialize even its first insert.
            DB::statement('LOCK TABLE demo_office_directory_revisions IN SHARE ROW EXCLUSIVE MODE');
            if (auth()->id() !== $fresh->id || ! app(DemonstrationCertificateRenderer::class)->canPreview($fresh)) {
                throw new AuthorizationException;
            }
            $latest = DemoOfficeDirectoryRevision::latest('id')->first();
            if ($latest?->id !== $expectedRevision) {
                throw ValidationException::withMessages(['officeLabel' => 'The directory changed. Reload before saving this office.']);
            }
            $data = validator($attributes, [
                'label' => 'required|string|max:100', 'director' => 'required|string|max:160',
                'designation' => 'required|string|max:160',
            ])->validate();
            $offices = $latest?->offices ?? [];
            if ($key !== null && ! isset($offices[$key])) {
                throw ValidationException::withMessages(['officeLabel' => 'Choose an existing office or add a new one.']);
            }
            foreach ($offices as $existingKey => $office) {
                if ($existingKey !== $key && mb_strtolower(trim($office['label'])) === mb_strtolower(trim($data['label']))) {
                    throw ValidationException::withMessages(['officeLabel' => 'This office label already exists. Edit its existing entry.']);
                }
            }
            if ($key === null && count($offices) >= 100) {
                throw ValidationException::withMessages(['officeLabel' => 'The demonstration directory supports up to 100 offices.']);
            }
            $offices[$key ?? (string) Str::uuid()] = array_map('trim', $data);
            $revision = DemoOfficeDirectoryRevision::create(['offices' => $offices, 'created_by' => $fresh->id]);
            app(AuditRecorder::class)->record('demonstration_document.directory_updated', 'success', $fresh, $revision, metadata: ['prior_revision_id' => $latest?->id]);

            return $revision;
        }, 1);
    }
}
