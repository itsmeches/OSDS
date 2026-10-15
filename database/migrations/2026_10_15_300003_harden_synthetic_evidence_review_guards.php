<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_synthetic_review_case_binding() RETURNS trigger AS $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM application_drafts ad JOIN institutions i ON i.id=ad.institution_id WHERE ad.id=NEW.application_draft_id AND ad.publication_manifest_id=NEW.publication_manifest_id AND ad.module_id=NEW.module_id AND ad.institution_id=NEW.institution_id AND ad.created_by=NEW.applicant_id AND i.primary_region_id=NEW.region_id) THEN RAISE EXCEPTION 'Review copy does not match its draft binding.'; END IF;
                RETURN NEW; END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER synthetic_review_case_binding_guard BEFORE INSERT OR UPDATE ON synthetic_evidence_review_cases FOR EACH ROW EXECUTE FUNCTION enforce_synthetic_review_case_binding();
            CREATE OR REPLACE FUNCTION enforce_synthetic_review_requirement_binding() RETURNS trigger AS $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM synthetic_evidence_review_cases c JOIN publication_manifests pm ON pm.id=c.publication_manifest_id JOIN documentary_requirements dr ON dr.id=NEW.documentary_requirement_id WHERE c.id=NEW.case_id AND dr.requirement_set_version_id=pm.requirement_set_version_id) THEN RAISE EXCEPTION 'Review requirement does not belong to the exact manifest.'; END IF;
                RETURN NEW; END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER synthetic_review_requirement_binding_guard BEFORE INSERT OR UPDATE ON synthetic_evidence_review_requirements FOR EACH ROW EXECUTE FUNCTION enforce_synthetic_review_requirement_binding();
            CREATE OR REPLACE FUNCTION enforce_synthetic_review_item_binding() RETURNS trigger AS $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM synthetic_evidence_review_requirements cr JOIN synthetic_evidence_review_cases c ON c.id=cr.case_id JOIN evidence_reference_revisions rr ON rr.id=NEW.evidence_reference_revision_id JOIN evidence_references er ON er.id=rr.evidence_reference_id WHERE cr.id=NEW.case_requirement_id AND er.application_draft_id=c.application_draft_id AND er.documentary_requirement_id=cr.documentary_requirement_id AND rr.action <> 'removed') THEN RAISE EXCEPTION 'Review item is outside the copied draft requirement.'; END IF;
                RETURN NEW; END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER synthetic_review_item_binding_guard BEFORE INSERT OR UPDATE ON synthetic_evidence_review_items FOR EACH ROW EXECUTE FUNCTION enforce_synthetic_review_item_binding();
            CREATE OR REPLACE FUNCTION enforce_synthetic_finding_revision() RETURNS trigger AS $$ BEGIN
                IF NEW.evidence_reference_revision_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM synthetic_evidence_review_items WHERE case_requirement_id=NEW.case_requirement_id AND evidence_reference_revision_id=NEW.evidence_reference_revision_id) THEN RAISE EXCEPTION 'Finding revision is not part of the immutable review copy.'; END IF;
                RETURN NEW; END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER synthetic_finding_revision_guard BEFORE INSERT ON synthetic_evidence_review_findings FOR EACH ROW EXECUTE FUNCTION enforce_synthetic_finding_revision();
            CREATE OR REPLACE FUNCTION enforce_synthetic_reviewer_assignment() RETURNS trigger AS $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM users WHERE id=NEW.reviewer_id AND is_synthetic=TRUE) THEN RAISE EXCEPTION 'Reviewer assignment requires a synthetic identity.'; END IF;
                IF TG_OP='UPDATE' AND (OLD.reviewer_id<>NEW.reviewer_id OR OLD.region_id<>NEW.region_id OR OLD.module_id<>NEW.module_id OR OLD.starts_at<>NEW.starts_at OR OLD.ends_at IS DISTINCT FROM NEW.ends_at OR OLD.granted_by<>NEW.granted_by OR OLD.reason<>NEW.reason OR OLD.revoked_at IS NOT NULL OR NEW.revoked_at IS NULL) THEN RAISE EXCEPTION 'Reviewer assignments are immutable except for one revocation.'; END IF;
                RETURN NEW; END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER synthetic_reviewer_assignment_guard BEFORE INSERT OR UPDATE ON synthetic_evidence_reviewer_assignments FOR EACH ROW EXECUTE FUNCTION enforce_synthetic_reviewer_assignment();
            CREATE TRIGGER synthetic_reviewer_assignment_no_delete BEFORE DELETE ON synthetic_evidence_reviewer_assignments FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS enforce_synthetic_reviewer_assignment() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS enforce_synthetic_finding_revision() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS enforce_synthetic_review_item_binding() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS enforce_synthetic_review_requirement_binding() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS enforce_synthetic_review_case_binding() CASCADE');
    }
};
