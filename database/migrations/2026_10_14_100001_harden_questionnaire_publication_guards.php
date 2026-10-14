<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_published_configuration_mutation() RETURNS trigger AS $$
            DECLARE parent_state text;
            BEGIN
                IF TG_TABLE_NAME = 'form_definitions' THEN
                    IF EXISTS (SELECT 1 FROM form_versions WHERE form_definition_id = OLD.id AND state IN ('published','retired')) THEN RAISE EXCEPTION 'Published configuration is immutable.'; END IF;
                    IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
                    RETURN NEW;
                ELSIF TG_TABLE_NAME = 'form_versions' THEN parent_state := OLD.state;
                ELSIF TG_TABLE_NAME = 'requirement_set_versions' THEN parent_state := OLD.state;
                ELSIF TG_TABLE_NAME = 'form_sections' THEN SELECT state INTO parent_state FROM form_versions WHERE id = CASE WHEN TG_OP = 'DELETE' THEN OLD.form_version_id ELSE NEW.form_version_id END;
                ELSIF TG_TABLE_NAME = 'form_questions' THEN SELECT state INTO parent_state FROM form_versions WHERE id = CASE WHEN TG_OP = 'DELETE' THEN OLD.form_version_id ELSE NEW.form_version_id END;
                ELSIF TG_TABLE_NAME = 'form_question_options' THEN SELECT fv.state INTO parent_state FROM form_versions fv JOIN form_questions fq ON fq.form_version_id = fv.id WHERE fq.id = CASE WHEN TG_OP = 'DELETE' THEN OLD.form_question_id ELSE NEW.form_question_id END;
                ELSIF TG_TABLE_NAME = 'documentary_requirements' THEN SELECT state INTO parent_state FROM requirement_set_versions WHERE id = CASE WHEN TG_OP = 'DELETE' THEN OLD.requirement_set_version_id ELSE NEW.requirement_set_version_id END;
                ELSIF TG_TABLE_NAME = 'documentary_requirement_provider' THEN SELECT rsv.state INTO parent_state FROM requirement_set_versions rsv JOIN documentary_requirements dr ON dr.requirement_set_version_id = rsv.id WHERE dr.id = CASE WHEN TG_OP = 'DELETE' THEN OLD.documentary_requirement_id ELSE NEW.documentary_requirement_id END;
                END IF;
                IF parent_state IN ('published','retired') THEN RAISE EXCEPTION 'Published configuration is immutable.'; END IF;
                IF TG_OP = 'UPDATE' THEN
                    IF TG_TABLE_NAME = 'form_versions' THEN
                        IF NEW.state = 'published' AND NOT EXISTS (SELECT 1 FROM publication_manifests WHERE form_version_id = NEW.id) THEN RAISE EXCEPTION 'A publication manifest is required.'; END IF;
                    ELSIF TG_TABLE_NAME = 'requirement_set_versions' THEN
                        IF NEW.state = 'published' AND NOT EXISTS (SELECT 1 FROM publication_manifests WHERE requirement_set_version_id = NEW.id) THEN RAISE EXCEPTION 'A publication manifest is required.'; END IF;
                    END IF;
                END IF;
                IF TG_OP = 'DELETE' THEN RETURN OLD; END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            DROP TRIGGER IF EXISTS form_definitions_immutable ON form_definitions;
            CREATE TRIGGER form_definitions_immutable BEFORE UPDATE OR DELETE ON form_definitions FOR EACH ROW EXECUTE FUNCTION prevent_published_configuration_mutation();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS form_definitions_immutable ON form_definitions');
    }
};
