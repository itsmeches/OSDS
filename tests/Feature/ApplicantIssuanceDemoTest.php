<?php

namespace Tests\Feature;

use App\Filament\Pages\DemonstrationDocuments;
use App\Models\AuditEvent;
use App\Models\DemoIssuanceFormRevision;
use App\Models\User;
use App\Services\Applicants\ApplicantSandboxGuard;
use App\Services\Auditing\AuditRecorder;
use App\Services\Documents\DemoOfficeDirectoryService;
use Database\Seeders\AuthorizationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class ApplicantIssuanceDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed(AuthorizationSeeder::class);
    }

    public function test_resource_only_local_login_reaches_form_without_enabling_application_sandbox(): void
    {
        $this->app->instance('env', 'local');
        $this->mock(ApplicantSandboxGuard::class, fn ($mock) => $mock->shouldReceive('allowed')->andReturn(false));
        $this->get(route('applicant.login'))->assertOk()->assertSee('Sign in to applicant resources');
        $applicant = User::factory()->create(['is_synthetic' => false, 'password' => 'LocalResourceOnly-2026!']);
        $applicant->givePermissionTo('applicant.portal.access');
        $this->withSession(['_token' => 'resource-login-test'])->post(route('applicant.login.store'), ['_token' => 'resource-login-test', 'email' => $applicant->email, 'password' => 'LocalResourceOnly-2026!'])
            ->assertRedirect(route('applicant.issuance-demo'));
        $this->get(route('applicant.issuance-demo'))->assertOk()->assertSee('Demonstration form unavailable')
            ->assertDontSee('Start application')->assertDontSee('My applications');
        $this->get(route('applicant.start'))->assertNotFound();
        $this->get(route('applicant.home'))->assertNotFound();
    }

    public function test_resource_login_rejects_missing_permission_inactive_account_and_production(): void
    {
        $this->app->instance('env', 'local');
        $this->mock(ApplicantSandboxGuard::class, fn ($mock) => $mock->shouldReceive('allowed')->andReturn(false));
        $actor = User::factory()->create(['password' => 'LocalResourceOnly-2026!']);
        $credentials = ['_token' => 'resource-login-test', 'email' => $actor->email, 'password' => 'LocalResourceOnly-2026!'];
        $this->withSession(['_token' => 'resource-login-test'])->post(route('applicant.login.store'), $credentials)->assertSessionHasErrors('email');
        $this->assertGuest();
        $actor->givePermissionTo('applicant.portal.access');
        $actor->update(['status' => 'inactive']);
        $this->withSession(['_token' => 'resource-login-test'])->post(route('applicant.login.store'), $credentials)->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->app->instance('env', 'production');
        $this->get(route('applicant.login'))->assertNotFound();
        $this->withSession(['_token' => 'resource-login-test'])->post(route('applicant.login.store'), $credentials)->assertNotFound();
    }

    private function setupForm(): array
    {
        $manager = User::factory()->create(['is_synthetic' => true]);
        $manager->assignRole('OSDS');
        $this->actingAs($manager);
        $directory = app(DemoOfficeDirectoryService::class)->save($manager, null, null, ['label' => 'Fictional Regional Office', 'director' => 'Fictional Director', 'designation' => 'Demo Director']);
        $page = Livewire::test(DemonstrationDocuments::class)->set('issuanceTitle', 'OSDS sample issuance form')->set('issuanceEnabled', true)
            ->set('issuanceLabels.organization', 'Fictional organization')->call('addIssuanceQuestion')
            ->set('issuanceExtras.0.label', 'Fictional sport')->set('issuanceExtras.0.required', true)->call('publishIssuanceForm')->assertHasNoErrors();
        $form = DemoIssuanceFormRevision::sole();
        $applicant = User::factory()->create(['is_synthetic' => true]);
        $applicant->givePermissionTo('applicant.portal.access');

        return [$manager, $applicant, $form, $directory, $page];
    }

    private function input($form, $directory): array
    {
        return ['form_revision' => $form->id, 'directory_revision' => $directory->id, 'office_key' => array_key_first($directory->offices),
            'kind' => 'cor', 'organization' => 'Fictional Applicant HEI', 'address' => '123 Demo Avenue', 'date' => '2026-12-01',
            'fictional_confirmation' => '1', 'extras' => [$form->definition['extras'][0]['key'] => 'Fictional volleyball']];
    }

    public function test_osds_publishes_editable_form_and_applicant_prints_both_watermarked_layouts(): void
    {
        [$manager, $applicant, $form, $directory] = $this->setupForm();
        $this->actingAs($applicant)->get(route('applicant.issuance-demo'))->assertOk()->assertSee('OSDS sample issuance form')->assertSee('Fictional organization')->assertSee('Fictional sport');
        $auditCount = AuditEvent::count();
        foreach (['cor', 'acsa'] as $kind) {
            $input = $this->input($form, $directory);
            $input['kind'] = $kind;
            $input['director'] = 'FORGED';
            $input['extras']['unknown'] = 'FORGED_EXTRA';
            $this->post(route('applicant.issuance-demo.preview'), $input)->assertOk()->assertSee('Fictional Applicant HEI')
                ->assertSee('Fictional Director')->assertSee('Fictional volleyball')->assertSee('DRAFT / DEMONSTRATION ONLY')
                ->assertSee('demo-v3')->assertSee('Form revision '.$form->id)->assertDontSee('FORGED')->assertDontSee('FORGED_EXTRA');
        }
        $this->assertSame($auditCount, AuditEvent::count());
        $this->assertSame(0, DB::table('applications')->count());
        $this->assertSame($manager->id, $form->created_by);
    }

    public function test_applicant_cannot_edit_configuration_and_other_accounts_cannot_answer(): void
    {
        [, $applicant, $form, $directory] = $this->setupForm();
        $this->actingAs($applicant);
        Livewire::test(DemonstrationDocuments::class)->assertForbidden();
        $other = User::factory()->create(['is_synthetic' => true]);
        $other->assignRole('CHEDRO');
        $this->actingAs($other)->get(route('applicant.issuance-demo'))->assertForbidden();
        $this->post(route('applicant.issuance-demo.preview'), $this->input($form, $directory))->assertForbidden();
        $this->actingAs($applicant);
        $applicant->revokePermissionTo('applicant.portal.access');
        $this->post(route('applicant.issuance-demo.preview'), $this->input($form, $directory))->assertForbidden();
        $this->assertSame(1, DemoIssuanceFormRevision::count());
    }

    public function test_changed_or_withdrawn_form_rejects_stale_answers_and_preserves_old_definition(): void
    {
        [$manager, $applicant, $form, $directory, $page] = $this->setupForm();
        $this->actingAs($manager);
        $page->set('issuanceTitle', 'Updated sample')->call('publishIssuanceForm')->assertHasNoErrors();
        $this->actingAs($applicant)->postJson(route('applicant.issuance-demo.preview'), $this->input($form, $directory))->assertUnprocessable()->assertJsonValidationErrors('form_revision');
        $this->assertSame('OSDS sample issuance form', $form->fresh()->definition['title']);
        $this->actingAs($manager);
        Livewire::test(DemonstrationDocuments::class)->set('issuanceEnabled', false)->call('publishIssuanceForm')->assertHasNoErrors();
        $this->actingAs($applicant)->get(route('applicant.issuance-demo'))->assertOk()->assertSee('Demonstration form unavailable');
        $input = $this->input($form, $directory);
        $input['form_revision'] = DemoIssuanceFormRevision::max('id');
        $this->postJson(route('applicant.issuance-demo.preview'), $input)->assertUnprocessable();
    }

    public function test_required_extra_fields_and_forged_merge_values_are_validated(): void
    {
        [, $applicant, $form, $directory] = $this->setupForm();
        $this->actingAs($applicant);
        $input = $this->input($form, $directory);
        $input['extras'] = [];
        $this->postJson(route('applicant.issuance-demo.preview'), $input)->assertUnprocessable()->assertJsonValidationErrors('extras.'.$form->definition['extras'][0]['key']);
        $input = $this->input($form, $directory);
        $input['organization'] = '<script>alert(1)</script>';
        $this->post(route('applicant.issuance-demo.preview'), $input)->assertOk()->assertDontSee('<script>', false)->assertSee('&lt;script&gt;', false);
        $this->app->instance('env', 'production');
        $this->get(route('applicant.issuance-demo'))->assertForbidden();
    }

    public function test_form_publication_rolls_back_when_actual_audit_insert_then_failure_occurs(): void
    {
        [$manager, , $form] = $this->setupForm();
        $this->actingAs($manager);
        $recorder = new AuditRecorder;
        $this->mock(AuditRecorder::class, function ($mock) use ($recorder): void {
            $mock->shouldReceive('record')->once()->andReturnUsing(function (...$args) use ($recorder) {
                $recorder->record(...$args);
                throw new RuntimeException('isolated form audit fault');
            });
        });
        $before = AuditEvent::count();
        Livewire::test(DemonstrationDocuments::class)->set('issuanceTitle', 'Not committed')->call('publishIssuanceForm')->assertHasErrors('issuanceTitle');
        $this->assertSame(1, DemoIssuanceFormRevision::count());
        $this->assertSame($before, AuditEvent::count());
        $this->assertSame($form->definition, $form->fresh()->definition);
    }

    public function test_stale_publication_and_revoked_manager_cannot_change_form(): void
    {
        [$manager, , , , $page] = $this->setupForm();
        $this->actingAs($manager);
        $stale = Livewire::test(DemonstrationDocuments::class);
        $page->set('issuanceTitle', 'New revision')->call('publishIssuanceForm')->assertHasNoErrors();
        $stale->set('issuanceTitle', 'Stale overwrite')->call('publishIssuanceForm')->assertHasErrors('issuanceTitle');
        $manager->syncRoles([]);
        $page->call('publishIssuanceForm')->assertForbidden();
        $this->assertSame(2, DemoIssuanceFormRevision::count());
    }
}
