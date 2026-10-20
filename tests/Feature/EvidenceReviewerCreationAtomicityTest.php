<?php

namespace Tests\Feature;

use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\Pages\CreateSyntheticEvidenceReviewerAssignment;
use App\Filament\Resources\SyntheticEvidenceReviewerAssignments\SyntheticEvidenceReviewerAssignmentResource;
use App\Models\AuditEvent;
use App\Models\OsdsModule;
use App\Models\Region;
use App\Models\SyntheticEvidenceReviewerAssignment;
use App\Models\User;
use App\Services\Auditing\AuditRecorder;
use App\Support\AccountRole;
use Database\Seeders\AuthorizationSeeder;
use Database\Seeders\QuestionnaireReferenceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class EvidenceReviewerCreationAtomicityTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $this->assertSame('osds_test', DB::connection()->getDatabaseName());
        $this->seed([AuthorizationSeeder::class, QuestionnaireReferenceSeeder::class]);
        $actor = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $actor->assignRole(AccountRole::Admin->value);
        $this->actingAs($actor);
        $reviewer = User::factory()->create(['status' => 'active', 'is_synthetic' => true]);
        $reviewer->assignRole(AccountRole::Chedro->value);
        $region = Region::create(['code' => 'G5', 'name' => 'Isolated evidence creation region']);
        $this->assertTrue(SyntheticEvidenceReviewerAssignmentResource::canCreate());

        return [$actor, ['reviewer_id' => $reviewer->id, 'region_id' => $region->id,
            'module_id' => OsdsModule::firstOrFail()->id, 'starts_at' => now()->subMinute(),
            'reason' => 'Isolated evidence creation atomicity probe']];
    }

    public function test_initial_create_mount_does_not_mutate_records(): void
    {
        [$actor] = $this->fixture();
        $before = [SyntheticEvidenceReviewerAssignment::count(), AuditEvent::count()];
        // The historical callback failure did not reproduce on this empty
        // initial mount. Search, selection and actual creation remain separate.
        Livewire::actingAs($actor)->test(CreateSyntheticEvidenceReviewerAssignment::class);
        $this->assertSame($before, [SyntheticEvidenceReviewerAssignment::count(), AuditEvent::count()]);
    }

    public function test_inherited_creation_persistence_rolls_back_when_mandatory_audit_fails(): void
    {
        [, $data] = $this->fixture();
        $page = new CreateSyntheticEvidenceReviewerAssignment;
        $this->assertFalse($page->hasDatabaseTransactions());
        $before = [SyntheticEvidenceReviewerAssignment::count(), AuditEvent::count()];
        $this->mock(AuditRecorder::class)->shouldReceive('record')->once()
            ->withArgs(fn ($action, ...$rest) => $action === 'evidence.reviewer_assignment_granted')
            ->andThrow(new \RuntimeException('ISOLATED_EVIDENCE_CREATION_AUDIT_FAILURE'));
        // Retain the original protected-handler regression independently of the
        // form. Its current authoritative override now owns the service call.
        // Full Create/Livewire behavior is covered by the separate workflow test.
        $handler = new \ReflectionMethod($page, 'handleRecordCreation');
        $this->assertSame(CreateSyntheticEvidenceReviewerAssignment::class, $handler->getDeclaringClass()->getName());
        try {
            $handler->invoke($page, $data);
            $this->fail('Mandatory audit fault must be reached.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('data.reason', $exception->errors());
            $this->assertStringNotContainsString('ISOLATED_EVIDENCE_CREATION_AUDIT_FAILURE', $exception->getMessage());
        }
        $this->assertSame($before[1], AuditEvent::count());
        $this->assertSame($before[0], SyntheticEvidenceReviewerAssignment::count(), 'A failed mandatory evidence-reviewer grant audit must not leave an assignment persisted.');
    }
}
