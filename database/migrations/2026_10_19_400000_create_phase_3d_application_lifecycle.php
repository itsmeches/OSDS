<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $t->foreignId('publication_manifest_id')->constrained()->restrictOnDelete();
            $t->foreignId('institution_id')->constrained()->restrictOnDelete();
            $t->foreignId('applicant_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('routing_region_id')->nullable()->constrained('regions')->restrictOnDelete();
            $t->string('state', 30)->default('draft');
            $t->unsignedBigInteger('lock_version')->default(1);
            $t->unsignedBigInteger('current_submission_id')->nullable();
            $t->unsignedBigInteger('current_working_revision_id')->nullable();
            $t->timestampsTz();
            $t->index(['applicant_id', 'state', 'updated_at']);
            $t->index(['routing_region_id', 'module_id', 'state']);
        });
        Schema::create('application_working_revisions', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('application_id')->constrained()->restrictOnDelete();
            $t->foreignId('application_draft_id')->unique()->constrained()->restrictOnDelete();
            $t->unsignedInteger('revision_number');
            $t->string('purpose', 20);
            $t->string('state', 20)->default('open');
            $t->unsignedBigInteger('source_submission_id')->nullable();
            $t->unsignedBigInteger('correction_request_id')->nullable();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('created_at')->useCurrent();
            $t->timestampTz('consumed_at')->nullable();
            $t->unsignedBigInteger('consumed_by_submission_id')->nullable();
            $t->unique(['application_id', 'revision_number']);
        });
        Schema::table('application_drafts', fn (Blueprint $t) => $t->foreignId('application_id')->nullable()->constrained()->restrictOnDelete());
        Schema::table('evidence_reference_revisions', fn (Blueprint $t) => $t->foreignId('source_evidence_revision_id')->nullable()->constrained('evidence_reference_revisions')->restrictOnDelete());
        foreach (DB::table('application_drafts')->orderBy('id')->get() as $draft) {
            $appId = DB::table('applications')->insertGetId(['public_id' => (string) Str::uuid(), 'module_id' => $draft->module_id, 'publication_manifest_id' => $draft->publication_manifest_id, 'institution_id' => $draft->institution_id, 'applicant_id' => $draft->created_by, 'state' => 'draft', 'lock_version' => 1, 'created_at' => $draft->created_at ?? now(), 'updated_at' => $draft->updated_at ?? now()]);
            DB::table('application_drafts')->where('id', $draft->id)->update(['application_id' => $appId]);
            $workId = DB::table('application_working_revisions')->insertGetId(['public_id' => (string) Str::uuid(), 'application_id' => $appId, 'application_draft_id' => $draft->id, 'revision_number' => 1, 'purpose' => 'initial', 'state' => 'open', 'created_by' => $draft->created_by, 'created_at' => $draft->created_at ?? now()]);
            DB::table('applications')->where('id', $appId)->update(['current_working_revision_id' => $workId]);
        }
        Schema::table('application_drafts', fn (Blueprint $t) => $t->foreignId('application_id')->nullable(false)->change());
        DB::statement("CREATE UNIQUE INDEX applications_one_active_configuration ON applications(institution_id,module_id,publication_manifest_id) WHERE state IN ('draft','submitted','under_screening','correction_requested','resubmitted')");
        DB::statement("CREATE UNIQUE INDEX application_one_open_working_revision ON application_working_revisions(application_id) WHERE state='open'");

        Schema::create('application_submissions', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('application_id')->constrained()->restrictOnDelete();
            $t->foreignId('working_revision_id')->unique()->constrained('application_working_revisions')->restrictOnDelete();
            $t->foreignId('source_draft_id')->constrained('application_drafts')->restrictOnDelete();
            $t->unsignedInteger('submission_sequence');
            $t->foreignId('publication_manifest_id')->constrained()->restrictOnDelete();
            $t->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $t->foreignId('institution_id')->constrained()->restrictOnDelete();
            $t->foreignId('routing_region_id')->constrained('regions')->restrictOnDelete();
            $t->foreignId('applicant_id')->constrained('users')->restrictOnDelete();
            $t->string('applicant_name_snapshot');
            $t->string('organization_name_snapshot');
            $t->string('organization_type_snapshot', 50);
            $t->unsignedBigInteger('source_draft_lock_version');
            $t->string('answer_hash', 64);
            $t->string('requirement_hash', 64);
            $t->string('evidence_hash', 64);
            $t->string('combined_hash', 64);
            $t->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $t->timestampTz('submitted_at');
            $t->foreignId('prior_submission_id')->nullable()->constrained('application_submissions')->restrictOnDelete();
            $t->unsignedBigInteger('answered_correction_request_id')->nullable();
            $t->uuid('idempotency_key');
            $t->uuid('correlation_id');
            $t->unique(['application_id', 'submission_sequence']);
            $t->unique(['application_id', 'submitted_by', 'idempotency_key'], 'application_submission_idempotency_unique');
        });
        Schema::create('application_submission_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('submission_id')->constrained('application_submissions')->restrictOnDelete();
            $t->foreignId('form_question_id')->constrained()->restrictOnDelete();
            $t->string('question_key');
            $t->jsonb('value')->nullable();
            $t->boolean('is_applicable');
            $t->unsignedInteger('sort_order');
            $t->unique(['submission_id', 'form_question_id']);
        });
        Schema::create('application_submission_requirements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('submission_id')->constrained('application_submissions')->restrictOnDelete();
            $t->foreignId('documentary_requirement_id')->constrained()->restrictOnDelete();
            $t->boolean('is_applicable');
            $t->boolean('is_required');
            $t->unsignedInteger('minimum_count');
            $t->unsignedInteger('maximum_count');
            $t->unsignedInteger('active_count');
            $t->unsignedInteger('sort_order');
            $t->unique(['submission_id', 'documentary_requirement_id'], 'submission_requirement_unique');
        });
        Schema::create('application_submission_evidence_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('submission_requirement_id')->constrained('application_submission_requirements')->restrictOnDelete();
            $t->foreignId('evidence_reference_revision_id')->constrained()->restrictOnDelete();
            $t->unique(['submission_requirement_id', 'evidence_reference_revision_id'], 'submission_evidence_unique');
        });
        Schema::create('application_state_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained()->restrictOnDelete();
            $t->string('from_state', 30)->nullable();
            $t->string('to_state', 30);
            $t->string('event', 40);
            $t->foreignId('submission_id')->nullable()->constrained('application_submissions')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('applicant_summary')->nullable();
            $t->uuid('correlation_id');
            $t->timestampTz('created_at')->useCurrent();
        });
        Schema::create('synthetic_application_reviewer_assignments', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('reviewer_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('region_id')->constrained()->restrictOnDelete();
            $t->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $t->timestampTz('starts_at');
            $t->timestampTz('ends_at')->nullable();
            $t->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $t->text('reason');
            $t->timestampTz('revoked_at')->nullable();
            $t->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('revocation_reason')->nullable();
            $t->timestampsTz();
            $t->index(['reviewer_id', 'region_id', 'module_id'], 'application_reviewer_scope_lookup');
        });
        Schema::create('application_screening_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained()->restrictOnDelete();
            $t->foreignId('submission_id')->constrained('application_submissions')->restrictOnDelete();
            $t->string('event', 40);
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('correlation_id');
            $t->timestampTz('created_at')->useCurrent();
        });
        Schema::create('application_correction_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('application_id')->constrained()->restrictOnDelete();
            $t->foreignId('submission_id')->constrained('application_submissions')->restrictOnDelete();
            $t->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $t->text('applicant_summary');
            $t->uuid('correlation_id');
            $t->timestampTz('requested_at')->useCurrent();
        });
        Schema::create('application_correction_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('correction_request_id')->constrained('application_correction_requests')->restrictOnDelete();
            $t->string('target_kind', 30);
            $t->foreignId('form_question_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('documentary_requirement_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('issue_category', 40);
            $t->text('instruction');
            $t->unsignedInteger('sort_order');
        });

        Schema::table('applications', function (Blueprint $t) {
            $t->foreign('current_submission_id')->references('id')->on('application_submissions')->restrictOnDelete();
            $t->foreign('current_working_revision_id')->references('id')->on('application_working_revisions')->restrictOnDelete();
        });
        Schema::table('application_working_revisions', function (Blueprint $t) {
            $t->foreign('source_submission_id')->references('id')->on('application_submissions')->restrictOnDelete();
            $t->foreign('correction_request_id')->references('id')->on('application_correction_requests')->restrictOnDelete();
            $t->foreign('consumed_by_submission_id')->references('id')->on('application_submissions')->restrictOnDelete();
        });
        Schema::table('application_submissions', fn (Blueprint $t) => $t->foreign('answered_correction_request_id')->references('id')->on('application_correction_requests')->restrictOnDelete());

        DB::statement("ALTER TABLE applications ADD CONSTRAINT applications_state_check CHECK (state IN ('draft','submitted','under_screening','correction_requested','resubmitted'))");
        DB::statement("ALTER TABLE application_working_revisions ADD CONSTRAINT working_revision_state_check CHECK (state IN ('open','consumed') AND purpose IN ('initial','correction'))");
        DB::statement("ALTER TABLE application_correction_items ADD CONSTRAINT correction_item_target_check CHECK (target_kind IN ('question','document_requirement','general'))");
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_consumed_draft_mutation() RETURNS trigger AS $$ DECLARE draft_id bigint; BEGIN
                IF TG_TABLE_NAME='application_draft_answers' THEN draft_id := CASE WHEN TG_OP='DELETE' THEN OLD.application_draft_id ELSE NEW.application_draft_id END;
                ELSIF TG_TABLE_NAME='evidence_references' THEN draft_id := CASE WHEN TG_OP='DELETE' THEN OLD.application_draft_id ELSE NEW.application_draft_id END;
                ELSE SELECT application_draft_id INTO draft_id FROM evidence_references WHERE id=CASE WHEN TG_OP='DELETE' THEN OLD.evidence_reference_id ELSE NEW.evidence_reference_id END; END IF;
                IF EXISTS (SELECT 1 FROM application_working_revisions WHERE application_draft_id=draft_id AND state='consumed') THEN RAISE EXCEPTION 'Consumed application working revisions are immutable.'; END IF; IF TG_OP='DELETE' THEN RETURN OLD; ELSE RETURN NEW; END IF; END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER consumed_answers_guard BEFORE INSERT OR UPDATE OR DELETE ON application_draft_answers FOR EACH ROW EXECUTE FUNCTION prevent_consumed_draft_mutation();
            CREATE TRIGGER consumed_evidence_reference_guard BEFORE INSERT OR UPDATE OR DELETE ON evidence_references FOR EACH ROW EXECUTE FUNCTION prevent_consumed_draft_mutation();
            CREATE TRIGGER consumed_evidence_revision_guard BEFORE INSERT OR UPDATE OR DELETE ON evidence_reference_revisions FOR EACH ROW EXECUTE FUNCTION prevent_consumed_draft_mutation();
            CREATE OR REPLACE FUNCTION enforce_application_transition() RETURNS trigger AS $$ BEGIN
                IF NEW.state<>OLD.state AND NOT ((OLD.state='draft' AND NEW.state='submitted') OR (OLD.state IN ('submitted','resubmitted') AND NEW.state='under_screening') OR (OLD.state='under_screening' AND NEW.state='correction_requested') OR (OLD.state='correction_requested' AND NEW.state='resubmitted')) THEN RAISE EXCEPTION 'Invalid synthetic application transition.'; END IF;
                IF NEW.module_id<>OLD.module_id OR NEW.publication_manifest_id<>OLD.publication_manifest_id OR NEW.institution_id<>OLD.institution_id OR NEW.applicant_id<>OLD.applicant_id THEN RAISE EXCEPTION 'Application bindings are immutable.'; END IF; RETURN NEW; END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_transition_guard BEFORE UPDATE ON applications FOR EACH ROW EXECUTE FUNCTION enforce_application_transition();
        SQL);
        foreach (['application_submissions', 'application_submission_answers', 'application_submission_requirements', 'application_submission_evidence_items', 'application_state_history', 'application_screening_events', 'application_correction_requests', 'application_correction_items'] as $table) {
            DB::statement("CREATE TRIGGER {$table}_append_only BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation()");
        }
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS prevent_consumed_draft_mutation() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS enforce_application_transition() CASCADE');
        Schema::table('application_submissions', fn (Blueprint $t) => $t->dropForeign(['answered_correction_request_id']));
        Schema::table('application_working_revisions', function (Blueprint $t) {
            $t->dropForeign(['source_submission_id']);
            $t->dropForeign(['correction_request_id']);
            $t->dropForeign(['consumed_by_submission_id']);
        });
        Schema::table('applications', function (Blueprint $t) {
            $t->dropForeign(['current_submission_id']);
            $t->dropForeign(['current_working_revision_id']);
        });
        Schema::table('application_drafts', fn (Blueprint $t) => $t->dropConstrainedForeignId('application_id'));
        Schema::table('evidence_reference_revisions', fn (Blueprint $t) => $t->dropConstrainedForeignId('source_evidence_revision_id'));
        foreach (['application_correction_items', 'application_correction_requests', 'application_screening_events', 'synthetic_application_reviewer_assignments', 'application_state_history', 'application_submission_evidence_items', 'application_submission_requirements', 'application_submission_answers', 'application_submissions', 'application_working_revisions', 'applications'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
