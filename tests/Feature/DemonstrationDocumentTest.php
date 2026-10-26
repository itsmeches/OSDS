<?php

namespace Tests\Feature;

use App\Filament\Pages\CaseDemonstrationDocuments;
use App\Models\AuditEvent;
use App\Models\DemonstrationDocument;
use App\Models\DemonstrationDocumentVersion;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Services\Auditing\AuditRecorder;
use App\Services\Documents\DemonstrationDocumentService;
use App\Services\Documents\PrivateDemonstrationPdf;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Tests\Support\B3Scenario;
use Tests\TestCase;

class DemonstrationDocumentTest extends TestCase
{
    use RefreshDatabase { refreshDatabase as private refreshApprovedDatabase; }

    public function refreshDatabase(): void
    {
        if (config('database.default') !== 'pgsql' || DB::connection()->getDatabaseName() !== 'osds_test') {
            throw new \RuntimeException('Approved disposable database required before preparation');
        }
        $this->refreshApprovedDatabase();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        config(['demonstration_documents.node' => sys_get_temp_dir().'/osds-phase1-tools/node/node-v24.20.0-win-x64/node.exe']);
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
    }

    private function generate(array $s): DemonstrationDocument
    {
        $this->actingAs($s['operator']);

        return app(DemonstrationDocumentService::class)->generate($s['operator'], $s['request']->id, $s['template']->id, $s['personnel']->id);
    }

    private function denied(callable $call, string $class = ValidationException::class): void
    {
        try {
            $call();
            $this->fail('Denied operation succeeded');
        } catch (\Throwable $e) {
            $this->assertInstanceOf($class, $e);
        }
    }

    public static function modules(): array
    {
        return [['registration', 'cor'], ['acsa', 'acsa']];
    }

    #[DataProvider('modules')]
    public function test_actual_pdf_and_livewire_generation_immutable_download_for_each_module(string $module, string $type): void
    {
        $s = B3Scenario::create($module, str_repeat('Fictional long contact note. ', 65));
        $this->actingAs($s['operator']);
        $ui = Livewire::test(CaseDemonstrationDocuments::class)->set('templateIds.'.$s['request']->id, $s['template']->id)->set('personnelIds.'.$s['request']->id, $s['personnel']->id)->call('generate', $s['request']->id)->assertHasNoErrors();
        $doc = DemonstrationDocument::sole();
        $this->assertSame('completed', $doc->state);
        $this->assertSame($type, $doc->certificate_type);
        $this->assertGreaterThanOrEqual(2, $doc->page_count);
        $this->assertSame($s['request']->fresh()->identity_snapshot, $doc->source_snapshot['identity']);
        $this->assertSame($s['case']->final_decision_id, $doc->decision_id);
        $bytes = app(DemonstrationDocumentService::class)->verifiedBytes($s['operator'], $doc);
        $this->assertSame($doc->checksum, hash('sha256', $bytes));
        $check = app(PrivateDemonstrationPdf::class)->inspect(storage_path('app/private/'.$doc->storage_key));
        $this->assertSame($doc->page_count, $check['page_count']);
        $this->assertSame($doc->id, $this->generate($s)->id);
        $this->assertSame(1, DemonstrationDocument::count());
        $this->actingAs($s['applicant'])->get(route('documents.b3.download', $doc->public_id))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertContent($bytes);
        $this->actingAs($s['manager']);
        foreach ([$s['template'], $s['personnel']] as $v) {
            app(DemonstrationDocumentService::class)->advanceVersion($s['manager'], $v->id, 3, 'withdrawn');
        }
        $this->actingAs($s['operator']);
        $this->assertSame($bytes, app(DemonstrationDocumentService::class)->verifiedBytes($s['operator'], $doc->fresh()));
        $this->denied(fn () => DB::transaction(fn () => DB::table('demonstration_documents')->where('id', $doc->id)->update(['checksum' => str_repeat('0', 64)])), QueryException::class);
        $this->assertSame($doc->checksum, $doc->fresh()->checksum);
    }

    public function test_actual_template_review_publication_preview_and_personnel_snapshot(): void
    {
        $s = B3Scenario::create();
        Livewire::actingAs($s['manager'])->test(CaseDemonstrationDocuments::class)->set('moduleId', $s['module']->id)->set('wording', 'Fictional reference text')->call('createTemplate')->assertHasNoErrors();
        $v = DemonstrationDocumentVersion::latest('id')->first();
        $ui = Livewire::test(CaseDemonstrationDocuments::class)->call('advance', $v->id, 1, 'reviewed')->assertHasNoErrors();
        $this->get(route('documents.b3.preview', $v->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($s['directory']->offices[$s['personnel']->content['office_key']], $s['personnel']->content['office']);
        $this->denied(fn () => app(DemonstrationDocumentService::class)->createVersion($s['manager'], 'template', $s['module']->id, ['wording' => '<script>bad</script>', 'fields' => []]));
        $this->denied(fn () => DB::transaction(fn () => DB::table('demonstration_document_versions')->where('id', $v->id)->update(['content' => '{}'])), QueryException::class);
    }

    public function test_staff_permission_and_wrong_region_and_module_denials(): void
    {
        $s = B3Scenario::create();
        $s['operator']->revokePermissionTo('documents.generate');
        $this->denied(fn () => $this->generate($s), AuthorizationException::class);
        $s['operator']->givePermissionTo('documents.generate');
        DB::table('synthetic_application_reviewer_assignments')->where('reviewer_id', $s['operator']->id)->update(['revoked_at' => now(), 'revoked_by' => $s['publisher']->id, 'revocation_reason' => 'Disposable B3 revocation']);
        $this->denied(fn () => $this->generate($s), AuthorizationException::class);
        $this->assertSame(0, DemonstrationDocument::count());
    }

    public function test_renderer_failure_records_failed_attempt_and_no_completed_metadata(): void
    {
        $s = B3Scenario::create();
        $this->mock(PrivateDemonstrationPdf::class, fn ($m) => $m->shouldReceive('render')->once()->andThrow(new \RuntimeException('PRIVATE_RENDER_FAILURE')));
        $this->denied(fn () => $this->generate($s), \RuntimeException::class);
        $d = DemonstrationDocument::sole();
        $this->assertSame('failed', $d->state);
        $this->assertNull($d->checksum);
        $this->assertNull($d->storage_key);
        $this->assertSame(1, DB::table('demonstration_document_attempts')->count());
        $this->assertSame(['generation_started', 'generation_failed'], DB::table('demonstration_document_history')->where('document_id', $d->id)->orderBy('id')->pluck('event')->all());
    }

    public function test_authority_revoked_after_render_prevents_completion(): void
    {
        $s = B3Scenario::create();
        $real = new PrivateDemonstrationPdf;
        $this->mock(PrivateDemonstrationPdf::class, function ($m) use ($s, $real) {
            $m->shouldReceive('render')->once()->andReturnUsing(function ($html, $dir) use ($s, $real) {
                $p = $real->render($html, $dir);
                $s['operator']->revokePermissionTo('documents.generate');

                return $p;
            });
            $m->shouldReceive('inspect')->andReturnUsing(fn ($p) => $real->inspect($p));
        });
        $this->denied(fn () => $this->generate($s), AuthorizationException::class);
        $this->assertSame('failed', DemonstrationDocument::sole()->state);
        $this->assertNull(DemonstrationDocument::sole()->checksum);
        $this->assertSame(0, AuditEvent::where('action', 'demonstration_pdf.generation_completed')->count());
    }

    public function test_template_withdrawn_after_render_does_not_substitute_new_version(): void
    {
        $s = B3Scenario::create();
        $real = new PrivateDemonstrationPdf;
        $this->mock(PrivateDemonstrationPdf::class, function ($m) use ($s, $real) {
            $m->shouldReceive('render')->once()->andReturnUsing(function ($html, $dir) use ($s, $real) {
                $p = $real->render($html, $dir);
                auth()->login($s['manager']);
                app(DemonstrationDocumentService::class)->advanceVersion($s['manager'], $s['template']->id, 3, 'withdrawn');
                auth()->login($s['operator']);

                return $p;
            });
            $m->shouldReceive('inspect')->andReturnUsing(fn ($p) => $real->inspect($p));
        });
        $this->denied(fn () => $this->generate($s));
        $this->assertSame('failed', DemonstrationDocument::sole()->state);
    }

    public function test_integrity_incident_missing_file_restore_identical_and_corrupt_file_blocked(): void
    {
        $s = B3Scenario::create();
        $d = $this->generate($s);
        $path = storage_path('app/private/'.$d->storage_key);
        $backup = $path.'.backup';
        copy($path, $backup);
        unlink($path);
        $service = app(DemonstrationDocumentService::class);
        $this->denied(fn () => $service->verifiedBytes($s['operator'], $d));
        $this->assertSame(1, AuditEvent::where('action', 'demonstration_pdf.integrity_incident')->count());
        $service->restore($s['operator'], $d->id, $backup);
        $this->assertSame($d->checksum, hash('sha256', $service->verifiedBytes($s['operator'], $d)));
        file_put_contents($path, 'corrupt');
        $this->denied(fn () => $service->verifiedBytes($s['operator'], $d));
        $this->denied(fn () => $service->restore($s['operator'], $d->id, $backup));
        $this->assertSame('completed', $d->fresh()->state);
        unlink($backup);
    }

    public static function auditFaults(): array
    {
        return [['before'], ['after'], ['foreign_key']];
    }

    #[DataProvider('auditFaults')]
    public function test_completion_audit_fault_rolls_back_and_removes_uncommitted_file(string $fault): void
    {
        $s = B3Scenario::create();
        $real = new AuditRecorder;
        $this->mock(AuditRecorder::class, function ($m) use ($real, $fault) {
            $m->shouldReceive('record')->andReturnUsing(function (...$args) use ($real, $fault) {
                if ($args[0] === 'demonstration_pdf.generation_completed') {
                    if ($fault === 'before') {
                        throw new \RuntimeException('PRIVATE_PRE_AUDIT');
                    }if ($fault === 'foreign_key') {
                        $bad = clone $args[2];
                        $bad->forceFill(['id' => -1]);
                        $args[2] = $bad;
                    }$real->record(...$args);
                    throw new \RuntimeException('PRIVATE_POST_AUDIT');
                }

                return $real->record(...$args);
            });
        });
        $this->denied(fn () => $this->generate($s), $fault === 'foreign_key' ? QueryException::class : \RuntimeException::class);
        $d = DemonstrationDocument::sole();
        $this->assertSame('failed', $d->state);
        $this->assertNull($d->checksum);
        $this->assertSame(0, AuditEvent::where('action', 'demonstration_pdf.generation_completed')->count());
        $this->assertFileDoesNotExist(storage_path('app/private/demonstration-documents/'.$d->public_id.'/'.$d->attempt_key.'.pdf'));
    }

    public function test_download_denials_and_no_automatic_regeneration(): void
    {
        $s = B3Scenario::create();
        $d = $this->generate($s);
        $this->actingAs($s['manager'])->get(route('documents.b3.download', $d->public_id))->assertForbidden();
        $this->actingAs($s['applicant']);
        DB::table('institution_representatives')->where('user_id', $s['applicant']->id)->update(['revoked_at' => now()]);
        $this->get(route('documents.b3.download', $d->public_id))->assertForbidden();
        $this->get(route('documents.b3.download', (string) Str::uuid()))->assertNotFound();
        $this->assertSame(1, DB::table('demonstration_document_attempts')->count());
    }

    public function test_actual_pdf_long_identity_and_multi_page_markings(): void
    {
        $s = B3Scenario::create();
        $d = $this->generate($s);
        // Exercise the identical rendering path with extreme fictional text,
        // without mutating any submitted request or completed original.
        $render = $d->render_snapshot;
        $render['identity']['organization'] = str_repeat('Very Long Fictional Higher Education Institution ', 15);
        $render['identity']['address'] = str_repeat('123 Fictional Demonstration Avenue, Sample District, Philippines. ', 20);
        $d->render_snapshot = $render;
        $dir = storage_path('app/private/demonstration-documents/tmp/stress-'.Str::uuid());
        $renderer = app(PrivateDemonstrationPdf::class);
        try {
            $path = $renderer->render(app(DemonstrationDocumentService::class)->html($d), $dir);
            $check = $renderer->inspect($path, [$render['identity']['organization'], $render['identity']['address']]);
            $this->assertGreaterThanOrEqual(3, $check['page_count']);
            $this->assertSame('completed', $d->fresh()->state);
            $this->assertNotSame($render['identity'], $d->fresh()->render_snapshot['identity']);
        } finally {
            foreach (['input.html', 'rendered.pdf'] as $name) {
                if (is_file($dir.'/'.$name)) {
                    unlink($dir.'/'.$name);
                }
            }
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    public function test_draft_request_withdrawn_and_wrong_module_template_denials(): void
    {
        $s = B3Scenario::create(submit: false);
        $this->denied(fn () => $this->generate($s));
        $this->assertSame(0, DemonstrationDocument::count());
        $s = B3Scenario::create();
        $service = app(DemonstrationDocumentService::class);
        $this->actingAs($s['manager']);
        $service->advanceVersion($s['manager'], $s['template']->id, 3, 'withdrawn');
        $this->denied(fn () => $this->generate($s));
        $this->actingAs($s['manager']);
        $other = OsdsModule::where('code', 'acsa')->firstOrFail();
        $v = $service->createVersion($s['manager'], 'template', $other->id, ['wording' => 'Fictional reference', 'fields' => []]);
        $service->advanceVersion($s['manager'], $v->id, 1, 'reviewed');
        $service->advanceVersion($s['manager'], $v->id, 2, 'published');
        $s['template'] = $v;
        $this->denied(fn () => $this->generate($s));
        $this->assertSame(0, DemonstrationDocument::count());
    }

    public function test_unknown_supplementary_key_and_wrong_personnel_region_block_generation(): void
    {
        $s = B3Scenario::create();
        $service = app(DemonstrationDocumentService::class);
        $this->actingAs($s['manager']);
        $region = Region::create(['code' => 'B3-WRONG', 'name' => 'Wrong synthetic region']);
        $p = $service->createVersion($s['manager'], 'personnel', $s['module']->id, ['region_id' => $region->id, 'directory_revision_id' => $s['directory']->id, 'office_key' => $s['personnel']->content['office_key'], 'provenance' => 'Wrong region denial fixture']);
        $service->advanceVersion($s['manager'], $p->id, 1, 'reviewed');
        $service->advanceVersion($s['manager'], $p->id, 2, 'published');
        $wrong = $s;
        $wrong['personnel'] = $p;
        $this->denied(fn () => $this->generate($wrong));
        $this->actingAs($s['manager']);
        $service->advanceVersion($s['manager'], $s['template']->id, 3, 'withdrawn');
        $t = $service->createVersion($s['manager'], 'template', $s['module']->id, ['wording' => 'Fictional reference', 'fields' => ['unknown_key']]);
        $service->advanceVersion($s['manager'], $t->id, 1, 'reviewed');
        $service->advanceVersion($s['manager'], $t->id, 2, 'published');
        $s['template'] = $t;
        $this->denied(fn () => $this->generate($s));
        $this->assertSame(0, DemonstrationDocument::count());
    }

    public static function renderFaults(): array
    {
        return [['invalid_pdf'], ['timeout'], ['storage']];
    }

    #[DataProvider('renderFaults')]
    public function test_render_and_storage_failures_never_complete(string $fault): void
    {
        $s = B3Scenario::create();
        $real = new PrivateDemonstrationPdf;
        $blocked = null;
        $this->mock(PrivateDemonstrationPdf::class, function ($m) use ($real, $fault, &$blocked) {
            $m->shouldReceive('render')->andReturnUsing(function ($html, $dir) use ($real, $fault, &$blocked) {
                if ($fault === 'timeout') {
                    throw new ProcessTimedOutException(new Process(['node']), ProcessTimedOutException::TYPE_GENERAL);
                }
                if ($fault === 'invalid_pdf') {
                    mkdir($dir, 0700, true);
                    file_put_contents($dir.'/rendered.pdf', 'not a PDF');

                    return $dir.'/rendered.pdf';
                }
                $p = $real->render($html, $dir);
                $blocked = storage_path('app/private/demonstration-documents/'.DemonstrationDocument::sole()->public_id);
                file_put_contents($blocked, 'fixture blocks storage directory');

                return $p;
            });
            $m->shouldReceive('inspect')->andReturnUsing(fn (...$a) => $real->inspect(...$a));
        });
        try {
            $this->generate($s);
            $this->fail('Fault did not block generation');
        } catch (\Throwable $e) {
            $this->assertNotInstanceOf(AssertionFailedError::class, $e);
        }
        $d = DemonstrationDocument::sole();
        $this->assertSame('failed', $d->state);
        $this->assertNull($d->completed_at);
        $this->assertNull($d->checksum);
        $this->assertNull($d->page_count);
        $this->assertSame(0, AuditEvent::where('action', 'demonstration_pdf.generation_completed')->count());
        if ($blocked && is_file($blocked)) {
            unlink($blocked);
        }
    }

    public function test_direct_sql_binding_checks_and_original_uniqueness(): void
    {
        $s = B3Scenario::create();
        $d = $this->generate($s);
        $row = DB::table('demonstration_documents')->where('id', $d->id)->first();
        $base = (array) $row;
        unset($base['id']);
        $base['public_id'] = (string) Str::uuid();
        $base['state'] = 'processing';
        foreach (['completed_at', 'storage_key', 'checksum', 'byte_size', 'page_count'] as $k) {
            $base[$k] = null;
        }
        $this->denied(fn () => DB::transaction(fn () => DB::table('demonstration_documents')->insert($base)), QueryException::class);
        $base['source_snapshot'] = '{}';
        $this->denied(fn () => DB::transaction(fn () => DB::table('demonstration_documents')->insert($base)), QueryException::class);
        $this->denied(fn () => DB::transaction(fn () => DB::table('demonstration_document_attempts')->update(['render_snapshot' => '{}'])), QueryException::class);
        $this->denied(fn () => DB::transaction(fn () => DB::table('demonstration_document_history')->delete()), QueryException::class);
        $this->assertSame(1, DemonstrationDocument::count());
    }

    public function test_expired_authority_and_explicit_interrupted_recovery(): void
    {
        $s = B3Scenario::create();
        $this->travel(40)->days();
        $this->denied(fn () => $this->generate($s), AuthorizationException::class);
        $this->travelBack();
        // Simulate a killed renderer: failed-state audit also fails, retaining
        // the committed reservation for explicit, time-bounded recovery.
        $real = new AuditRecorder;
        $this->mock(AuditRecorder::class, function ($m) use ($real) {
            $m->shouldReceive('record')->andReturnUsing(function (...$args) use ($real) {
                if ($args[0] === 'demonstration_pdf.generation_failed') {
                    throw new \RuntimeException('Simulated unavailable failure recorder');
                }

                return $real->record(...$args);
            });
        });
        $this->mock(PrivateDemonstrationPdf::class, fn ($m) => $m->shouldReceive('render')->andThrow(new \RuntimeException('Simulated renderer crash')));
        $this->denied(fn () => $this->generate($s), \RuntimeException::class);
        $d = DemonstrationDocument::sole();
        $this->assertSame('processing', $d->state);
        $this->denied(fn () => app(DemonstrationDocumentService::class)->recover($s['operator'], $d->id));
        $this->travel(121)->seconds();
        app(DemonstrationDocumentService::class)->recover($s['operator'], $d->id);
        $this->assertSame('failed', $d->fresh()->state);
        $this->assertNull($d->fresh()->storage_key);
    }
}
