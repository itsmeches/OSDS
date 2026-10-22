<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issuance_definitions', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->string('state');
            $t->string('title');
            $t->text('instructions')->nullable();
            $t->foreignId('configuration_review_approval_id')->constrained()->restrictOnDelete();
            $t->foreignId('source_form_version_id')->constrained('form_versions')->restrictOnDelete();
            $t->jsonb('definition');
            $t->string('definition_hash', 64);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('lock_version')->default(1);
            $t->timestampsTz();
            $t->unique(['module_id', 'version']);
        });
        Schema::create('issuance_requests', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('application_id')->constrained()->restrictOnDelete();
            $t->foreignId('submission_id')->constrained('application_submissions')->restrictOnDelete();
            $t->foreignId('decision_id')->constrained('application_decision_stages')->restrictOnDelete();
            $t->foreignId('definition_id')->constrained('issuance_definitions')->restrictOnDelete();
            $t->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $t->foreignId('institution_id')->constrained()->restrictOnDelete();
            $t->foreignId('region_id')->constrained('regions')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('certificate_type', 8);
            $t->string('state');
            $t->jsonb('identity_snapshot');
            $t->jsonb('definition_snapshot');
            $t->jsonb('answers');
            $t->uuid('request_key');
            $t->string('payload_hash', 64);
            $t->unsignedInteger('lock_version')->default(1);
            $t->timestampTz('submitted_at')->nullable();
            $t->timestampsTz();
            $t->unique(['actor_id', 'request_key']);
        });
        Schema::create('issuance_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('request_id')->nullable()->constrained('issuance_requests')->restrictOnDelete();
            $t->foreignId('definition_id')->constrained('issuance_definitions')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('event');
            $t->timestampTz('created_at')->useCurrent();
        });
        DB::unprepared(<<<'SQL'
            ALTER TABLE issuance_definitions ADD CONSTRAINT issuance_definition_state CHECK(state IN ('draft','reviewed','published','withdrawn'));
            CREATE UNIQUE INDEX issuance_one_current_definition ON issuance_definitions(module_id) WHERE state='published';
            ALTER TABLE issuance_requests ADD CONSTRAINT issuance_request_state CHECK(state IN ('draft','submitted'));
            CREATE UNIQUE INDEX issuance_one_original_request ON issuance_requests(decision_id,certificate_type) WHERE state='submitted';
            CREATE UNIQUE INDEX issuance_one_versioned_draft ON issuance_requests(decision_id,definition_id,actor_id) WHERE state='draft';
            ALTER TABLE issuance_requests ADD CONSTRAINT issuance_request_submission_time CHECK((state='submitted')=(submitted_at IS NOT NULL));
            CREATE TRIGGER issuance_history_append_only BEFORE UPDATE OR DELETE ON issuance_history FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
            CREATE OR REPLACE FUNCTION guard_issuance_definition() RETURNS trigger AS $$ BEGIN
                IF TG_OP='DELETE' THEN RAISE EXCEPTION 'Issuance definition history is immutable.'; END IF;
                IF TG_OP='UPDATE' THEN
                    IF (to_jsonb(NEW)-'state'-'lock_version'-'updated_at') IS DISTINCT FROM (to_jsonb(OLD)-'state'-'lock_version'-'updated_at') OR NEW.lock_version<>OLD.lock_version+1
                    OR NOT ((OLD.state='draft' AND NEW.state='reviewed') OR (OLD.state='reviewed' AND NEW.state='published') OR (OLD.state='published' AND NEW.state='withdrawn')) THEN RAISE EXCEPTION 'Invalid issuance definition transition.'; END IF;
                ELSIF NEW.state<>'draft' THEN RAISE EXCEPTION 'Definition must start draft.'; END IF;
                IF NOT EXISTS(SELECT 1 FROM configuration_review_approvals p JOIN configuration_review_requests r ON r.id=p.configuration_review_request_id JOIN form_versions f ON f.id=NEW.source_form_version_id JOIN form_definitions d ON d.id=f.form_definition_id WHERE p.id=NEW.configuration_review_approval_id AND p.outcome='approved' AND r.module_id=NEW.module_id AND d.module_id=NEW.module_id) THEN RAISE EXCEPTION 'Definition requires matching approved configuration and source module.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER issuance_definition_guard BEFORE INSERT OR UPDATE OR DELETE ON issuance_definitions FOR EACH ROW EXECUTE FUNCTION guard_issuance_definition();
            CREATE OR REPLACE FUNCTION guard_issuance_request() RETURNS trigger AS $$
            DECLARE a applications%ROWTYPE; d issuance_definitions%ROWTYPE;
            BEGIN
                IF TG_OP='DELETE' THEN RAISE EXCEPTION 'Issuance requests cannot be deleted.'; END IF;
                IF TG_OP='UPDATE' THEN
                    IF OLD.state='submitted' THEN RAISE EXCEPTION 'Submitted issuance snapshots are immutable.'; END IF;
                    IF (to_jsonb(NEW)-'answers'-'payload_hash'-'state'-'submitted_at'-'lock_version'-'updated_at') IS DISTINCT FROM (to_jsonb(OLD)-'answers'-'payload_hash'-'state'-'submitted_at'-'lock_version'-'updated_at') OR NEW.lock_version<>OLD.lock_version+1 THEN RAISE EXCEPTION 'Issuance bindings are immutable and require next version.'; END IF;
                END IF;
                SELECT * INTO a FROM applications WHERE id=NEW.application_id FOR UPDATE;
                SELECT * INTO d FROM issuance_definitions WHERE id=NEW.definition_id FOR SHARE;
                IF a.state<>'approved' OR a.final_decision_id IS DISTINCT FROM NEW.decision_id OR a.current_submission_id IS DISTINCT FROM NEW.submission_id OR a.current_working_revision_id IS NOT NULL OR a.institution_id<>NEW.institution_id OR a.routing_region_id<>NEW.region_id OR a.module_id<>NEW.module_id OR a.applicant_id<>NEW.actor_id THEN RAISE EXCEPTION 'Issuance must bind the approved case and exact submission.'; END IF;
                IF NOT EXISTS(SELECT 1 FROM application_decision_stages s WHERE s.id=NEW.decision_id AND s.application_id=a.id AND s.submission_id=NEW.submission_id AND s.stage_role='director' AND s.action='approved') THEN RAISE EXCEPTION 'Director approval required.'; END IF;
                IF d.state<>'published' OR d.module_id<>NEW.module_id OR NEW.definition_snapshot IS DISTINCT FROM d.definition OR NOT EXISTS(SELECT 1 FROM application_submissions s JOIN publication_manifests m ON m.id=s.publication_manifest_id WHERE s.id=NEW.submission_id AND m.form_version_id=d.source_form_version_id) THEN RAISE EXCEPTION 'Current matching issuance definition required.'; END IF;
                IF NOT EXISTS(SELECT 1 FROM modules m WHERE m.id=NEW.module_id AND ((m.code='registration' AND NEW.certificate_type='cor') OR (m.code='acsa' AND NEW.certificate_type='acsa'))) THEN RAISE EXCEPTION 'Certificate module mismatch.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER issuance_request_guard BEFORE INSERT OR UPDATE OR DELETE ON issuance_requests FOR EACH ROW EXECUTE FUNCTION guard_issuance_request();
        SQL);
    }

    public function down(): void
    {
        if (DB::table('issuance_requests')->exists() || DB::table('issuance_definitions')->exists()) {
            throw new RuntimeException('Preserve issuance history; downgrade requires separate review.');
        }
        Schema::drop('issuance_history');
        Schema::drop('issuance_requests');
        Schema::drop('issuance_definitions');
        DB::unprepared('DROP FUNCTION IF EXISTS guard_issuance_request(); DROP FUNCTION IF EXISTS guard_issuance_definition();');
    }
};
