<?php

namespace Tests\Feature;

use App\Filament\Pages\DemonstrationDocuments;
use App\Models\AuditEvent;
use App\Models\DemoOfficeDirectoryRevision;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Services\Documents\DemonstrationCertificateRenderer;
use App\Services\Documents\DemoOfficeDirectoryService;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class DemonstrationDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_actual_page_maintains_offices_and_fills_both_templates_from_selected_office(): void
    {
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->assignRole('OSDS');
        $this->actingAs($actor);
        $page = Livewire::test(DemonstrationDocuments::class)->set('officeLabel', 'CHEDRO I — demonstration')
            ->set('director', 'Dr. First Fictional')->set('designation', 'Demo Director I')->call('saveOffice')->assertHasNoErrors();
        $first = DemoOfficeDirectoryRevision::sole();
        $key = array_key_first($first->offices);
        $page->set('officeLabel', 'CHEDRO II — demonstration')->set('director', 'Dr. Second Fictional')->set('designation', 'Demo Director II')->call('saveOffice')->assertHasNoErrors();
        $second = DemoOfficeDirectoryRevision::latest('id')->first();
        $secondKey = collect($second->offices)->search(fn ($office) => $office['label'] === 'CHEDRO II — demonstration');
        foreach (['cor', 'acsa'] as $kind) {
            $this->post(route('documents.demonstration.issuance'), [
                'kind' => $kind, 'organization' => 'Fictional Applicant <script>alert(1)</script>', 'address' => 'Demo address',
                'date' => '2026-12-01', 'office_key' => $secondKey, 'directory_revision' => $second->id,
                'fictional_confirmation' => '1', 'director' => 'FORGED DIRECTOR', 'designation' => 'FORGED TITLE',
            ])->assertOk()->assertSee('Dr. Second Fictional')->assertSee('Demo Director II')->assertSee('DRAFT / DEMONSTRATION ONLY')
                ->assertSee('demo-v2')->assertDontSee('FORGED DIRECTOR')->assertDontSee('<script>', false);
        }
        $html = app(DemonstrationCertificateRenderer::class)->renderIssuance([
            'kind' => 'cor', 'organization' => 'Fictional HEI', 'address' => 'Demo address', 'date' => '2026-12-01',
            'office_key' => $key, 'directory_revision' => $second->id, 'fictional_confirmation' => '1',
        ]);
        $page->call('editOffice', $key)->set('director', 'Dr. Updated Fictional')->call('saveOffice')->assertHasNoErrors();
        $this->assertStringContainsString('Dr. First Fictional', $html);
        $this->assertSame('Dr. First Fictional', $first->fresh()->offices[$key]['director']);
        $this->assertSame(3, DemoOfficeDirectoryRevision::count());
        $this->assertSame(3, AuditEvent::where('action', 'demonstration_document.directory_updated')->count());
        $this->assertSame(0, DB::table('applications')->count());
    }

    public function test_stale_directory_edits_and_stale_or_forged_previews_are_rejected(): void
    {
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->assignRole('OSDS');
        $this->actingAs($actor);
        $directory = app(DemoOfficeDirectoryService::class);
        $first = $directory->save($actor, null, null, ['label' => 'Office A', 'director' => 'Fictional A', 'designation' => 'Demo']);
        $key = array_key_first($first->offices);
        $directory->save($actor, $first->id, $key, ['label' => 'Office A', 'director' => 'Fictional B', 'designation' => 'Demo']);
        try {
            $directory->save($actor, $first->id, $key, ['label' => 'Office A', 'director' => 'Stale', 'designation' => 'Demo']);
            $this->fail('Stale update accepted');
        } catch (ValidationException) {
            $this->assertSame(2, DemoOfficeDirectoryRevision::count());
        }
        $input = ['kind' => 'cor', 'organization' => 'Fictional HEI', 'address' => 'Demo', 'date' => '2026-12-01', 'office_key' => $key, 'directory_revision' => $first->id, 'fictional_confirmation' => '1'];
        $this->postJson(route('documents.demonstration.issuance'), $input)->assertUnprocessable()->assertJsonValidationErrors('office_key');
        $input['directory_revision'] = DemoOfficeDirectoryRevision::max('id');
        $input['office_key'] = '00000000-0000-4000-8000-000000000000';
        $this->postJson(route('documents.demonstration.issuance'), $input)->assertUnprocessable()->assertJsonValidationErrors('office_key');
        $input['office_key'] = $key;
        $input['fictional_confirmation'] = '0';
        $this->postJson(route('documents.demonstration.issuance'), $input)->assertUnprocessable()->assertJsonValidationErrors('fictional_confirmation');
    }

    public function test_directory_revision_and_real_audit_insert_roll_back_together(): void
    {
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->assignRole('OSDS');
        $this->actingAs($actor);
        $recorder = new AuditRecorder;
        $this->mock(AuditRecorder::class, function ($mock) use ($recorder): void {
            $mock->shouldReceive('record')->once()->andReturnUsing(function (...$arguments) use ($recorder) {
                $recorder->record(...$arguments);
                throw new RuntimeException('isolated directory audit fault');
            });
        });
        $before = AuditEvent::count();
        Livewire::test(DemonstrationDocuments::class)->set('officeLabel', 'Office A')->set('director', 'Fictional A')->set('designation', 'Demo')
            ->call('saveOffice')->assertHasErrors('officeLabel');
        $this->assertSame(0, DemoOfficeDirectoryRevision::count());
        $this->assertSame($before, AuditEvent::count());
    }

    public function test_chedro_cannot_manage_directory_or_submit_issuance_preview(): void
    {
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->assignRole('CHEDRO');
        $this->actingAs($actor)->post(route('documents.demonstration.issuance'))->assertForbidden();
        Livewire::test(DemonstrationDocuments::class)->assertForbidden();
        $this->assertSame(0, DemoOfficeDirectoryRevision::count());
    }

    public function test_both_versioned_templates_render_fixed_fictional_samples_and_embedded_watermarks(): void
    {
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->assignRole('OSDS');
        $this->actingAs($actor);
        Livewire::test(DemonstrationDocuments::class)->assertSee('Certificate layout previews');
        foreach (['cor' => 'CERTIFICATE OF REGISTRATION', 'acsa' => 'AUTHORITY TO CONDUCT SPORTS ACTIVITY'] as $kind => $title) {
            $url = route('documents.demonstration.preview', ['version' => 'demo-v1', 'kind' => $kind]);
            $response = $this->get($url.'?organization=REAL+APPLICANT&director=REAL+PERSON');
            $response->assertOk()->assertSee($title)->assertSee('Fictional Lakeside')->assertSee('DRAFT / DEMONSTRATION ONLY')
                ->assertSee('NO LEGAL VALIDITY')->assertSee('demo-v1')->assertDontSee('REAL APPLICANT')->assertDontSee('REAL PERSON')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertSame(0, DB::table('applications')->count());
            $this->get($url.'?download=1')->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="'.$kind.'-demo-v1-DEMONSTRATION.html"');
        }
        $this->get('/document-previews/demo-v2/cor')->assertNotFound();
        $this->get('/document-previews/demo-v1/unknown')->assertNotFound();
    }

    public function test_preview_requires_existing_management_authority_and_active_identity(): void
    {
        $url = '/document-previews/demo-v1/cor';
        $this->get($url)->assertRedirect();
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->assignRole('CHEDRO');
        $this->actingAs($actor)->get($url)->assertForbidden();
        $actor->syncRoles(['OSDS']);
        $this->get($url)->assertOk();
        $actor->syncRoles([]);
        $this->get($url)->assertForbidden();
        $actor->assignRole('OSDS');
        $actor->update(['status' => 'inactive']);
        $this->get($url)->assertForbidden();
    }

    public function test_production_rendering_route_is_disabled(): void
    {
        $actor = User::factory()->create(['is_synthetic' => true]);
        $actor->assignRole('OSDS');
        $this->actingAs($actor);
        $this->app->instance('env', 'production');
        $this->assertFalse(app(DemonstrationCertificateRenderer::class)->canPreview($actor));
        $this->get('/document-previews/demo-v1/cor')->assertForbidden();
    }
}
