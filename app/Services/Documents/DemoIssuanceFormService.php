<?php

namespace App\Services\Documents;

use App\Models\DemoIssuanceFormRevision;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DemoIssuanceFormService
{
    public const LABELS = [
        'kind' => 'Type of certificate', 'organization' => 'Name of HEI / Association',
        'address' => 'Address', 'date' => 'Demonstration date', 'office_key' => 'CHED Regional Office',
    ];

    public function canAnswer(?User $actor): bool
    {
        if (! $this->available() || ! $actor) {
            return false;
        }
        $actor = User::find($actor->id);

        return $actor?->isActive() && $actor->can('applicant.portal.access');
    }

    public function available(): bool
    {
        return app()->environment(['local', 'testing', 'sandbox']);
    }

    public function save(User $actor, ?int $expectedRevision, array $definition): DemoIssuanceFormRevision
    {
        return DB::transaction(function () use ($actor, $expectedRevision, $definition) {
            $fresh = User::query()->lockForUpdate()->findOrFail($actor->id);
            $roles = DB::table('model_has_roles')->where('model_type', $fresh->getMorphClass())->where('model_id', $fresh->id)->orderBy('role_id')->sharedLock()->pluck('role_id');
            DB::table('model_has_permissions')->where('model_type', $fresh->getMorphClass())->where('model_id', $fresh->id)->orderBy('permission_id')->sharedLock()->get();
            DB::table('role_has_permissions')->whereIn('role_id', $roles)->orderBy('role_id')->orderBy('permission_id')->sharedLock()->get();
            DB::statement('LOCK TABLE demo_issuance_form_revisions IN SHARE ROW EXCLUSIVE MODE');
            if (auth()->id() !== $fresh->id || ! app(DemonstrationCertificateRenderer::class)->canPreview($fresh)) {
                throw new AuthorizationException;
            }
            $latest = DemoIssuanceFormRevision::latest('id')->first();
            if ($latest?->id !== $expectedRevision) {
                throw ValidationException::withMessages(['issuanceTitle' => 'This form changed. Reload before publishing your changes.']);
            }
            $rules = [
                'title' => 'required|string|max:160', 'instructions' => 'nullable|string|max:2000',
                'enabled' => 'required|boolean', 'extras' => 'present|array|max:12',
                'extras.*.key' => 'required|uuid|distinct', 'extras.*.label' => 'required|string|max:160',
                'extras.*.type' => 'required|in:text,textarea,date', 'extras.*.required' => 'required|boolean',
            ];
            foreach (self::LABELS as $key => $label) {
                $rules['labels.'.$key] = 'required|string|max:160';
            }
            $data = validator($definition, $rules)->validate();
            $data['labels'] = array_intersect_key($data['labels'], self::LABELS);
            $data['extras'] = array_map(fn ($field) => array_intersect_key($field, array_flip(['key', 'label', 'type', 'required'])), array_values($data['extras']));
            $revision = DemoIssuanceFormRevision::create(['definition' => $data, 'created_by' => $fresh->id]);
            app(AuditRecorder::class)->record('demonstration_issuance.form_published', 'success', $fresh, $revision, metadata: ['prior_revision_id' => $latest?->id, 'available' => (bool) $data['enabled']]);

            return $revision;
        }, 1);
    }

    public function renderApplicant(array $input): string
    {
        $form = DemoIssuanceFormRevision::latest('id')->first();
        $id = validator($input, ['form_revision' => 'required|integer|min:1'])->validate()['form_revision'];
        if (! $form || ! $form->definition['enabled'] || $form->id !== (int) $id) {
            throw ValidationException::withMessages(['form_revision' => 'OSDS changed or withdrew this form. Keep your entries, then reload the form before previewing.']);
        }
        $rules = ['extras' => 'sometimes|array'];
        foreach ($form->definition['extras'] as $field) {
            $rules['extras.'.$field['key']] = [($field['required'] ? 'required' : 'nullable'), 'string', $field['type'] === 'date' ? 'date_format:Y-m-d' : 'max:1000'];
        }
        $answers = validator($input, $rules)->validate();
        $details = [];
        foreach ($form->definition['extras'] as $field) {
            $details[] = ['label' => $field['label'], 'answer' => $answers['extras'][$field['key']] ?? ''];
        }

        // Renderer validates all certificate merge fields and resolves personnel
        // from the posted current directory revision, never client name fields.
        return app(DemonstrationCertificateRenderer::class)->renderIssuance($input, ['formRevision' => $form->id, 'details' => $details]);
    }
}
