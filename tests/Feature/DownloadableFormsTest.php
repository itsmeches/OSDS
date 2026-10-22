<?php

namespace Tests\Feature;

use App\Filament\Pages\DownloadableForms;
use App\Models\AuditEvent;
use App\Models\DownloadableForm;
use App\Models\DownloadableFormVersion;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Services\Downloads\DownloadableFormService;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class DownloadableFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
        Storage::fake('local');
    }

    private function manager(): User
    {
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->assignRole('OSDS');
        $this->actingAs($actor);

        return $actor;
    }

    public function test_multipart_zip_route_imports_original_five_forms_without_livewire_temporary_state(): void
    {
        $this->manager();
        $before = AuditEvent::count();
        $archive = UploadedFile::fake()->createWithContent('forms.zip', file_get_contents(base_path('docs/source-artifacts/downloadable-forms/original-downloadable-forms.zip')));
        $this->post(route('forms.import-zip'), ['archive' => $archive, 'archiveVersion' => 'Native upload'])
            ->assertRedirect(DownloadableForms::getUrl())->assertSessionHas('zip_import_success', '5 forms imported');
        $this->assertSame(5, DownloadableForm::count());
        $this->assertSame($before + 5, AuditEvent::count());
        foreach (DownloadableForm::with('currentVersion')->get() as $form) {
            Storage::disk('local')->assertExists($form->currentVersion->path);
            $this->assertSame('Native upload', $form->currentVersion->version_label);
        }
    }

    public function test_multipart_zip_route_rejects_missing_fields_and_non_manager(): void
    {
        $this->manager();
        $this->post(route('forms.import-zip'))->assertSessionHasErrors(['archive', 'archiveVersion']);
        $reader = User::factory()->create(['is_synthetic' => true]);
        $reader->assignRole('CHEDRO');
        $this->actingAs($reader)->post(route('forms.import-zip'))->assertForbidden();
        $this->assertSame(0, DownloadableForm::count());
    }

    public function test_multipart_zip_route_audit_failure_rolls_back_and_returns_redacted_feedback(): void
    {
        $this->manager();
        $before = AuditEvent::count();
        $recorder = new AuditRecorder;
        $this->mock(AuditRecorder::class, function ($mock) use ($recorder): void {
            $mock->shouldReceive('record')->once()->andReturnUsing(function (...$arguments) use ($recorder) {
                $recorder->record(...$arguments);
                throw new RuntimeException('private ZIP fault marker');
            });
        });
        $archive = UploadedFile::fake()->createWithContent('forms.zip', file_get_contents(base_path('docs/source-artifacts/downloadable-forms/original-downloadable-forms.zip')));
        $this->post(route('forms.import-zip'), ['archive' => $archive, 'archiveVersion' => 'Retain this label'])
            ->assertRedirect(DownloadableForms::getUrl())->assertSessionHasErrors('archive')->assertSessionHasInput('archiveVersion', 'Retain this label');
        $this->assertStringNotContainsString('private ZIP fault marker', session('errors')->first('archive'));
        $this->assertSame(0, DownloadableForm::count());
        $this->assertSame($before, AuditEvent::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_zip_import_action_reports_missing_file_and_version_without_persistence(): void
    {
        $actor = $this->manager();
        $before = AuditEvent::count();
        Livewire::actingAs($actor)->test(DownloadableForms::class)->call('importArchive')->assertHasErrors(['archive', 'archiveVersion']);
        $archive = UploadedFile::fake()->createWithContent('forms.zip', file_get_contents(base_path('docs/source-artifacts/downloadable-forms/original-downloadable-forms.zip')));
        Livewire::actingAs($actor)->test(DownloadableForms::class)->set('archive', $archive)->call('importArchive')->assertHasErrors('archiveVersion');
        $this->assertSame(0, DownloadableForm::count());
        $this->assertSame($before, AuditEvent::count());
    }

    public function test_actual_page_imports_original_zip_as_five_separate_downloads(): void
    {
        $actor = $this->manager();
        $archive = UploadedFile::fake()->createWithContent('forms.zip', file_get_contents(base_path('docs/source-artifacts/downloadable-forms/original-downloadable-forms.zip')));
        Livewire::actingAs($actor)->test(DownloadableForms::class)
            ->set('archiveVersion', 'Source forms')->set('archive', $archive)->call('importArchive')->assertHasNoErrors();
        $this->assertSame(5, DownloadableForm::count());
        $this->assertSame(5, AuditEvent::where('action', 'downloadable_form.published')->count());
        foreach (DownloadableForm::with('currentVersion')->get() as $form) {
            $this->assertNull($form->module_id);
            $this->assertSame($actor->id, $form->currentVersion->uploaded_by);
            Storage::disk('local')->assertExists($form->currentVersion->path);
        }
    }

    public function test_zip_import_rolls_back_earlier_files_when_later_file_is_invalid(): void
    {
        $actor = $this->manager();
        $path = tempnam(sys_get_temp_dir(), 'osds-test-');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $pdf = $this->pdf();
        $zip->addFromString('first.pdf', file_get_contents($pdf->getRealPath()));
        $zip->addFromString('bad.docx', 'not an Office file');
        $zip->close();
        $before = AuditEvent::count();
        try {
            app(DownloadableFormService::class)->importZip($actor, new UploadedFile($path, 'forms.zip', null, null, true), 'v1');
            $this->fail('Invalid package accepted');
        } catch (ValidationException) {
            $this->assertSame(0, DownloadableForm::count());
            $this->assertSame($before, AuditEvent::count());
            $this->assertSame([], Storage::disk('local')->allFiles());
        } finally {
            unlink($path);
        }
    }

    public function test_zip_import_audit_failure_rolls_back_the_entire_batch_and_bytes(): void
    {
        $actor = $this->manager();
        $recorder = new AuditRecorder;
        $calls = 0;
        $this->mock(AuditRecorder::class, function ($mock) use ($recorder, &$calls): void {
            $mock->shouldReceive('record')->twice()->andReturnUsing(function (...$arguments) use ($recorder, &$calls) {
                $audit = $recorder->record(...$arguments);
                if (++$calls === 2) {
                    throw new RuntimeException('isolated ZIP audit failure');
                }

                return $audit;
            });
        });
        $before = AuditEvent::count();
        try {
            app(DownloadableFormService::class)->importZip($actor, new UploadedFile(base_path('docs/source-artifacts/downloadable-forms/original-downloadable-forms.zip'), 'forms.zip', null, null, true), 'v1');
            $this->fail('Audit failure was ignored');
        } catch (RuntimeException $error) {
            $this->assertSame('isolated ZIP audit failure', $error->getMessage());
            $this->assertSame(0, DownloadableForm::count());
            $this->assertSame(0, DownloadableFormVersion::count());
            $this->assertSame($before, AuditEvent::count());
            $this->assertSame([], Storage::disk('local')->allFiles());
        }
    }

    public function test_zip_import_rejects_traversal_and_read_only_staff(): void
    {
        $actor = $this->manager();
        $path = tempnam(sys_get_temp_dir(), 'osds-test-');
        $zip = new \ZipArchive;
        $zip->open($path, \ZipArchive::OVERWRITE);
        $zip->addFromString('../escape.pdf', '%PDF-1.4');
        $zip->close();
        try {
            app(DownloadableFormService::class)->importZip($actor, new UploadedFile($path, 'forms.zip', null, null, true), 'v1');
            $this->fail('Unsafe path accepted');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('archive', $error->errors());
            $this->assertSame(0, DownloadableForm::count());
        } finally {
            unlink($path);
        }
        $viewer = User::factory()->create(['is_synthetic' => true]);
        $viewer->assignRole('CHEDRO');
        Livewire::actingAs($viewer)->test(DownloadableForms::class)->call('importArchive')->assertForbidden();
    }

    private function pdf(string $name = 'blank.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");
    }

    private function attributes(): array
    {
        return ['title' => 'Blank undertaking', 'description' => 'Synthetic blank form', 'module_id' => null, 'version_label' => 'October 2026'];
    }

    private function publish(User $actor): DownloadableForm
    {
        return app(DownloadableFormService::class)->publish($actor, $this->attributes(), $this->pdf());
    }

    public function test_actual_staff_page_publishes_replaces_and_withdraws_with_retained_versions(): void
    {
        $actor = $this->manager();
        $page = Livewire::actingAs($actor)->test(DownloadableForms::class)
            ->set('formTitle', 'Blank undertaking')->set('versionLabel', 'v1')->set('upload', $this->pdf())->call('save')->assertHasNoErrors();
        $form = DownloadableForm::sole();
        $first = $form->currentVersion;
        Storage::disk('local')->assertExists($first->path);
        $this->assertSame($actor->id, $first->uploaded_by);
        $this->assertSame(1, AuditEvent::where('action', 'downloadable_form.published')->count());
        $page->call('edit', $form->id)->set('versionLabel', 'v2')->set('upload', $this->pdf('replacement.pdf'))->call('save')->assertHasNoErrors();
        $form->refresh();
        $this->assertSame(2, $form->versions()->count());
        $this->assertNotSame($first->id, $form->current_version_id);
        Storage::disk('local')->assertExists($first->path);
        $this->get(route('forms.download', [$form->public_id, $first->id]))->assertNotFound();
        $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertOk()->assertDownload('replacement.pdf');
        $page->call('withdraw', $form->id, $form->current_version_id)->assertHasNoErrors();
        $this->assertNotNull($form->fresh()->withdrawn_at);
        $this->assertSame(2, DownloadableFormVersion::count());
        $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertNotFound();
        $this->assertSame(1, AuditEvent::where('action', 'downloadable_form.withdrawn')->count());
    }

    public function test_applicant_reads_current_downloads_but_cannot_manage_and_guests_cannot_download(): void
    {
        $form = $this->publish($this->manager());
        $applicant = User::factory()->create(['is_synthetic' => true]);
        $applicant->givePermissionTo('applicant.portal.access');
        $this->actingAs($applicant)->get(route('applicant.forms'))->assertOk()->assertSee('Blank undertaking');
        $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        Livewire::actingAs($applicant)->test(DownloadableForms::class)->assertForbidden();
        auth()->logout();
        $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertRedirect();
    }

    public static function auditFaults(): array
    {
        return [['before'], ['after'], ['database']];
    }

    #[DataProvider('auditFaults')]
    public function test_publish_audit_failures_roll_back_database_and_remove_only_staged_file(string $fault): void
    {
        $actor = $this->manager();
        $count = AuditEvent::count();
        $depth = DB::transactionLevel();
        $sink = Mockery::mock(AuditRecorder::class);
        $sink->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($fault, $depth) {
            $this->assertSame($depth + 1, DB::transactionLevel());
            if ($fault === 'database') {
                $invalid = new User;
                $invalid->id = -1;
                $args[2] = $invalid;
            }
            if ($fault !== 'before') {
                (new AuditRecorder)->record(...$args);
            }
            throw new RuntimeException('ISOLATED_DOWNLOAD_FAULT');
        });
        $this->app->instance(AuditRecorder::class, $sink);
        Livewire::actingAs($actor)->test(DownloadableForms::class)->set('formTitle', 'Faulted form')
            ->set('versionLabel', 'v1')->set('upload', $this->pdf())->call('save')
            ->assertHasErrors(['upload'])->assertSet('formTitle', 'Faulted form')->assertNotified('Action could not be confirmed');
        $this->assertSame(0, DownloadableForm::count());
        $this->assertSame(0, DownloadableFormVersion::count());
        $this->assertSame($count, AuditEvent::count());
        $this->assertSame([], Storage::disk('local')->allFiles('downloadable-forms'));
    }

    public function test_failed_replacement_or_withdrawal_retains_old_current_file_and_audit(): void
    {
        $actor = $this->manager();
        $form = $this->publish($actor);
        $version = $form->current_version_id;
        $path = $form->currentVersion->path;
        $count = AuditEvent::count();
        $sink = Mockery::mock(AuditRecorder::class);
        $sink->shouldReceive('record')->twice()->andReturnUsing(function (...$args) {
            (new AuditRecorder)->record(...$args);
            throw new RuntimeException('ISOLATED_DOWNLOAD_AFTER');
        });
        $this->app->instance(AuditRecorder::class, $sink);
        foreach (['replace', 'withdraw'] as $operation) {
            try {
                $service = app(DownloadableFormService::class);
                $operation === 'replace' ? $service->publish($actor, $this->attributes(), $this->pdf(), $form->id, $version)
                    : $service->withdraw($actor, $form->id, $version);
                $this->fail('Expected isolated audit failure');
            } catch (RuntimeException $error) {
                $this->assertSame('ISOLATED_DOWNLOAD_AFTER', $error->getMessage());
            }
        }
        $this->assertSame($version, $form->fresh()->current_version_id);
        $this->assertNull($form->fresh()->withdrawn_at);
        $this->assertSame(1, DownloadableFormVersion::count());
        $this->assertSame($count, AuditEvent::count());
        $this->assertSame([$path], Storage::disk('local')->allFiles('downloadable-forms'));
    }

    public function test_permission_loss_and_inactive_actor_after_mount_are_denied_without_upload(): void
    {
        $actor = $this->manager();
        $page = Livewire::actingAs($actor)->test(DownloadableForms::class)->set('formTitle', 'No save')->set('versionLabel', 'v1')->set('upload', $this->pdf());
        $actor->removeRole('OSDS');
        $page->call('save')->assertForbidden();
        $this->assertSame(0, DownloadableForm::count());
        $actor->givePermissionTo(['platform.admin.access', 'forms.downloads.manage']);
        User::whereKey($actor->id)->update(['status' => 'inactive']);
        $this->assertFalse(app(DownloadableFormService::class)->canRead($actor));
        $this->assertSame([], Storage::disk('local')->allFiles('downloadable-forms'));
    }

    public function test_stale_replacement_does_not_overwrite_current_and_withdrawal_is_repeat_safe(): void
    {
        $actor = $this->manager();
        $form = $this->publish($actor);
        $original = $form->current_version_id;
        $service = app(DownloadableFormService::class);
        $new = $service->publish($actor, $this->attributes(), $this->pdf(), $form->id, $original);
        try {
            $service->publish($actor, $this->attributes(), $this->pdf(), $form->id, $original);
            $this->fail('Expected stale version denial');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('title', $error->errors());
        }
        $this->assertSame($new->current_version_id, $form->fresh()->current_version_id);
        $this->assertSame(2, count(Storage::disk('local')->allFiles('downloadable-forms')));
        $service->withdraw($actor, $form->id, $new->current_version_id);
        $service->withdraw($actor, $form->id, $new->current_version_id);
        $this->assertSame(1, AuditEvent::where('action', 'downloadable_form.withdrawn')->count());
    }

    public function test_invalid_upload_metadata_and_unauthorized_service_cannot_publish(): void
    {
        $actor = $this->manager();
        foreach (['blank.exe', 'blank.docx', 'blank.xlsx', 'blank.pdf'] as $name) {
            try {
                app(DownloadableFormService::class)->publish($actor, $this->attributes(), UploadedFile::fake()->createWithContent($name, 'not an office file'));
                $this->fail('Expected invalid content rejection');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('upload', $error->errors());
            }
        }
        $other = User::factory()->create(['is_synthetic' => true]);
        try {
            app(DownloadableFormService::class)->publish($other, $this->attributes(), $this->pdf());
            $this->fail('Expected unauthorized actor');
        } catch (AuthorizationException) {
            $this->assertSame(0, DownloadableForm::count());
        }
        $this->assertSame([], Storage::disk('local')->allFiles('downloadable-forms'));
    }

    public function test_original_office_forms_are_accepted_and_forged_identity_fields_are_ignored(): void
    {
        $actor = $this->manager();
        foreach (['CERTIFICATE OF UNDERTAKING.docx', 'LIST OF MEMBER HEIs.xlsx'] as $name) {
            $file = new UploadedFile(base_path('docs/source-artifacts/downloadable-forms/'.$name), $name, null, null, true);
            $form = app(DownloadableFormService::class)->publish($actor, $this->attributes() + ['id' => -1, 'uploaded_by' => -1, 'current_version_id' => -1], $file);
            $this->assertSame($actor->id, $form->currentVersion->uploaded_by);
            $this->assertSame(hash_file('sha256', $file->getRealPath()), $form->currentVersion->sha256);
            $this->assertSame($name, $form->currentVersion->original_name);
        }
        $this->assertSame(2, DownloadableFormVersion::count());
    }

    public function test_withdrawn_forms_hidden_and_replacement_republishes_without_exposing_old_versions(): void
    {
        $actor = $this->manager();
        $form = $this->publish($actor);
        $service = app(DownloadableFormService::class);
        $service->withdraw($actor, $form->id, $form->current_version_id);
        $reader = User::factory()->create(['is_synthetic' => true]);
        $reader->givePermissionTo('applicant.portal.access');
        $this->actingAs($reader)->get(route('applicant.forms'))->assertOk()->assertDontSee('Blank undertaking');
        $this->actingAs($actor);
        $replacement = $service->publish($actor, $this->attributes(), $this->pdf(), $form->id, $form->current_version_id);
        $this->assertNull($replacement->withdrawn_at);
        $this->actingAs($reader)->get(route('applicant.forms'))->assertSee('Blank undertaking');
        $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertNotFound();
    }

    public function test_database_rejects_historical_version_mutation_and_cross_form_binding(): void
    {
        $actor = $this->manager();
        $first = $this->publish($actor);
        $second = $this->publish($actor);
        foreach (['version', 'binding'] as $attempt) {
            try {
                DB::transaction(function () use ($first, $second, $attempt) {
                    if ($attempt === 'version') {
                        $first->currentVersion->update(['version_label' => 'forged']);
                    } else {
                        $first->update(['current_version_id' => $second->current_version_id]);
                    }
                });
                $this->fail('Database guard should reject mutation');
            } catch (QueryException) {
                $this->assertSame($first->currentVersion->id, $first->fresh()->current_version_id);
            }
        }
    }

    public function test_production_and_unrelated_accounts_cannot_download(): void
    {
        $actor = $this->manager();
        $form = $this->publish($actor);
        $other = User::factory()->create(['is_synthetic' => true]);
        $this->actingAs($other)->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertNotFound();
        $this->actingAs($actor);
        $this->app->detectEnvironment(fn () => 'production');
        $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertNotFound();
        $this->assertFalse(app(DownloadableFormService::class)->canManage($actor));
    }

    public function test_unprivileged_staff_cannot_see_manage_or_download_forms(): void
    {
        $form = $this->publish($this->manager());
        foreach (['Admin'] as $role) {
            $staff = User::factory()->create(['is_synthetic' => true]);
            $staff->assignRole($role);
            $this->assertFalse(app(DownloadableFormService::class)->canRead($staff));
            $this->actingAs($staff)->get('/admin/downloadable-forms')->assertForbidden();
            $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertNotFound();
            Livewire::actingAs($staff)->test(DownloadableForms::class)->assertForbidden();
        }
    }

    public function test_chedro_can_view_and_download_but_all_management_actions_are_denied(): void
    {
        $form = $this->publish($this->manager());
        $reader = User::factory()->create(['is_synthetic' => true]);
        $reader->assignRole('CHEDRO');
        $service = app(DownloadableFormService::class);
        $this->assertTrue($service->canRead($reader));
        $this->assertFalse($service->canManage($reader));
        $count = AuditEvent::count();
        $this->actingAs($reader)->get('/admin/downloadable-forms')->assertOk()
            ->assertSee('Blank undertaking')->assertDontSee('Publish form')->assertDontSee('Withdraw Blank undertaking');
        $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertOk();
        Livewire::actingAs($reader)->test(DownloadableForms::class)->call('edit', $form->id)->assertForbidden();
        Livewire::actingAs($reader)->test(DownloadableForms::class)->set('formTitle', 'Forged edit')->call('save')->assertForbidden();
        Livewire::actingAs($reader)->test(DownloadableForms::class)->call('withdraw', $form->id, $form->current_version_id)->assertForbidden();
        $this->assertSame('Blank undertaking', $form->fresh()->title);
        $this->assertNull($form->fresh()->withdrawn_at);
        $this->assertSame(1, $form->versions()->count());
        $this->assertSame($count, AuditEvent::count());
        $reader->removeRole('CHEDRO');
        $reader->givePermissionTo('platform.admin.access');
        $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertNotFound();
    }

    public function test_existing_local_app_does_not_require_test_database_name_for_blank_downloads(): void
    {
        $actor = $this->manager();
        $form = $this->publish($actor);
        $this->app->detectEnvironment(fn () => 'local');
        // Only configuration is changed; the real connection remains osds_test.
        config(['database.connections.pgsql.database' => 'osds_dev']);
        $this->assertTrue(app(DownloadableFormService::class)->canManage($actor));
        $this->get(route('forms.download', [$form->public_id, $form->current_version_id]))->assertOk();
        config(['database.connections.pgsql.database' => 'osds_test']);
    }
}
