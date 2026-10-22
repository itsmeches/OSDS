<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_decision_stages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('application_id')->constrained()->restrictOnDelete();
            $t->foreignId('submission_id')->constrained('application_submissions')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('stage_role', 16);
            $t->string('action', 32);
            $t->foreignId('predecessor_id')->nullable()->constrained('application_decision_stages')->restrictOnDelete();
            $t->foreignId('correction_request_id')->nullable()->constrained('application_correction_requests')->restrictOnDelete();
            $t->boolean('attested')->default(false);
            $t->text('applicant_summary');
            $t->text('internal_notes')->nullable();
            $t->uuid('request_key');
            $t->string('payload_hash', 64);
            $t->timestampTz('created_at')->useCurrent();
            $t->unique(['application_id', 'actor_id', 'request_key']);
            $t->index(['application_id', 'submission_id', 'id']);
        });
        Schema::table('applications', fn (Blueprint $t) => $t->foreignId('final_decision_id')->nullable()->constrained('application_decision_stages')->restrictOnDelete());
        DB::unprepared(<<<'SQL'
            ALTER TABLE application_decision_stages ADD CONSTRAINT decision_stage_kind CHECK (
                (stage_role='evaluator' AND action IN ('evaluated','correction_requested')) OR
                (stage_role='chief' AND action IN ('recommended','returned')) OR
                (stage_role='director' AND action IN ('approved','rejected')));
            ALTER TABLE application_decision_stages ADD CONSTRAINT decision_stage_attestation CHECK (action<>'evaluated' OR attested);
            CREATE UNIQUE INDEX one_final_decision_per_application ON application_decision_stages(application_id) WHERE stage_role='director';
            CREATE TRIGGER application_decision_stages_append_only BEFORE UPDATE OR DELETE ON application_decision_stages FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();

            CREATE OR REPLACE FUNCTION enforce_b1_stage() RETURNS trigger AS $$
            DECLARE a applications%ROWTYPE; prior application_decision_stages%ROWTYPE;
            BEGIN
                SELECT * INTO a FROM applications WHERE id=NEW.application_id FOR UPDATE;
                IF a.current_submission_id IS DISTINCT FROM NEW.submission_id OR NOT EXISTS (
                    SELECT 1 FROM application_submissions s WHERE s.id=NEW.submission_id AND s.application_id=a.id
                ) THEN RAISE EXCEPTION 'Stage must bind the exact current submission.'; END IF;
                IF NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.actor_id AND is_synthetic AND status='active' AND deactivated_at IS NULL) OR NEW.actor_id=a.applicant_id THEN RAISE EXCEPTION 'Synthetic active staff actor required.'; END IF;
                IF EXISTS (SELECT 1 FROM application_decision_stages WHERE application_id=a.id AND actor_id=NEW.actor_id AND stage_role<>NEW.stage_role) THEN RAISE EXCEPTION 'Case-wide stage separation required.'; END IF;
                IF NEW.action='correction_requested' THEN
                    IF a.state<>'correction_requested' OR a.current_working_revision_id IS NULL OR NOT EXISTS (
                        SELECT 1 FROM application_correction_requests r WHERE r.id=NEW.correction_request_id AND r.application_id=a.id AND r.submission_id=NEW.submission_id AND r.requested_by=NEW.actor_id
                    ) THEN RAISE EXCEPTION 'Stage correction must bind the existing correction operation.'; END IF;
                ELSE
                    IF a.state<>'under_screening' OR a.current_working_revision_id IS NOT NULL OR NEW.correction_request_id IS NOT NULL THEN RAISE EXCEPTION 'Stage requires current screening without open corrections.'; END IF;
                    SELECT * INTO prior FROM application_decision_stages WHERE application_id=a.id AND submission_id=NEW.submission_id ORDER BY id DESC LIMIT 1;
                    IF NEW.action='evaluated' THEN
                        IF prior.id IS NOT NULL AND prior.action<>'returned' THEN RAISE EXCEPTION 'Evaluation is not the next stage.'; END IF;
                        IF NEW.predecessor_id IS DISTINCT FROM prior.id THEN RAISE EXCEPTION 'Evaluation predecessor changed.'; END IF;
                    ELSE
                        IF prior.id IS NULL OR NEW.predecessor_id IS DISTINCT FROM prior.id THEN RAISE EXCEPTION 'Stage predecessor changed.'; END IF;
                        IF NEW.stage_role='chief' AND prior.action<>'evaluated' THEN RAISE EXCEPTION 'Current evaluation required.'; END IF;
                        IF NEW.stage_role='director' AND prior.action<>'recommended' THEN RAISE EXCEPTION 'Current recommendation required.'; END IF;
                    END IF;
                END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER b1_stage_binding BEFORE INSERT ON application_decision_stages FOR EACH ROW EXECUTE FUNCTION enforce_b1_stage();

            CREATE OR REPLACE FUNCTION enforce_b1_final_commit() RETURNS trigger AS $$ BEGIN
                IF NEW.stage_role='director' AND NOT EXISTS (SELECT 1 FROM applications a WHERE a.id=NEW.application_id AND a.final_decision_id=NEW.id AND a.current_submission_id=NEW.submission_id AND a.state=NEW.action AND a.current_working_revision_id IS NULL) THEN RAISE EXCEPTION 'Final decision and terminal application must commit together.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE CONSTRAINT TRIGGER b1_final_commit AFTER INSERT ON application_decision_stages DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION enforce_b1_final_commit();
            CREATE OR REPLACE FUNCTION enforce_b1_application_insert() RETURNS trigger AS $$ BEGIN
                IF NEW.state IN ('approved','rejected') OR NEW.final_decision_id IS NOT NULL THEN RAISE EXCEPTION 'New application cannot start with a final decision.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER b1_application_insert BEFORE INSERT ON applications FOR EACH ROW EXECUTE FUNCTION enforce_b1_application_insert();
            ALTER TABLE applications DROP CONSTRAINT applications_state_check;
            ALTER TABLE applications ADD CONSTRAINT applications_state_check CHECK (state IN ('draft','submitted','under_screening','correction_requested','resubmitted','approved','rejected'));
            DROP INDEX applications_one_active_configuration;
            CREATE UNIQUE INDEX applications_one_active_configuration ON applications(institution_id,module_id,publication_manifest_id) WHERE state IN ('draft','submitted','under_screening','correction_requested','resubmitted','approved','rejected');
        SQL);
        $this->transitionFunction(true);
    }

    private function transitionFunction(bool $b1): void
    {
        $extraEdge = $b1 ? " OR (OLD.state='under_screening' AND NEW.state IN ('approved','rejected'))" : '';
        $extraChecks = $b1 ? <<<'SQL'
            IF OLD.state IN ('approved','rejected') AND NEW IS DISTINCT FROM OLD THEN RAISE EXCEPTION 'Terminal applications are immutable.'; END IF;
            IF NEW.state IN ('approved','rejected') THEN
                IF NEW.current_working_revision_id IS NOT NULL OR NOT EXISTS (SELECT 1 FROM application_decision_stages d WHERE d.id=NEW.final_decision_id AND d.application_id=NEW.id AND d.submission_id=NEW.current_submission_id AND d.stage_role='director' AND d.action=NEW.state) THEN RAISE EXCEPTION 'Matching immutable final decision required.'; END IF;
            ELSIF NEW.final_decision_id IS NOT NULL THEN RAISE EXCEPTION 'Nonterminal application cannot bind a final decision.'; END IF;
        SQL : '';
        DB::unprepared(<<<SQL
            CREATE OR REPLACE FUNCTION enforce_application_transition() RETURNS trigger AS $$ BEGIN
                IF NEW.state<>OLD.state AND NOT ((OLD.state='draft' AND NEW.state='submitted') OR (OLD.state IN ('submitted','resubmitted') AND NEW.state='under_screening') OR (OLD.state='under_screening' AND NEW.state='correction_requested') OR (OLD.state='correction_requested' AND NEW.state='resubmitted'){$extraEdge}) THEN RAISE EXCEPTION 'Invalid synthetic application transition.'; END IF;
                IF NEW.module_id<>OLD.module_id OR NEW.publication_manifest_id<>OLD.publication_manifest_id OR NEW.institution_id<>OLD.institution_id OR NEW.applicant_id<>OLD.applicant_id THEN RAISE EXCEPTION 'Application bindings are immutable.'; END IF;
                IF NEW.lock_version<OLD.lock_version OR (NEW.state<>OLD.state AND NEW.lock_version<>OLD.lock_version+1) THEN RAISE EXCEPTION 'Application transition requires the next lock version.'; END IF;
                IF NEW.current_submission_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM application_submissions s WHERE s.id=NEW.current_submission_id AND s.application_id=NEW.id) THEN RAISE EXCEPTION 'Current submission belongs to another application.'; END IF;
                IF NEW.current_working_revision_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM application_working_revisions w WHERE w.id=NEW.current_working_revision_id AND w.application_id=NEW.id AND w.state='open') THEN RAISE EXCEPTION 'Current working revision is not an open revision of this application.'; END IF;
                IF NEW.state='draft' AND NEW.current_submission_id IS NOT NULL THEN RAISE EXCEPTION 'A draft application cannot have a current submission.'; END IF;
                IF NEW.state IN ('submitted','under_screening','resubmitted') AND (NEW.current_submission_id IS NULL OR NEW.current_working_revision_id IS NOT NULL) THEN RAISE EXCEPTION 'Submitted states require one current submission and no open working revision.'; END IF;
                IF NEW.state='correction_requested' AND (NEW.current_submission_id IS NULL OR NEW.current_working_revision_id IS NULL) THEN RAISE EXCEPTION 'Correction state requires the submitted copy and an open working revision.'; END IF;
                {$extraChecks}
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
        SQL);
    }

    public function down(): void
    {
        if (DB::table('application_decision_stages')->exists()) {
            throw new RuntimeException('Preserve B1 decision history; downgrade requires separate disposition.');
        }
        $this->transitionFunction(false);
        DB::statement('DROP TRIGGER b1_application_insert ON applications');
        DB::statement('DROP FUNCTION IF EXISTS enforce_b1_application_insert()');
        Schema::table('applications', fn (Blueprint $t) => $t->dropConstrainedForeignId('final_decision_id'));
        Schema::drop('application_decision_stages');
        DB::unprepared("DROP FUNCTION IF EXISTS enforce_b1_stage(); DROP FUNCTION IF EXISTS enforce_b1_final_commit(); ALTER TABLE applications DROP CONSTRAINT applications_state_check; ALTER TABLE applications ADD CONSTRAINT applications_state_check CHECK (state IN ('draft','submitted','under_screening','correction_requested','resubmitted')); DROP INDEX applications_one_active_configuration; CREATE UNIQUE INDEX applications_one_active_configuration ON applications(institution_id,module_id,publication_manifest_id) WHERE state IN ('draft','submitted','under_screening','correction_requested','resubmitted');");
    }
};
