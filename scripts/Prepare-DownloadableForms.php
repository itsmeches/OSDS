<?php

use App\Models\DownloadableForm;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

require __DIR__.'/Bootstrap-FindingAtomicity.php';

// Bootstrap asserts osds_ui_browser_test before this script does any work.
$mode = $argv[1] ?? 'inspect';
$state = storage_path('app/private/downloadable-forms-browser.json');
if ($mode === 'prepare') {
    Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_21_500000_create_downloadable_forms.php', '--force' => true]);
    Permission::findOrCreate('forms.downloads.manage', 'web');
    Permission::findOrCreate('forms.downloads.view', 'web');
    $suffix = Str::lower(Str::random(8));
    $staff = User::factory()->create(['name' => 'Synthetic forms manager', 'email' => 'forms-staff-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
    $staff->givePermissionTo(['platform.admin.access', 'forms.downloads.manage']);
    $reader = User::factory()->create(['name' => 'Synthetic forms reader', 'email' => 'forms-reader-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
    $reader->givePermissionTo('applicant.portal.access');
    $viewer = User::factory()->create(['name' => 'Synthetic CHEDRO forms viewer', 'email' => 'forms-chedro-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
    $viewer->givePermissionTo(['platform.admin.access', 'forms.downloads.view']);
    file_put_contents($state, json_encode(['staff' => $staff->email, 'reader' => $reader->email, 'viewer' => $viewer->email, 'title' => 'A downloadable form '.$suffix, 'actor_id' => $staff->id], JSON_PRETTY_PRINT));
}
$data = json_decode(file_get_contents($state), true, flags: JSON_THROW_ON_ERROR);
if ($mode === 'revoke') {
    User::findOrFail($data['actor_id'])->revokePermissionTo('forms.downloads.manage');
}
$data['forms'] = DownloadableForm::with('currentVersion')->where('title', $data['title'])->get()->map(fn ($form) => [
    'id' => $form->id, 'public_id' => $form->public_id, 'version' => $form->current_version_id,
    'withdrawn' => $form->withdrawn_at !== null, 'versions' => $form->versions()->count(),
])->all();
echo json_encode($data, JSON_THROW_ON_ERROR);
