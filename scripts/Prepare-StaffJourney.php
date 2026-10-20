<?php

use App\Models\ApplicantSandboxEligibilityGrant;
use App\Models\ConfigurationReviewRequest;
use App\Models\FormVersion;
use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\NationwideAccessGrant;
use App\Models\PublicationManifest;
use App\Models\Region;
use App\Models\RequirementSetVersion;
use App\Models\SyntheticApplicationReviewerAssignment;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Services\Authorization\ScopeAuthorizer;
use App\Services\Questionnaires\QuestionnaireAuthoringService;
use App\Services\Questionnaires\QuestionnaireDraftService;
use App\Services\Questionnaires\QuestionnairePublicationService;
use App\Support\AccountRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$staffAction = $argv[1] ?? 'prepare';
$argv[1] = $staffAction === 'prepare' ? 'prepare' : 'inspect';
// Existing bootstrap refuses cached configuration and every database other
// than osds_ui_browser_test. It creates new pairs; it does not reseed/reset.
require __DIR__.'/Prepare-BuilderJourney.php';
$staffFile = storage_path('app/private/staff-journey.json');
$publisher = app(QuestionnairePublicationService::class);
if ($staffAction === 'prepare') {
    $form = FormVersion::findOrFail($fixture['form']);
    $requirements = RequirementSetVersion::findOrFail($fixture['requirements']);
    $drafts = app(QuestionnaireDraftService::class);
    $otherForm = $drafts->cloneForm($osds, $form);
    $otherRequirements = $drafts->cloneRequirements($osds, $requirements);
    $review = $publisher->submitForReview($osds, $form, $requirements);
    $returned = $publisher->submitForReview($osds, $otherForm, $otherRequirements);
    foreach (PublicationManifest::where('module_id', $requirements->module_id)->whereDoesntHave('retirement')->get() as $old) {
        $publisher->retire($admin, $old, 'Prepare isolated staff browser journey');
    }
    $suffix = Str::lower(Str::random(8));
    $region = Region::create(['code' => 'F-'.$suffix, 'name' => 'Staff browser region '.$suffix]);
    $staffFixture = ['review' => $review->id, 'returned' => $returned->id, 'form' => $form->id, 'requirements' => $requirements->id, 'region' => $region->id, 'region_name' => $region->name];
    foreach (['super' => AccountRole::SuperUser, 'creator' => AccountRole::Chedro, 'verifier' => AccountRole::Chedro, 'overlap' => AccountRole::Admin] as $key => $role) {
        $user = User::factory()->create(['name' => 'Synthetic staff '.$key, 'email' => 'staff-f-'.$key.'-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
        $user->assignRole($role->value);
        if ($key === 'overlap') {
            $user->givePermissionTo('accounts.create.school');
        }
        UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $user->id, 'region_id' => $region->id, 'starts_at' => now()->subDay(), 'granted_by' => $admin->id]));
        $staffFixture[$key] = ['id' => $user->id, 'email' => $user->email];
    }
    NationwideAccessGrant::create(['user_id' => $staffFixture['super']['id'], 'granted_by' => $admin->id, 'starts_at' => now()->subDay(), 'reason' => 'Explicit isolated nationwide fixture']);
    $organization = Institution::create(['name' => 'Synthetic staff school '.$suffix, 'primary_region_id' => $region->id, 'is_synthetic' => true]);
    $staffFixture['organization'] = ['id' => $organization->id, 'name' => $organization->name];
    $staffFixture['school_email'] = 'staff-f-school-'.$suffix.'@example.invalid';
    foreach (['scheduled', 'expired'] as $state) {
        $target = User::factory()->create(['name' => 'F '.$state.' target '.$suffix, 'is_synthetic' => true]);
        $target->assignRole(AccountRole::Osds->value);
        UserRegionAssignment::withoutEvents(fn () => UserRegionAssignment::create(['user_id' => $target->id, 'region_id' => $region->id, 'granted_by' => $admin->id, 'starts_at' => $state === 'scheduled' ? now()->addDay() : now()->subDays(3), 'ends_at' => $state === 'scheduled' ? now()->addDays(3) : now()->subDay()]));
    }
    foreach ([SyntheticApplicationReviewerAssignment::class, SyntheticEvidenceReviewerAssignment::class] as $assignmentClass) {
        $assignmentClass::create(['reviewer_id' => $staffFixture['verifier']['id'], 'region_id' => $region->id, 'module_id' => $requirements->module_id, 'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2), 'granted_by' => $admin->id, 'reason' => 'Synthetic scheduled fixture']);
    }
    ApplicantSandboxEligibilityGrant::create(['institution_id' => $organization->id, 'module_id' => $requirements->module_id, 'starts_at' => now()->addDay(), 'granted_by' => $admin->id, 'reason' => 'Synthetic scheduled fixture']);
} else {
    $staffFixture = json_decode(file_get_contents($staffFile), true, flags: JSON_THROW_ON_ERROR);
}
if ($staffAction === 'mutate-returned') {
    $request = ConfigurationReviewRequest::findOrFail($staffFixture['returned']);
    if ($request->state !== 'rejected') {
        throw new RuntimeException('Returned draft required.');
    }
    $version = $request->formVersion;
    $docs = $request->requirementSetVersion;
    app(QuestionnaireAuthoringService::class)->save($osds, $version, $docs, [
        'title' => $version->definition->name,
        'sections' => $version->sections->map(fn ($s): array => ['key' => $s->key, 'title' => $s->title, 'description' => $s->description ?? '', 'questions' => $s->questions->map(fn ($q): array => [
            'key' => $q->key, 'label' => $q->key === 'source' ? 'Later returned draft browser marker' : $q->label, 'help_text' => $q->help_text ?? '', 'type' => $q->type, 'is_required' => $q->is_required, 'validation_rules' => $q->validation_rules ?? [], 'condition_rules' => $q->condition_rules,
            'options' => $q->options->map(fn ($o): array => ['key' => $o->key, 'label' => $o->label])->all(),
        ])->all()])->all(),
        'requirements' => $docs->requirements->map(fn ($d): array => ['key' => $d->key, 'label' => $d->label, 'instructions' => $d->instructions ?? '', 'is_required' => $d->is_required, 'minimum_evidence' => $d->minimum_evidence, 'maximum_evidence' => $d->maximum_evidence, 'condition_rules' => $d->condition_rules, 'provider_ids' => $d->providers->pluck('id')->all()])->all(),
    ], $version->lock_version, $docs->lock_version);
}
$request = ConfigurationReviewRequest::findOrFail($staffFixture['review']);
$staffFixture['review_state'] = $request->state;
$staffFixture['approval_count'] = $request->approvals()->count();
$staffFixture['publications'] = PublicationManifest::whereIn('configuration_review_approval_id', $request->approvals()->pluck('id'))->get()->map(fn ($m): array => ['id' => $m->id, 'channel' => $m->channel, 'form' => $m->form_version_id, 'requirements' => $m->requirement_set_version_id, 'retired' => $m->retirement !== null])->all();
$school = User::where('email', $staffFixture['school_email'])->first();
$staffFixture['school'] = $school ? ['id' => $school->id, 'status' => $school->status, 'verified_by' => InstitutionRepresentative::where('user_id', $school->id)->value('verified_by')] : null;
if ($staffAction === 'inspect-account-type') {
    $email = $argv[2] ?? '';
    if (! str_starts_with($email, 'staff-f-race-') || ! str_ends_with($email, '@example.invalid')) {
        throw new RuntimeException('Isolated account marker required.');
    }
    $created = User::where('email', $email)->first();
    $staffFixture['account_type_probe'] = $created ? ['roles' => $created->getRoleNames()->all(), 'status' => $created->status] : null;
}
if ($staffAction === 'metrics') {
    $measure = function (bool $eager): array {
        DB::enableQueryLog();
        DB::flushQueryLog();
        $started = hrtime(true);
        $rows = PublicationManifest::with($eager ? ['module', 'retirement'] : ['module'])->latest('id')->limit(10)->get();
        foreach ($rows as $row) {
            for ($i = 0; $i < 3; $i++) {
                $eager ? $row->retirement !== null : $row->retirement()->exists();
            }
        }
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        return ['rows' => $rows->count(), 'queries' => count($log), 'query_ms' => array_sum(array_column($log, 'time')), 'elapsed_ms' => round((hrtime(true) - $started) / 1e6, 3)];
    };
    $staffFixture['publication_lookup_metrics'] = ['previous_access_pattern' => $measure(false), 'current_access_pattern' => $measure(true)];
    $selectorMeasure = function (User $actor, bool $sql): array {
        $scope = app(ScopeAuthorizer::class);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $started = hrtime(true);
        $ids = $sql
            ? Region::whereIn('id', $scope->effectiveRegionIds($actor))->orderBy('name')->pluck('id')->all()
            : Region::orderBy('name')->get()->filter(fn ($region): bool => $scope->canAccessRegion($actor, $region->id))->pluck('id')->all();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        return ['ids' => $ids, 'queries' => count($queries), 'query_ms' => array_sum(array_column($queries, 'time')), 'elapsed_ms' => round((hrtime(true) - $started) / 1e6, 3)];
    };
    foreach (['overlap', 'super'] as $identity) {
        $actor = User::findOrFail($staffFixture[$identity]['id']);
        $before = $selectorMeasure($actor, false);
        $after = $selectorMeasure($actor, true);
        if ($before['ids'] !== $after['ids']) {
            throw new RuntimeException('Scope mismatch in isolated selector measurement.');
        }
        $allowed = count($after['ids']);
        unset($before['ids'], $after['ids']);
        $staffFixture['region_selector_metrics'][$identity] = ['allowed_count' => $allowed, 'same_options' => true, 'previous_access_pattern' => $before, 'current_access_pattern' => $after];
    }
}
file_put_contents($staffFile, json_encode($staffFixture, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
echo "Isolated staff fixture completed.\n";
