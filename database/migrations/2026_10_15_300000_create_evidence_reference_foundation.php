<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evidence_references', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('application_draft_id')->constrained()->restrictOnDelete();
            $table->foreignId('documentary_requirement_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();
            $table->index(['application_draft_id', 'documentary_requirement_id'], 'evidence_reference_requirement_lookup');
        });
        Schema::create('evidence_reference_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evidence_reference_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('revision_number');
            $table->string('action', 20);
            $table->foreignId('evidence_provider_type_id')->nullable()->constrained()->restrictOnDelete();
            $table->text('locator_ciphertext')->nullable();
            $table->string('encryption_key_version', 40)->nullable();
            $table->string('locator_fingerprint', 64)->nullable();
            $table->text('applicant_label_ciphertext')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('draft_lock_version');
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['evidence_reference_id', 'revision_number'], 'evidence_revision_sequence_unique');
            $table->index('locator_fingerprint');
        });
        Schema::create('evidence_mutation_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_draft_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('operation', 30);
            $table->uuid('idempotency_key');
            $table->unsignedBigInteger('result_lock_version');
            $table->foreignId('evidence_reference_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('evidence_reference_revision_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['application_draft_id', 'user_id', 'operation', 'idempotency_key'], 'evidence_idempotency_unique');
        });
        DB::statement("ALTER TABLE evidence_reference_revisions ADD CONSTRAINT evidence_revision_action_check CHECK (action IN ('added','replaced','removed'))");
        DB::statement("ALTER TABLE evidence_reference_revisions ADD CONSTRAINT evidence_revision_payload_check CHECK ((action = 'removed' AND locator_ciphertext IS NULL AND encryption_key_version IS NULL AND locator_fingerprint IS NULL AND evidence_provider_type_id IS NULL) OR (action IN ('added','replaced') AND locator_ciphertext IS NOT NULL AND encryption_key_version IS NOT NULL AND locator_fingerprint IS NOT NULL AND evidence_provider_type_id IS NOT NULL))");
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION enforce_evidence_reference_manifest_requirement() RETURNS trigger AS $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM application_drafts ad JOIN publication_manifests pm ON pm.id=ad.publication_manifest_id JOIN documentary_requirements dr ON dr.id=NEW.documentary_requirement_id WHERE ad.id=NEW.application_draft_id AND dr.requirement_set_version_id=pm.requirement_set_version_id) THEN RAISE EXCEPTION 'Evidence requirement does not belong to the draft manifest.'; END IF;
                RETURN NEW; END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER evidence_reference_manifest_guard BEFORE INSERT OR UPDATE ON evidence_references FOR EACH ROW EXECUTE FUNCTION enforce_evidence_reference_manifest_requirement();
            CREATE OR REPLACE FUNCTION enforce_evidence_revision_provider() RETURNS trigger AS $$ BEGIN
                IF NEW.action <> 'removed' AND NOT EXISTS (SELECT 1 FROM evidence_references er JOIN documentary_requirement_provider drp ON drp.documentary_requirement_id=er.documentary_requirement_id WHERE er.id=NEW.evidence_reference_id AND drp.evidence_provider_type_id=NEW.evidence_provider_type_id) THEN RAISE EXCEPTION 'Evidence provider is not accepted for this requirement.'; END IF;
                RETURN NEW; END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER evidence_revision_provider_guard BEFORE INSERT ON evidence_reference_revisions FOR EACH ROW EXECUTE FUNCTION enforce_evidence_revision_provider();
            CREATE TRIGGER evidence_references_append_only BEFORE UPDATE OR DELETE ON evidence_references FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
            CREATE TRIGGER evidence_reference_revisions_append_only BEFORE UPDATE OR DELETE ON evidence_reference_revisions FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS enforce_evidence_revision_provider() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS enforce_evidence_reference_manifest_requirement() CASCADE');
        Schema::dropIfExists('evidence_mutation_idempotency_keys');
        Schema::dropIfExists('evidence_reference_revisions');
        Schema::dropIfExists('evidence_references');
    }
};
