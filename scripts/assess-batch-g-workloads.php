<?php

use App\Filament\Pages\SyntheticApplicationIntake;
use App\Filament\Pages\SyntheticEvidenceReviews;
use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\Application;
use App\Models\DocumentaryRequirement;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\PublicationManifest;
use App\Models\User;
use App\Services\Applicants\ApplicantDraftService;
use App\Services\Applications\ApplicationLifecycleService;
use App\Services\Evidence\EvidenceReferenceService;
use App\Services\Evidence\SyntheticEvidenceReviewService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
$fixture = json_decode(file_get_contents(storage_path('app/private/chedro-journey.json')), true, flags: JSON_THROW_ON_ERROR);
$reviewer = User::findOrFail($fixture['reviewerId']);
if (! $reviewer->is_synthetic || ! str_starts_with($reviewer->email, 'chedro-d-')) {
    throw new RuntimeException('Isolated reviewer required.');
}
$manifest = PublicationManifest::findOrFail($fixture['manifest']);
// Resolve from the exact existing fixture application without assuming a relation name.
$region = Application::where('public_id', $fixture['applications'][0])->value('routing_region_id');
$school = User::where('email', 'school@example.invalid')->firstOrFail();
$admin = User::where('email', 'admin@example.invalid')->firstOrFail();
$report = ['database' => DB::connection()->getDatabaseName(), 'timezone' => DB::selectOne('SHOW timezone')->TimeZone,
    'method' => 'Domain-created synthetic workload; actual guarded queue/filter builders, page size 10, one warmup then 7 samples per scenario; SQL text/bindings are never exported', 'stages' => []];
$made = 12;
foreach ([12, 36, 60] as $size) {
    auth()->logout();
    for (; $made < $size; $made++) {
        $org = Institution::create(['name' => 'G final workload '.Str::uuid(), 'primary_region_id' => $region, 'is_synthetic' => true]);
        InstitutionRepresentative::create(['institution_id' => $org->id, 'user_id' => $school->id, 'starts_at' => now()->subDay(), 'verified_at' => now(), 'verified_by' => $admin->id]);
        ApplicantSandboxEligibilityGrant::create(['institution_id' => $org->id, 'module_id' => $manifest->module_id, 'granted_by' => $admin->id, 'starts_at' => now()->subDay(), 'reason' => 'Isolated workload']);
        $draft = app(ApplicantDraftService::class)->start($school, $org, $manifest);
        app(ApplicantDraftService::class)->save($school, $draft, ['name' => 'Synthetic workload', 'has_activity' => 'yes', 'details' => 'Synthetic', 'email' => 'synthetic@example.invalid'], 1, (string) Str::uuid());
        app(EvidenceReferenceService::class)->add($school, $draft->fresh(), DocumentaryRequirement::findOrFail($fixture['requirement']), 'https://docs.google.com/document/d/SyntheticWorkloadFile'.str_pad((string) $made, 6, '0', STR_PAD_LEFT).'/edit', 2, (string) Str::uuid());
        app(SyntheticEvidenceReviewService::class)->createCopy($school, $draft->fresh(), 3, (string) Str::uuid());
        app(ApplicationLifecycleService::class)->submit($school, $draft->application, 1, 3, (string) Str::uuid());
    }
    foreach ([SyntheticApplicationIntake::class, SyntheticEvidenceReviews::class] as $class) {
        foreach (['page-one', 'page-two', 'empty-search', 'missing-permission'] as $scenario) {
            $samples = [];
            for ($i = 0; $i < 8; $i++) {
                $actor = $scenario === 'missing-permission' ? User::where('email', $fixture['noPermission'])->firstOrFail() : $reviewer->fresh();
                auth()->login($actor);
                $page = new $class;
                if ($scenario === 'empty-search') {
                    $page->search = 'NoSuchSyntheticOrganizationGFinal';
                }
                DB::enableQueryLog();
                DB::flushQueryLog();
                $start = hrtime(true);
                $query = (new ReflectionMethod($page, 'queueQuery'))->invoke($page);
                $column = $class === SyntheticApplicationIntake::class ? 'routing_region_id' : 'region_id';
                $query = (new ReflectionMethod($page, 'filterQueue'))->invoke($page, $query, $column);
                $rows = $query->orderByDesc('id')->paginate(10, page: $scenario === 'page-two' ? 2 : 1);
                foreach ($rows as $row) {
                    $row->institution->name;
                    $row->module->name;
                    $row->region->name;
                    if ($class === SyntheticApplicationIntake::class) {
                        $row->manifest->module->name;
                    }
                }
                $elapsed = (hrtime(true) - $start) / 1e6;
                $log = DB::getQueryLog();
                DB::disableQueryLog();
                if ($i > 0) {
                    $samples[] = ['queries' => count($log), 'sql_ms' => round(array_sum(array_column($log, 'time')), 3), 'elapsed_ms' => round($elapsed, 3), 'returned' => $rows->count(), 'total' => $rows->total()];
                }
                $expected = in_array($scenario, ['empty-search', 'missing-permission'], true) ? 0 : $size;
                if ($rows->total() !== $expected || $rows->count() > 10) {
                    throw new RuntimeException('Scoped workload count mismatch.');
                }
            }
            $report['stages'][] = ['size' => $size, 'queue' => class_basename($class), 'scenario' => $scenario, 'samples' => $samples];
        }
    }
}
file_put_contents(base_path('docs/screenshots/phase-3d7-g-final/workloads.json'), json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Isolated queue workload measurements completed.\n";
