<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION validate_application_submission_binding() RETURNS trigger AS $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM applications a
                    JOIN application_working_revisions w ON w.id=NEW.working_revision_id
                    JOIN application_drafts d ON d.id=NEW.source_draft_id
                    WHERE a.id=NEW.application_id AND w.application_id=a.id AND w.application_draft_id=d.id
                      AND d.application_id=a.id AND a.module_id=NEW.module_id
                      AND a.publication_manifest_id=NEW.publication_manifest_id
                      AND a.institution_id=NEW.institution_id AND a.applicant_id=NEW.applicant_id
                      AND d.module_id=NEW.module_id AND d.publication_manifest_id=NEW.publication_manifest_id
                      AND d.institution_id=NEW.institution_id AND d.created_by=NEW.applicant_id
                ) THEN RAISE EXCEPTION 'Submission bindings do not match the application and working revision.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_submission_binding_guard BEFORE INSERT ON application_submissions FOR EACH ROW EXECUTE FUNCTION validate_application_submission_binding();

            CREATE OR REPLACE FUNCTION validate_submission_answer_binding() RETURNS trigger AS $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM application_submissions s
                    JOIN publication_manifests pm ON pm.id=s.publication_manifest_id
                    JOIN form_questions q ON q.id=NEW.form_question_id
                    JOIN form_sections fs ON fs.id=q.form_section_id
                    WHERE s.id=NEW.submission_id AND fs.form_version_id=pm.form_version_id AND q.key=NEW.question_key
                ) THEN RAISE EXCEPTION 'Submission answer is outside the exact manifest form version.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_submission_answer_binding_guard BEFORE INSERT ON application_submission_answers FOR EACH ROW EXECUTE FUNCTION validate_submission_answer_binding();

            CREATE OR REPLACE FUNCTION validate_submission_requirement_binding() RETURNS trigger AS $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM application_submissions s
                    JOIN publication_manifests pm ON pm.id=s.publication_manifest_id
                    JOIN documentary_requirements r ON r.id=NEW.documentary_requirement_id
                    WHERE s.id=NEW.submission_id AND r.requirement_set_version_id=pm.requirement_set_version_id
                ) THEN RAISE EXCEPTION 'Submission requirement is outside the exact manifest requirement set.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_submission_requirement_binding_guard BEFORE INSERT ON application_submission_requirements FOR EACH ROW EXECUTE FUNCTION validate_submission_requirement_binding();

            CREATE OR REPLACE FUNCTION validate_submission_evidence_binding() RETURNS trigger AS $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM application_submission_requirements sr
                    JOIN application_submissions s ON s.id=sr.submission_id
                    JOIN evidence_reference_revisions er ON er.id=NEW.evidence_reference_revision_id
                    JOIN evidence_references e ON e.id=er.evidence_reference_id
                    WHERE sr.id=NEW.submission_requirement_id
                      AND e.application_draft_id=s.source_draft_id
                      AND e.documentary_requirement_id=sr.documentary_requirement_id
                      AND er.action<>'removed'
                ) THEN RAISE EXCEPTION 'Submission evidence is outside the exact source draft or requirement.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_submission_evidence_binding_guard BEFORE INSERT ON application_submission_evidence_items FOR EACH ROW EXECUTE FUNCTION validate_submission_evidence_binding();

            CREATE OR REPLACE FUNCTION validate_correction_item_binding() RETURNS trigger AS $$ BEGIN
                IF NEW.target_kind='question' AND (NEW.form_question_id IS NULL OR NEW.documentary_requirement_id IS NOT NULL OR NOT EXISTS (
                    SELECT 1 FROM application_correction_requests cr JOIN application_submission_answers sa ON sa.submission_id=cr.submission_id
                    WHERE cr.id=NEW.correction_request_id AND sa.form_question_id=NEW.form_question_id
                )) THEN RAISE EXCEPTION 'Correction question target is outside the exact submission.';
                ELSIF NEW.target_kind='document_requirement' AND (NEW.documentary_requirement_id IS NULL OR NEW.form_question_id IS NOT NULL OR NOT EXISTS (
                    SELECT 1 FROM application_correction_requests cr JOIN application_submission_requirements sr ON sr.submission_id=cr.submission_id
                    WHERE cr.id=NEW.correction_request_id AND sr.documentary_requirement_id=NEW.documentary_requirement_id
                )) THEN RAISE EXCEPTION 'Correction requirement target is outside the exact submission.';
                ELSIF NEW.target_kind='general' AND (NEW.form_question_id IS NOT NULL OR NEW.documentary_requirement_id IS NOT NULL) THEN RAISE EXCEPTION 'General correction target must not bind configuration records.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_correction_item_binding_guard BEFORE INSERT ON application_correction_items FOR EACH ROW EXECUTE FUNCTION validate_correction_item_binding();

            CREATE OR REPLACE FUNCTION protect_application_reviewer_assignment() RETURNS trigger AS $$ BEGIN
                IF TG_OP='INSERT' THEN
                    IF NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.reviewer_id AND is_synthetic=true)
                       OR NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.granted_by AND is_synthetic=true)
                       OR NEW.starts_at IS NULL OR (NEW.ends_at IS NOT NULL AND NEW.ends_at<=NEW.starts_at)
                    THEN RAISE EXCEPTION 'Application reviewer assignments require synthetic users and valid dates.'; END IF;
                    RETURN NEW;
                END IF;
                IF OLD.reviewer_id<>NEW.reviewer_id OR OLD.region_id<>NEW.region_id OR OLD.module_id<>NEW.module_id
                   OR OLD.starts_at<>NEW.starts_at OR OLD.ends_at IS DISTINCT FROM NEW.ends_at OR OLD.granted_by<>NEW.granted_by OR OLD.reason<>NEW.reason
                   OR OLD.created_at<>NEW.created_at
                THEN RAISE EXCEPTION 'Application reviewer assignment grants are immutable.'; END IF;
                IF OLD.revoked_at IS NOT NULL OR NEW.revoked_at IS NULL OR NEW.revoked_by IS NULL OR NULLIF(BTRIM(NEW.revocation_reason),'') IS NULL
                THEN RAISE EXCEPTION 'Only a complete one-time revocation may update an assignment.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_reviewer_assignment_guard BEFORE INSERT OR UPDATE OR DELETE ON synthetic_application_reviewer_assignments FOR EACH ROW EXECUTE FUNCTION protect_application_reviewer_assignment();
        SQL);
    }

    public function down(): void
    {
        foreach ([
            'application_reviewer_assignment_guard ON synthetic_application_reviewer_assignments',
            'application_correction_item_binding_guard ON application_correction_items',
            'application_submission_evidence_binding_guard ON application_submission_evidence_items',
            'application_submission_requirement_binding_guard ON application_submission_requirements',
            'application_submission_answer_binding_guard ON application_submission_answers',
            'application_submission_binding_guard ON application_submissions',
        ] as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS {$trigger}");
        }
        foreach ([
            'protect_application_reviewer_assignment', 'validate_correction_item_binding', 'validate_submission_evidence_binding',
            'validate_submission_requirement_binding', 'validate_submission_answer_binding', 'validate_application_submission_binding',
        ] as $function) {
            DB::statement("DROP FUNCTION IF EXISTS {$function}() CASCADE");
        }
    }
};
