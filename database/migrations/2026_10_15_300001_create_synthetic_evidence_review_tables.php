<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('synthetic_evidence_reviewer_assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestampsTz();
            $table->index(['reviewer_id', 'region_id', 'module_id'], 'synthetic_reviewer_scope_lookup');
        });
        Schema::create('synthetic_evidence_review_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('application_draft_id')->constrained()->restrictOnDelete();
            $table->foreignId('publication_manifest_id')->constrained()->restrictOnDelete();
            $table->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $table->foreignId('institution_id')->constrained()->restrictOnDelete();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->foreignId('applicant_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('draft_lock_version');
            $table->string('answer_state_hash', 64);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->foreignId('supersedes_case_id')->nullable()->constrained('synthetic_evidence_review_cases')->restrictOnDelete();
            $table->index(['region_id', 'module_id', 'created_at'], 'synthetic_review_queue_lookup');
        });
        Schema::create('synthetic_evidence_review_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('synthetic_evidence_review_cases')->restrictOnDelete();
            $table->foreignId('documentary_requirement_id')->constrained()->restrictOnDelete();
            $table->boolean('is_applicable');
            $table->boolean('is_required');
            $table->unsignedInteger('minimum_count');
            $table->unsignedInteger('maximum_count');
            $table->unsignedInteger('active_count');
            $table->unsignedInteger('sort_order');
            $table->unique(['case_id', 'documentary_requirement_id'], 'synthetic_case_requirement_unique');
        });
        Schema::create('synthetic_evidence_review_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_requirement_id')->constrained('synthetic_evidence_review_requirements')->restrictOnDelete();
            $table->foreignId('evidence_reference_revision_id')->constrained()->restrictOnDelete();
            $table->unique(['case_requirement_id', 'evidence_reference_revision_id'], 'synthetic_case_item_unique');
        });
        Schema::create('synthetic_evidence_review_findings', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('case_requirement_id')->constrained('synthetic_evidence_review_requirements')->restrictOnDelete();
            $table->foreignId('evidence_reference_revision_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $table->string('finding_type', 30);
            $table->string('outcome', 30);
            $table->text('comment')->nullable();
            $table->foreignId('supersedes_finding_id')->nullable()->constrained('synthetic_evidence_review_findings')->restrictOnDelete();
            $table->uuid('correlation_id');
            $table->timestampTz('reviewed_at')->useCurrent();
        });
        DB::statement("ALTER TABLE synthetic_evidence_review_findings ADD CONSTRAINT synthetic_finding_type_check CHECK (finding_type IN ('accessibility','completeness','document_match'))");
        DB::statement("ALTER TABLE synthetic_evidence_review_findings ADD CONSTRAINT synthetic_finding_outcome_check CHECK (outcome IN ('meets_test_check','needs_attention','unable_to_access','not_reviewed'))");
        foreach (['synthetic_evidence_review_cases', 'synthetic_evidence_review_requirements', 'synthetic_evidence_review_items', 'synthetic_evidence_review_findings'] as $table) {
            DB::statement("CREATE TRIGGER {$table}_append_only BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation()");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('synthetic_evidence_review_findings');
        Schema::dropIfExists('synthetic_evidence_review_items');
        Schema::dropIfExists('synthetic_evidence_review_requirements');
        Schema::dropIfExists('synthetic_evidence_review_cases');
        Schema::dropIfExists('synthetic_evidence_reviewer_assignments');
    }
};
