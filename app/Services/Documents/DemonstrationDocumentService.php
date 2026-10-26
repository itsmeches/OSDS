<?php

namespace App\Services\Documents;

use App\Models\Application;
use App\Models\DemonstrationDocument;
use App\Models\DemonstrationDocumentVersion;
use App\Models\DemoOfficeDirectoryRevision;
use App\Models\IssuanceRequest;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Auditing\AuditRecorder;
use App\Services\Authorization\ScopeAuthorizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class DemonstrationDocumentService
{
    public const WORDING = 'Sample reference wording: CHED Memorandum Order No. 13, Series of 2026, “Policies and Guidelines on Student-Athletes Protection,” and Republic Act No. 10676, “Student-Athletes Protection Act.”';

    public function createVersion(User $actor, string $kind, int $moduleId, array $input): DemonstrationDocumentVersion
    {
        return DB::transaction(function () use ($actor, $kind, $moduleId, $input) {
            abort_unless(in_array($kind, ['template', 'personnel'], true), 422);
            $actor = app(CaseIssuanceService::class)->actor($actor, 'documents.'.($kind === 'template' ? 'templates' : 'personnel').'.manage');
            $module = OsdsModule::whereIn('code', ['registration', 'acsa'])->lockForUpdate()->findOrFail($moduleId);
            if (! $module->is_active) {
                $this->invalid('Module unavailable.');
            }
            if ($kind === 'template') {
                $data = validator($input, ['wording' => 'required|string|max:1500', 'fields' => 'present|array|max:20', 'fields.*' => 'string|regex:/^[a-zA-Z0-9_]+$/|max:100|distinct'])->validate();
                if (preg_match('/[<>]|https?:\/\//i', $data['wording'])) {
                    $this->invalid('Use plain text without markup or links.');
                }
                $content = ['layout' => 'b3-letter-v1', 'wording' => $data['wording'], 'fields' => $data['fields']];
                $regionId = $directoryId = null;
            } else {
                $data = validator($input, ['region_id' => 'required|integer', 'directory_revision_id' => 'required|integer', 'office_key' => 'required|uuid', 'provenance' => 'required|string|max:1000'])->validate();
                $regionId = Region::where('is_active', true)->findOrFail($data['region_id'])->id;
                $directory = DemoOfficeDirectoryRevision::findOrFail($data['directory_revision_id']);
                $office = $directory->offices[$data['office_key']] ?? null;
                if (! $office || collect(['label', 'director', 'designation'])->contains(fn ($k) => ! is_string($office[$k] ?? null) || trim($office[$k]) === '')) {
                    $this->invalid('Select a complete directory entry.');
                }
                $directoryId = $directory->id;
                $content = ['office_key' => $data['office_key'], 'office' => $office, 'provenance' => $data['provenance']];
            }
            $v = DemonstrationDocumentVersion::create(['kind' => $kind, 'module_id' => $moduleId, 'region_id' => $regionId, 'directory_revision_id' => $directoryId, 'version' => 1 + (int) DemonstrationDocumentVersion::where(['kind' => $kind, 'module_id' => $moduleId, 'region_id' => $regionId])->max('version'), 'state' => 'draft', 'content' => $content, 'content_hash' => $this->hash($content), 'created_by' => $actor->id]);
            $this->event($actor, 'version_created', version: $v);

            return $v;
        }, 1);
    }

    public function advanceVersion(User $actor, int $id, int $expected, string $to): DemonstrationDocumentVersion
    {
        return DB::transaction(function () use ($actor, $id, $expected, $to) {
            $source = DemonstrationDocumentVersion::findOrFail($id);
            $verb = $to === 'reviewed' ? 'review' : 'publish';
            $actor = app(CaseIssuanceService::class)->actor($actor, 'documents.'.($source->kind === 'template' ? 'templates' : 'personnel').'.'.$verb);
            OsdsModule::whereKey($source->module_id)->lockForUpdate()->firstOrFail();
            $v = DemonstrationDocumentVersion::lockForUpdate()->findOrFail($id);
            $from = ['reviewed' => 'draft', 'published' => 'reviewed', 'withdrawn' => 'published'][$to] ?? null;
            if (! $from || $v->state !== $from || $v->lock_version !== $expected) {
                $this->invalid('Version changed. Reload before acting.');
            }
            if ($to === 'published' && DemonstrationDocumentVersion::where(['kind' => $v->kind, 'module_id' => $v->module_id, 'region_id' => $v->region_id, 'state' => 'published'])->exists()) {
                $this->invalid('Withdraw the published version first.');
            }
            $v->update(['state' => $to, 'lock_version' => $expected + 1]);
            $this->event($actor, 'version_'.$to, version: $v);

            return $v;
        }, 1);
    }

    public function staff(User $actor, Application $case, string $permission): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $actor = app(CaseIssuanceService::class)->actor($actor, $permission);
        if (! $actor->can('documents.view')) {
            throw new AuthorizationException;
        }
        $actor->regionAssignments()->orderBy('id')->lockForUpdate()->get();
        $actor->nationwideAccessGrants()->orderBy('id')->lockForUpdate()->get();
        SyntheticApplicationReviewerAssignment::where('reviewer_id', $actor->id)->orderBy('id')->lockForUpdate()->get();
        app(ApplicationLifecycleService::class)->authorizeReviewer($actor, $case, $permission);
        if ($actor->id === $case->applicant_id) {
            throw new AuthorizationException;
        }

        return $actor;
    }

    public function canSee(User $actor, Application $case): bool
    {
        try {
            $this->staff($actor, $case, 'documents.view');

            return true;
        } catch (AuthorizationException) {
            return false;
        }
    }

    public function visibleCases(User $actor): Builder
    {
        $query = Application::query();
        if (! $actor->isActive() || ! $actor->is_synthetic || ! $actor->can('documents.view') || ! $actor->can('platform.admin.access') || ! app(ApplicantSandboxGuard::class)->allowed()) {
            return $query->whereRaw('1=0');
        }

        return $query->whereIn('routing_region_id', app(ScopeAuthorizer::class)->effectiveRegionIds($actor))->whereExists(function ($q) use ($actor) {
            $q->selectRaw('1')->from('synthetic_application_reviewer_assignments as b3_scope')->where('b3_scope.reviewer_id', $actor->id)->whereColumn('b3_scope.region_id', 'applications.routing_region_id')->whereColumn('b3_scope.module_id', 'applications.module_id')->whereNull('b3_scope.revoked_at')->where('b3_scope.starts_at', '<=', now())->where(fn ($end) => $end->whereNull('b3_scope.ends_at')->orWhere('b3_scope.ends_at', '>', now()));
        });
    }

    private function coherent(IssuanceRequest $request): Application
    {
        $case = Application::lockForUpdate()->findOrFail($request->application_id);
        $decision = $case->decisionStages()->find($request->decision_id);
        if ($request->state !== 'submitted' || $case->state !== 'approved' || $case->current_working_revision_id !== null || $case->current_submission_id !== $request->submission_id || $case->final_decision_id !== $request->decision_id || $decision?->action !== 'approved' || $decision->submission_id !== $request->submission_id || ! $case->module->is_active || ! $case->region->is_active) {
            $this->invalid('A coherent submitted request and exact Director approval are required.');
        }
        if (! trim($request->identity_snapshot['organization'] ?? '') || ! trim($request->identity_snapshot['address'] ?? '')) {
            $this->invalid('Approved identity details are missing.');
        }

        return $case;
    }

    public function generate(User $actor, int $requestId, int $templateId, int $personnelId): DemonstrationDocument
    {
        $doc = DB::transaction(function () use ($actor, $requestId, $templateId, $personnelId) {
            $r = IssuanceRequest::findOrFail($requestId);
            $actor = $this->staff($actor, Application::findOrFail($r->application_id), 'documents.generate');
            $case = $this->coherent($r);
            $r = IssuanceRequest::lockForUpdate()->findOrFail($r->id);
            $doc = DemonstrationDocument::where('request_id', $r->id)->lockForUpdate()->first();
            if ($doc?->state === 'completed') {
                return $doc;
            }
            if ($doc?->state === 'processing') {
                $this->invalid('Generation is processing. Inspect it before an explicit recovery.');
            }
            [$template,$personnel] = $this->versions($r, $templateId, $personnelId);
            $key = (string) Str::uuid();
            $render = ['renderer' => 'b3-letter-v1', 'template' => $template->content, 'personnel' => $personnel->content, 'identity' => $r->identity_snapshot, 'date' => now()->timezone('Asia/Manila')->format('Y-m-d')];
            $attrs = ['template_id' => $templateId, 'personnel_id' => $personnelId, 'actor_id' => $actor->id, 'attempt_key' => $key, 'state' => 'processing', 'render_snapshot' => $render, 'started_at' => now()];
            if ($doc) {
                $doc->update($attrs);
            } else {
                $doc = DemonstrationDocument::create($attrs + ['public_id' => (string) Str::uuid(), 'request_id' => $r->id, 'application_id' => $case->id, 'submission_id' => $r->submission_id, 'decision_id' => $r->decision_id, 'definition_id' => $r->definition_id, 'module_id' => $r->module_id, 'region_id' => $r->region_id, 'institution_id' => $r->institution_id, 'certificate_type' => $r->certificate_type, 'source_snapshot' => $this->source($r)]);
            }
            DB::table('demonstration_document_attempts')->insert(['document_id' => $doc->id, 'attempt_key' => $key, 'actor_id' => $actor->id, 'template_id' => $templateId, 'personnel_id' => $personnelId, 'render_snapshot' => json_encode($render, JSON_THROW_ON_ERROR), 'created_at' => now()]);
            $this->event($actor, 'generation_started', $doc);

            return $doc->fresh();
        }, 1);
        if ($doc->state === 'completed') {
            $this->verifiedBytes($actor, $doc, 'documents.generate');

            return $doc;
        }
        $pdf = app(PrivateDemonstrationPdf::class);
        $temp = storage_path('app/private/demonstration-documents/tmp/'.$doc->attempt_key);
        $key = 'demonstration-documents/'.$doc->public_id.'/'.$doc->attempt_key.'.pdf';
        $path = storage_path('app/private/'.$key);
        try {
            $rendered = $pdf->render($this->html($doc), $temp);
            $pdf->inspect($rendered);
            if (! is_dir(dirname($path)) && ! mkdir(dirname($path), 0700, true) && ! is_dir(dirname($path))) {
                throw new \RuntimeException('Private promotion failed');
            }
            $in = fopen($rendered, 'rb');
            $out = fopen($path, 'xb');
            if (! $in || ! $out) {
                throw new \RuntimeException('Private promotion failed');
            }
            try {
                if (stream_copy_to_stream($in, $out) === false || ! fflush($out)) {
                    throw new \RuntimeException('Private promotion failed');
                }
            } finally {
                fclose($in);
                fclose($out);
            }
            $verified = $pdf->inspect($path, [$doc->render_snapshot['identity']['organization'], $doc->render_snapshot['identity']['address'], $doc->render_snapshot['personnel']['office']['director'], $doc->certificate_type === 'cor' ? 'CERTIFICATE OF RECOGNITION' : 'AUTHORITY TO CONDUCT SPORTS ACTIVITY']);

            return DB::transaction(function () use ($actor, $doc, $key, $path, $verified) {
                $r = IssuanceRequest::findOrFail($doc->request_id);
                $fresh = $this->staff($actor, Application::findOrFail($r->application_id), 'documents.generate');
                $this->coherent($r);
                $this->versions($r, $doc->template_id, $doc->personnel_id);
                $current = DemonstrationDocument::lockForUpdate()->findOrFail($doc->id);
                if ($current->state !== 'processing' || $current->attempt_key !== $doc->attempt_key || $current->actor_id !== $fresh->id || $current->render_snapshot !== $doc->render_snapshot || ! is_file($path) || filesize($path) !== $verified['byte_size'] || ! hash_equals($verified['checksum'], hash_file('sha256', $path))) {
                    $this->invalid('Attempt or private PDF changed. Inspect before recovery.');
                }
                $current->update($verified + ['state' => 'completed', 'storage_key' => $key, 'completed_at' => now()]);
                $this->event($fresh, 'generation_completed', $current);

                return $current;
            }, 1);
        } catch (\Throwable $e) {
            // Inspection is mandatory before cleanup. An ambiguous commit leaves
            // bytes private and reserved; it never initiates an automatic retry.
            try {
                DB::transaction(function () use ($doc, $actor) {
                    $current = DemonstrationDocument::lockForUpdate()->findOrFail($doc->id);
                    if ($current->state === 'processing' && $current->attempt_key === $doc->attempt_key) {
                        $current->update(['state' => 'failed']);
                        $this->event($actor, 'generation_failed', $current);
                    }
                }, 1);
                $known = DemonstrationDocument::findOrFail($doc->id);
                if ($known->state === 'failed' && $known->attempt_key === $doc->attempt_key && is_file($path)) {
                    unlink($path);
                }
            } catch (\Throwable) { /* Retain private bytes for explicit inspection. */
            }
            throw $e;
        } finally {
            foreach (['input.html', 'rendered.pdf'] as $name) {
                if (is_file($temp.'/'.$name)) {
                    unlink($temp.'/'.$name);
                }
            }
            if (is_dir($temp)) {
                @rmdir($temp);
            }
        }
    }

    private function versions(IssuanceRequest $r, int $template, int $personnel): array
    {
        $rows = DemonstrationDocumentVersion::whereIn('id', [$template, $personnel])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
        $t = $rows[$template] ?? null;
        $p = $rows[$personnel] ?? null;
        if (! $t || ! $p || $t->kind !== 'template' || $p->kind !== 'personnel' || $t->state !== 'published' || $p->state !== 'published' || $t->module_id !== $r->module_id || $p->module_id !== $r->module_id || $p->region_id !== $r->region_id) {
            $this->invalid('Exact published template and region personnel mapping are required.');
        }
        $allowed = array_column($r->definition_snapshot['questions'], 'key');
        if (array_diff($t->content['fields'], $allowed)) {
            $this->invalid('Template fields do not match this submitted definition.');
        }

        return [$t, $p];
    }

    private function source(IssuanceRequest $r): array
    {
        return ['identity' => $r->identity_snapshot, 'definition' => $r->definition_snapshot, 'answers' => $r->answers, 'payload_hash' => $r->payload_hash];
    }

    public function html(DemonstrationDocument $d): string
    {
        return $this->renderHtml($d->render_snapshot, $d->source_snapshot, $d->certificate_type, $d->application_id, $d->submission_id, $d->decision_id, $d->template_id, $d->personnel_id);
    }

    private function renderHtml(array $render, array $source, string $type, int|string $case, int|string $submission, int|string $decision, int $templateVersion, int $personnelVersion): string
    {
        $details = [];
        foreach ($source['definition']['questions'] as $q) {
            if (! in_array($q['key'], $render['template']['fields'], true)) {
                continue;
            }
            $value = $source['answers'][$q['key']] ?? null;
            $options = collect($q['options'] ?? [])->pluck('label', 'key');
            $label = fn ($v) => is_bool($v) ? ($v ? 'Confirmed' : 'Not confirmed') : (string) ($options[$v ?? ''] ?? $v ?? 'Not supplied');
            $details[] = ['label' => $q['label'], 'value' => is_array($value) ? implode(', ', array_map($label, $value)) : $label($value)];
        }

        return view('documents.demonstration.b3.layout', ['title' => $type === 'cor' ? 'CERTIFICATE OF RECOGNITION' : 'AUTHORITY TO CONDUCT SPORTS ACTIVITY', 'identity' => $render['identity'], 'template' => $render['template'], 'personnel' => $render['personnel'], 'date' => $render['date'], 'caseReference' => $case, 'submissionId' => $submission, 'decisionId' => $decision, 'templateVersion' => $templateVersion, 'personnelVersion' => $personnelVersion, 'details' => $details])->render();
    }

    public function preview(User $actor, DemonstrationDocumentVersion $template): string
    {
        app(CaseIssuanceService::class)->actor($actor, $actor->can('documents.templates.review') ? 'documents.templates.review' : 'documents.view');
        abort_unless($template->kind === 'template', 422);

        return $this->renderHtml(['template' => $template->content, 'identity' => ['organization' => 'Fictional Lakeside HEI', 'address' => '123 Fictional Demo Road'], 'personnel' => ['office' => ['director' => 'Dr. Alex Sample (fictional)', 'designation' => 'Demonstration Director', 'label' => 'Fictional regional office']], 'date' => now()->timezone('Asia/Manila')->format('Y-m-d')], ['definition' => ['questions' => []], 'answers' => []], OsdsModule::findOrFail($template->module_id)->code === 'registration' ? 'cor' : 'acsa', 'PREVIEW', 'PREVIEW', 'PREVIEW', $template->id, 0);
    }

    private function authorizeRead(User $actor, DemonstrationDocument $d, string $staffPermission): User
    {
        $case = Application::findOrFail($d->application_id);
        if ($case->applicant_id === $actor->id) {
            $actor = app(CaseIssuanceService::class)->actor($actor);
            $actor->representedInstitutions()->get();
            Gate::forUser($actor)->authorize('view', $case);

            return $actor;
        }

        return $this->staff($actor, $case, $staffPermission);
    }

    public function verifiedBytes(User $actor, DemonstrationDocument $doc, string $permission = 'documents.download'): string
    {
        DB::transaction(fn () => $this->authorizeRead($actor, $doc, $permission), 1);
        abort_unless($doc->state === 'completed', 409);
        try {
            $path = storage_path('app/private/'.$doc->storage_key);
            $handle = fopen($path, 'rb');
            if (! $handle) {
                throw new \RuntimeException('Missing file');
            }
            try {
                $bytes = stream_get_contents($handle);
            } finally {
                fclose($handle);
            }
            if (! is_string($bytes) || strlen($bytes) !== $doc->byte_size || ! hash_equals($doc->checksum, hash('sha256', $bytes))) {
                throw new \RuntimeException('Integrity mismatch');
            }
        } catch (\Throwable $e) {
            DB::transaction(function () use ($actor, $doc, $permission) {
                $fresh = $this->authorizeRead($actor, $doc, $permission);
                $this->event($fresh, 'integrity_incident', $doc);
            }, 1);
            $this->invalid('Document integrity check failed. Contact authorized staff; the original will not be regenerated.');
        }
        DB::transaction(function () use ($actor, $doc, $permission) {
            $fresh = $this->authorizeRead($actor, $doc, $permission);
            $this->event($fresh, 'download_authorized', $doc);
        }, 1);

        return $bytes;
    }

    public function recover(User $actor, int $documentId): void
    {
        DB::transaction(function () use ($actor, $documentId) {
            $doc = DemonstrationDocument::findOrFail($documentId);
            $actor = $this->staff($actor, Application::findOrFail($doc->application_id), 'documents.recover');
            $doc = DemonstrationDocument::lockForUpdate()->findOrFail($documentId);
            if ($doc->state !== 'processing' || $doc->started_at->greaterThan(now()->subSeconds(120))) {
                $this->invalid('Only expired processing attempts may be recovered. Completed originals cannot be retried.');
            }
            $doc->update(['state' => 'failed']);
            $this->event($actor, 'interrupted_attempt_recovered', $doc);
        }, 1);
    }

    public function restore(User $actor, int $documentId, string $backup): void
    {
        $doc = DemonstrationDocument::findOrFail($documentId);
        DB::transaction(fn () => $this->staff($actor, Application::findOrFail($doc->application_id), 'documents.recover'), 1);
        if ($doc->state !== 'completed') {
            $this->invalid('Restore requires a completed original.');
        }
        $check = app(PrivateDemonstrationPdf::class)->inspect($backup);
        if ($check['checksum'] !== $doc->checksum || $check['byte_size'] !== $doc->byte_size || $check['page_count'] !== $doc->page_count) {
            $this->invalid('Only checksum-identical backup bytes may be restored.');
        }
        // Never overwrite an original path, even if corrupt. A missing path may
        // be restored exclusively; corrupt files need separate forensic handling.
        DB::transaction(function () use ($actor, $doc, $backup) {
            $actor = $this->staff($actor, Application::findOrFail($doc->application_id), 'documents.recover');
            DemonstrationDocument::whereKey($doc->id)->lockForUpdate()->firstOrFail();
            $path = storage_path('app/private/'.$doc->storage_key);
            if (is_file($path)) {
                $this->invalid('Restore never overwrites existing bytes. Inspect the integrity incident first.');
            }
            $in = fopen($backup, 'rb');
            $out = fopen($path, 'xb');
            if (! $in || ! $out) {
                $this->invalid('Restore never overwrites existing bytes. Inspect the integrity incident first.');
            }
            try {
                if (stream_copy_to_stream($in, $out) !== $doc->byte_size || ! fflush($out) || hash_file('sha256', $path) !== $doc->checksum) {
                    throw new \RuntimeException('Restore failed');
                }
            } finally {
                fclose($in);
                fclose($out);
            }
            $this->event($actor, 'identical_backup_restored', $doc);
        }, 1);
    }

    public function event(User $actor, string $event, ?DemonstrationDocument $doc = null, ?DemonstrationDocumentVersion $version = null): void
    {
        DB::table('demonstration_document_history')->insert(['document_id' => $doc?->id, 'version_id' => $version?->id, 'actor_id' => $actor->id, 'event' => $event, 'attempt_key' => $doc?->attempt_key, 'created_at' => now()]);
        app(AuditRecorder::class)->record('demonstration_pdf.'.$event, 'success', $actor, $doc ?? $version, $doc?->region_id, $doc?->institution_id, ['request_id' => $doc?->request_id, 'submission_id' => $doc?->submission_id, 'template_id' => $doc?->template_id, 'personnel_id' => $doc?->personnel_id]);
    }

    private function hash(array $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }

    private function invalid(string $message): never
    {
        throw ValidationException::withMessages(['document' => $message]);
    }
}
