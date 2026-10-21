<?php

namespace App\Services\Documents;

use App\Models\DemoOfficeDirectoryRevision;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DemonstrationCertificateRenderer
{
    public const VERSION = 'demo-v1';

    public function renderIssuance(array $input, ?array $applicantForm = null): string
    {
        $data = validator($input, [
            'kind' => 'required|in:cor,acsa', 'organization' => 'required|string|max:200',
            'address' => 'required|string|max:400', 'date' => 'required|date_format:Y-m-d',
            'office_key' => 'required|uuid', 'directory_revision' => 'required|integer|min:1',
            'fictional_confirmation' => 'accepted',
        ])->validate();
        $directory = DemoOfficeDirectoryRevision::latest('id')->first();
        if (! $directory || $directory->id !== (int) $data['directory_revision'] || ! isset($directory->offices[$data['office_key']])) {
            throw ValidationException::withMessages(['office_key' => 'The office directory changed or this office is unavailable. Reload and select it again.']);
        }
        $office = $directory->offices[$data['office_key']];

        $version = $applicantForm ? 'demo-v3' : 'demo-v2';

        return view('documents.demonstration.'.$version.'.'.$data['kind'], [
            'organization' => $data['organization'], 'address' => $data['address'],
            'date' => $data['date'].' (demonstration date)', 'office' => $office['label'],
            'director' => $office['director'], 'designation' => $office['designation'],
            'reference' => 'DEMO-'.strtoupper($data['kind']).'-PREVIEW',
            'version' => $version, 'directoryRevision' => $directory->id,
            'formRevision' => $applicantForm['formRevision'] ?? null,
            'details' => $applicantForm['details'] ?? [],
        ])->render();
    }

    public function canPreview(?User $actor): bool
    {
        if (! app()->environment(['local', 'testing', 'sandbox']) || ! $actor) {
            return false;
        }
        $actor = User::find($actor->id);

        return $actor?->isActive() && $actor->can('platform.admin.access') && $actor->can('forms.downloads.manage');
    }

    public function render(string $kind, string $version): string
    {
        if (! in_array($kind, ['cor', 'acsa'], true) || $version !== self::VERSION) {
            throw new InvalidArgumentException('Unknown demonstration template.');
        }

        // Fixed fictional data only: no live case, personnel or applicant merge.
        return view('documents.demonstration.'.$version.'.'.$kind, [
            'organization' => 'Fictional Lakeside Higher Education Institute',
            'address' => '123 Demonstration Avenue, Fictional City, Philippines',
            'reference' => 'DEMO-'.strtoupper($kind).'-0001',
            'date' => '01 December 2026 (fictional)',
            'director' => 'Dr. Alex Sample (fictional)',
            'designation' => 'Demonstration Director',
            'office' => 'CHED Regional Office — Fictional demonstration office',
            'version' => self::VERSION,
        ])->render();
    }
}
