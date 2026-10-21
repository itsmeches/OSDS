<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

require __DIR__.'/Bootstrap-FindingAtomicity.php';
if (Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_21_600000_create_demo_office_directory_revisions.php', '--force' => true]) !== 0) {
    throw new RuntimeException('Isolated directory migration failed.');
}
if (Artisan::call('migrate', ['--path' => 'database/migrations/2026_10_21_700000_create_demo_issuance_form_revisions.php', '--force' => true]) !== 0) {
    throw new RuntimeException('Isolated form migration failed.');
}
Permission::findOrCreate('forms.downloads.manage', 'web');
$suffix = Str::lower(Str::random(8));
$actor = User::factory()->create(['name' => 'Synthetic document preview manager', 'email' => 'demo-document-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
$actor->givePermissionTo(['platform.admin.access', 'forms.downloads.manage']);
$applicant = User::factory()->create(['name' => 'Synthetic issuance form applicant', 'email' => 'demo-issuance-applicant-'.$suffix.'@example.invalid', 'password' => 'UiBrowserOnly-2026!', 'is_synthetic' => true]);
$applicant->givePermissionTo('applicant.portal.access');
echo json_encode(['email' => $actor->email, 'applicant' => $applicant->email, 'suffix' => $suffix], JSON_THROW_ON_ERROR);
