<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_applicant_draft_manifest() RETURNS trigger AS $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM publication_manifests pm
                    WHERE pm.id = NEW.publication_manifest_id
                      AND pm.channel = 'applicant_sandbox'
                      AND pm.module_id = NEW.module_id
                ) THEN RAISE EXCEPTION 'Draft must match an applicant-sandbox manifest and module.'; END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_draft_manifest_guard BEFORE INSERT OR UPDATE ON application_drafts
                FOR EACH ROW EXECUTE FUNCTION enforce_applicant_draft_manifest();

            CREATE OR REPLACE FUNCTION enforce_draft_answer_form_version() RETURNS trigger AS $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM application_drafts ad
                    JOIN publication_manifests pm ON pm.id = ad.publication_manifest_id
                    JOIN form_questions fq ON fq.id = NEW.form_question_id
                    WHERE ad.id = NEW.application_draft_id AND fq.form_version_id = pm.form_version_id
                ) THEN RAISE EXCEPTION 'Answer question must belong to the draft manifest form version.'; END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER application_draft_answer_version_guard BEFORE INSERT OR UPDATE ON application_draft_answers
                FOR EACH ROW EXECUTE FUNCTION enforce_draft_answer_form_version();

            CREATE OR REPLACE FUNCTION enforce_synthetic_eligibility() RETURNS trigger AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM institutions WHERE id = NEW.institution_id AND is_synthetic = true)
                THEN RAISE EXCEPTION 'Applicant sandbox eligibility requires a synthetic organization.'; END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER applicant_eligibility_synthetic_guard BEFORE INSERT OR UPDATE ON applicant_sandbox_eligibility_grants
                FOR EACH ROW EXECUTE FUNCTION enforce_synthetic_eligibility();

            CREATE OR REPLACE FUNCTION prevent_drafted_organization_type_change() RETURNS trigger AS $$
            BEGIN
                IF NEW.organization_type <> OLD.organization_type AND EXISTS (SELECT 1 FROM application_drafts WHERE institution_id = OLD.id)
                THEN RAISE EXCEPTION 'Organization type cannot change after an applicant draft exists.'; END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER institutions_drafted_type_guard BEFORE UPDATE ON institutions
                FOR EACH ROW EXECUTE FUNCTION prevent_drafted_organization_type_change();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS enforce_applicant_draft_manifest() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS enforce_draft_answer_form_version() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS enforce_synthetic_eligibility() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS prevent_drafted_organization_type_change() CASCADE');
    }
};
