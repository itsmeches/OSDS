<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->timestampsTz();
        });

        Schema::create('form_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->string('key', 100);
            $table->string('name');
            $table->string('audience', 40)->default('applicant');
            $table->foreignId('owner_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['module_id', 'key']);
        });

        Schema::create('form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_definition_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->unsignedInteger('configuration_revision')->default(1);
            $table->string('state', 20)->default('draft')->index();
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->string('configuration_hash', 64)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['form_definition_id', 'version_number']);
        });

        Schema::create('form_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_version_id')->constrained()->restrictOnDelete();
            $table->string('key', 100);
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order');
            $table->timestampsTz();
            $table->unique(['form_version_id', 'key']);
            $table->unique(['form_version_id', 'sort_order']);
        });

        Schema::create('form_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('form_section_id')->constrained()->restrictOnDelete();
            $table->string('key', 100);
            $table->string('type', 40);
            $table->string('label');
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order');
            $table->jsonb('validation_rules')->default('{}');
            $table->jsonb('condition_rules')->nullable();
            $table->timestampsTz();
            $table->unique(['form_version_id', 'key']);
            $table->unique(['form_section_id', 'sort_order']);
        });

        Schema::create('form_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_question_id')->constrained()->restrictOnDelete();
            $table->string('key', 100);
            $table->string('label');
            $table->unsignedInteger('sort_order');
            $table->timestampsTz();
            $table->unique(['form_question_id', 'key']);
            $table->unique(['form_question_id', 'sort_order']);
        });

        Schema::create('requirement_set_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->unsignedInteger('configuration_revision')->default(1);
            $table->string('state', 20)->default('draft')->index();
            $table->unsignedBigInteger('lock_version')->default(0);
            $table->string('configuration_hash', 64)->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['module_id', 'version_number']);
        });

        Schema::create('documentary_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_set_version_id')->constrained()->restrictOnDelete();
            $table->string('key', 100);
            $table->string('label');
            $table->text('instructions')->nullable();
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('minimum_evidence')->default(1);
            $table->unsignedSmallInteger('maximum_evidence')->default(1);
            $table->unsignedInteger('sort_order');
            $table->jsonb('condition_rules')->nullable();
            $table->timestampsTz();
            $table->unique(['requirement_set_version_id', 'key']);
            $table->unique(['requirement_set_version_id', 'sort_order']);
        });

        Schema::create('evidence_provider_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestampsTz();
        });

        Schema::create('documentary_requirement_provider', function (Blueprint $table) {
            $table->foreignId('documentary_requirement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evidence_provider_type_id')->constrained()->restrictOnDelete();
            $table->primary(['documentary_requirement_id', 'evidence_provider_type_id'], 'documentary_requirement_provider_primary');
        });

        Schema::create('configuration_review_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->foreignId('form_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('requirement_set_version_id')->constrained()->restrictOnDelete();
            $table->string('form_hash', 64);
            $table->string('requirement_hash', 64);
            $table->string('combined_hash', 64);
            $table->string('state', 20)->default('pending')->index();
            $table->foreignId('submitted_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('submitted_at');
            $table->timestampsTz();
        });

        Schema::create('configuration_review_approvals', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('configuration_review_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
            $table->string('outcome', 20);
            $table->string('approval_reference', 255);
            $table->text('comment')->nullable();
            $table->string('approved_hash', 64);
            $table->uuid('correlation_id')->index();
            $table->timestampTz('reviewed_at');
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create('publication_manifests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->foreignId('form_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('requirement_set_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('configuration_review_approval_id')->constrained()->restrictOnDelete();
            $table->string('channel', 32)->default('review_sandbox');
            $table->timestampTz('effective_from');
            $table->timestampTz('effective_until')->nullable();
            $table->string('form_hash', 64);
            $table->string('requirement_hash', 64);
            $table->string('combined_hash', 64);
            $table->foreignId('published_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('published_at');
            $table->timestampsTz();
            $table->index(['module_id', 'channel', 'effective_from']);
        });

        Schema::create('publication_manifest_retirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_manifest_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('retired_by')->constrained('users')->restrictOnDelete();
            $table->string('reason', 500);
            $table->uuid('correlation_id')->index();
            $table->timestampTz('retired_at');
            $table->timestampTz('created_at')->useCurrent();
        });

        DB::statement("ALTER TABLE form_versions ADD CONSTRAINT form_versions_state_check CHECK (state IN ('draft','in_review','published','retired'))");
        DB::statement("ALTER TABLE requirement_set_versions ADD CONSTRAINT requirement_set_versions_state_check CHECK (state IN ('draft','in_review','published','retired'))");
        DB::statement("ALTER TABLE form_questions ADD CONSTRAINT form_questions_type_check CHECK (type IN ('short_text','long_text','telephone','email','date','integer','decimal','single_choice','multiple_choice','yes_no','declaration'))");
        DB::statement('ALTER TABLE documentary_requirements ADD CONSTRAINT documentary_requirements_count_check CHECK (minimum_evidence <= maximum_evidence AND maximum_evidence > 0)');
        DB::statement("ALTER TABLE configuration_review_requests ADD CONSTRAINT configuration_review_requests_state_check CHECK (state IN ('pending','approved','rejected','invalidated'))");
        DB::statement("ALTER TABLE configuration_review_approvals ADD CONSTRAINT configuration_review_approvals_outcome_check CHECK (outcome IN ('approved','rejected'))");
        DB::statement("ALTER TABLE publication_manifests ADD CONSTRAINT publication_manifests_channel_check CHECK (channel = 'review_sandbox')");
        DB::statement('ALTER TABLE publication_manifests ADD CONSTRAINT publication_manifests_effective_dates_check CHECK (effective_until IS NULL OR effective_until > effective_from)');

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
            CREATE TRIGGER form_definitions_immutable BEFORE UPDATE OR DELETE ON form_definitions FOR EACH ROW EXECUTE FUNCTION prevent_published_configuration_mutation();
            CREATE TRIGGER form_versions_immutable BEFORE UPDATE OR DELETE ON form_versions FOR EACH ROW EXECUTE FUNCTION prevent_published_configuration_mutation();
            CREATE TRIGGER form_sections_immutable BEFORE INSERT OR UPDATE OR DELETE ON form_sections FOR EACH ROW EXECUTE FUNCTION prevent_published_configuration_mutation();
            CREATE TRIGGER form_questions_immutable BEFORE INSERT OR UPDATE OR DELETE ON form_questions FOR EACH ROW EXECUTE FUNCTION prevent_published_configuration_mutation();
            CREATE TRIGGER form_question_options_immutable BEFORE INSERT OR UPDATE OR DELETE ON form_question_options FOR EACH ROW EXECUTE FUNCTION prevent_published_configuration_mutation();
            CREATE TRIGGER requirement_set_versions_immutable BEFORE UPDATE OR DELETE ON requirement_set_versions FOR EACH ROW EXECUTE FUNCTION prevent_published_configuration_mutation();
            CREATE TRIGGER documentary_requirements_immutable BEFORE INSERT OR UPDATE OR DELETE ON documentary_requirements FOR EACH ROW EXECUTE FUNCTION prevent_published_configuration_mutation();
            CREATE TRIGGER documentary_requirement_provider_immutable BEFORE INSERT OR UPDATE OR DELETE ON documentary_requirement_provider FOR EACH ROW EXECUTE FUNCTION prevent_published_configuration_mutation();
            CREATE TRIGGER configuration_review_approvals_append_only BEFORE UPDATE OR DELETE ON configuration_review_approvals FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
            CREATE TRIGGER publication_manifests_append_only BEFORE UPDATE OR DELETE ON publication_manifests FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
            CREATE TRIGGER publication_manifest_retirements_append_only BEFORE UPDATE OR DELETE ON publication_manifest_retirements FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
            CREATE OR REPLACE FUNCTION enforce_question_section_version() RETURNS trigger AS $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM form_sections WHERE id = NEW.form_section_id AND form_version_id = NEW.form_version_id) THEN
                    RAISE EXCEPTION 'Question and section must belong to the same form version.';
                END IF;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER form_questions_section_version BEFORE INSERT OR UPDATE ON form_questions FOR EACH ROW EXECUTE FUNCTION enforce_question_section_version();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS prevent_published_configuration_mutation() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS enforce_question_section_version() CASCADE');
        Schema::dropIfExists('publication_manifest_retirements');
        Schema::dropIfExists('publication_manifests');
        Schema::dropIfExists('configuration_review_approvals');
        Schema::dropIfExists('configuration_review_requests');
        Schema::dropIfExists('documentary_requirement_provider');
        Schema::dropIfExists('evidence_provider_types');
        Schema::dropIfExists('documentary_requirements');
        Schema::dropIfExists('requirement_set_versions');
        Schema::dropIfExists('form_question_options');
        Schema::dropIfExists('form_questions');
        Schema::dropIfExists('form_sections');
        Schema::dropIfExists('form_versions');
        Schema::dropIfExists('form_definitions');
        Schema::dropIfExists('modules');
    }
};
