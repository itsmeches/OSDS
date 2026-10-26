<?php

namespace Tests\Support;

use App\Models\DemonstrationDocumentVersion;
use App\Models\DemoOfficeDirectoryRevision;
use App\Services\Documents\CaseIssuanceService;
use App\Services\Documents\DemonstrationDocumentService;
use App\Services\Documents\DemoOfficeDirectoryService;
use Illuminate\Support\Str;

class B3Scenario
{
    public static function create(string $module = 'registration', string $note = 'Fictional contact', bool $submit = true): array
    {
        $s = B2Scenario::create($module);
        auth()->login($s['applicant']);
        $request = app(CaseIssuanceService::class)->persist($s['applicant'], $s['case'], $s['definition']->id, 0, (string) Str::uuid(), ['contact_note' => $note], $submit);
        $operator = $s['staff']['evaluator'];
        $operator->givePermissionTo(['documents.view', 'documents.generate', 'documents.download', 'documents.recover']);
        $s['manager']->givePermissionTo(['platform.admin.access', 'forms.downloads.manage', 'documents.templates.manage', 'documents.templates.review', 'documents.templates.publish', 'documents.personnel.manage', 'documents.personnel.review', 'documents.personnel.publish']);
        auth()->login($s['manager']);
        $directory = app(DemoOfficeDirectoryService::class)->save($s['manager'], DemoOfficeDirectoryRevision::max('id'), null, ['label' => 'Fictional region '.$s['region']->id, 'director' => 'Dr. Alex Sample (fictional)', 'designation' => 'Demonstration Director']);
        $officeKey = array_key_last($directory->offices);
        $service = app(DemonstrationDocumentService::class);
        foreach (DemonstrationDocumentVersion::where(['kind' => 'template', 'module_id' => $s['module']->id, 'state' => 'published'])->get() as $old) {
            $service->advanceVersion($s['manager'], $old->id, $old->lock_version, 'withdrawn');
        }
        $template = $service->createVersion($s['manager'], 'template', $s['module']->id, ['wording' => DemonstrationDocumentService::WORDING, 'fields' => ['contact_note']]);
        $personnel = $service->createVersion($s['manager'], 'personnel', $s['module']->id, ['region_id' => $s['region']->id, 'directory_revision_id' => $directory->id, 'office_key' => $officeKey, 'provenance' => 'Reviewed fictional personnel for disposable B3 demonstration.']);
        foreach ([$template, $personnel] as $v) {
            $v = $service->advanceVersion($s['manager'], $v->id, 1, 'reviewed');
            $service->advanceVersion($s['manager'], $v->id, 2, 'published');
        }
        auth()->login($operator);

        return $s + compact('request', 'operator', 'template', 'personnel', 'directory');
    }
}
