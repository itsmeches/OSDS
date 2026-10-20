<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_application_transition() RETURNS trigger AS $$ BEGIN
                IF NEW.state<>OLD.state AND NOT ((OLD.state='draft' AND NEW.state='submitted') OR (OLD.state IN ('submitted','resubmitted') AND NEW.state='under_screening') OR (OLD.state='under_screening' AND NEW.state='correction_requested') OR (OLD.state='correction_requested' AND NEW.state='resubmitted')) THEN RAISE EXCEPTION 'Invalid synthetic application transition.'; END IF;
                IF NEW.module_id<>OLD.module_id OR NEW.publication_manifest_id<>OLD.publication_manifest_id OR NEW.institution_id<>OLD.institution_id OR NEW.applicant_id<>OLD.applicant_id THEN RAISE EXCEPTION 'Application bindings are immutable.'; END IF;
                IF NEW.lock_version<OLD.lock_version OR (NEW.state<>OLD.state AND NEW.lock_version<>OLD.lock_version+1) THEN RAISE EXCEPTION 'Application transition requires the next lock version.'; END IF;
                IF NEW.current_submission_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM application_submissions s WHERE s.id=NEW.current_submission_id AND s.application_id=NEW.id) THEN RAISE EXCEPTION 'Current submission belongs to another application.'; END IF;
                IF NEW.current_working_revision_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM application_working_revisions w WHERE w.id=NEW.current_working_revision_id AND w.application_id=NEW.id AND w.state='open') THEN RAISE EXCEPTION 'Current working revision is not an open revision of this application.'; END IF;
                IF NEW.state='draft' AND NEW.current_submission_id IS NOT NULL THEN RAISE EXCEPTION 'A draft application cannot have a current submission.'; END IF;
                IF NEW.state IN ('submitted','under_screening','resubmitted') AND (NEW.current_submission_id IS NULL OR NEW.current_working_revision_id IS NOT NULL) THEN RAISE EXCEPTION 'Submitted states require one current submission and no open working revision.'; END IF;
                IF NEW.state='correction_requested' AND (NEW.current_submission_id IS NULL OR NEW.current_working_revision_id IS NULL) THEN RAISE EXCEPTION 'Correction state requires the submitted copy and an open working revision.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;

            CREATE OR REPLACE FUNCTION enforce_working_revision_consumption() RETURNS trigger AS $$ BEGIN
                IF TG_OP='DELETE' THEN RAISE EXCEPTION 'Working revisions cannot be deleted.'; END IF;
                IF NEW.application_id<>OLD.application_id OR NEW.application_draft_id<>OLD.application_draft_id OR NEW.revision_number<>OLD.revision_number OR NEW.purpose<>OLD.purpose OR NEW.source_submission_id IS DISTINCT FROM OLD.source_submission_id OR NEW.correction_request_id IS DISTINCT FROM OLD.correction_request_id OR NEW.created_by<>OLD.created_by THEN RAISE EXCEPTION 'Working revision bindings are immutable.'; END IF;
                IF OLD.state='consumed' OR NEW.state<>'consumed' OR NEW.consumed_at IS NULL OR NEW.consumed_by_submission_id IS NULL OR NOT EXISTS (SELECT 1 FROM application_submissions s WHERE s.id=NEW.consumed_by_submission_id AND s.application_id=NEW.application_id AND s.working_revision_id=NEW.id) THEN RAISE EXCEPTION 'Working revision consumption must bind its exact immutable submission.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_working_revision_update_guard BEFORE UPDATE OR DELETE ON application_working_revisions FOR EACH ROW EXECUTE FUNCTION enforce_working_revision_consumption();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS application_working_revision_update_guard ON application_working_revisions');
        DB::statement('DROP FUNCTION IF EXISTS enforce_working_revision_consumption() CASCADE');
    }
};
