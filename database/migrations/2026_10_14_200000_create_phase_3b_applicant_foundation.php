<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('organization_type', 40)->default('hei')->index();
            $table->boolean('is_synthetic')->default(false)->index();
            $table->string('display_name')->nullable();
        });
        DB::statement("ALTER TABLE institutions ADD CONSTRAINT institutions_organization_type_check CHECK (organization_type IN ('hei','athletic_association','national_sports_association','private_organization','government_entity'))");
        DB::statement('ALTER TABLE publication_manifests DROP CONSTRAINT publication_manifests_channel_check');
        DB::statement("ALTER TABLE publication_manifests ADD CONSTRAINT publication_manifests_channel_check CHECK (channel IN ('review_sandbox','applicant_sandbox'))");
        Schema::create('applicant_sandbox_eligibility_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->restrictOnDelete();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('reason', 500);
            $table->timestampsTz();
            $table->index(['institution_id', 'module_id', 'revoked_at'], 'applicant_eligibility_lookup');
        });
        DB::statement('ALTER TABLE applicant_sandbox_eligibility_grants ADD CONSTRAINT applicant_eligibility_dates_check CHECK (ends_at IS NULL OR ends_at > starts_at)');
        DB::statement('CREATE UNIQUE INDEX active_applicant_sandbox_eligibility_unique ON applicant_sandbox_eligibility_grants (institution_id, module_id) WHERE revoked_at IS NULL');
        Schema::create('application_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('module_id')->constrained()->restrictOnDelete();
            $table->foreignId('publication_manifest_id')->constrained()->restrictOnDelete();
            $table->foreignId('institution_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('lock_version')->default(1);
            $table->timestampTz('last_saved_at')->nullable();
            $table->timestampsTz();
            $table->index(['created_by', 'status', 'updated_at']);
            $table->index(['institution_id', 'module_id', 'status']);
            $table->index('publication_manifest_id');
        });
        DB::statement("ALTER TABLE application_drafts ADD CONSTRAINT application_drafts_status_check CHECK (status = 'draft')");
        Schema::create('application_draft_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('form_question_id')->constrained()->restrictOnDelete();
            $table->jsonb('value');
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->unique(['application_draft_id', 'form_question_id'], 'application_draft_answer_unique');
        });
        Schema::create('application_draft_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('application_draft_id')->constrained()->restrictOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('event', 60);
            $table->unsignedBigInteger('lock_version');
            $table->uuid('correlation_id')->index();
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['application_draft_id', 'created_at']);
        });
        Schema::create('application_draft_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('idempotency_key');
            $table->string('operation', 40);
            $table->unsignedBigInteger('result_lock_version');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['application_draft_id', 'user_id', 'operation', 'idempotency_key'], 'draft_idempotency_unique');
        });
        DB::unprepared("CREATE OR REPLACE FUNCTION prevent_application_draft_rebinding() RETURNS trigger AS \$\$ BEGIN IF NEW.module_id <> OLD.module_id OR NEW.publication_manifest_id <> OLD.publication_manifest_id OR NEW.institution_id <> OLD.institution_id OR NEW.created_by <> OLD.created_by THEN RAISE EXCEPTION 'Application draft bindings are immutable.'; END IF; RETURN NEW; END; \$\$ LANGUAGE plpgsql; CREATE TRIGGER application_draft_binding_immutable BEFORE UPDATE ON application_drafts FOR EACH ROW EXECUTE FUNCTION prevent_application_draft_rebinding(); CREATE TRIGGER application_draft_events_append_only BEFORE UPDATE OR DELETE ON application_draft_events FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();");
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS prevent_application_draft_rebinding() CASCADE');
        Schema::dropIfExists('application_draft_idempotency_keys');
        Schema::dropIfExists('application_draft_events');
        Schema::dropIfExists('application_draft_answers');
        Schema::dropIfExists('application_drafts');
        Schema::dropIfExists('applicant_sandbox_eligibility_grants');
        DB::statement('ALTER TABLE publication_manifests DROP CONSTRAINT publication_manifests_channel_check');
        DB::statement("ALTER TABLE publication_manifests ADD CONSTRAINT publication_manifests_channel_check CHECK (channel = 'review_sandbox')");
        Schema::table('institutions', fn (Blueprint $table) => $table->dropColumn(['organization_type', 'is_synthetic', 'display_name']));
    }
};
