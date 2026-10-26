<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demonstration_document_versions', function (Blueprint $t) {
            $t->id();
            $t->string('kind');
            $t->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $t->foreignId('region_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('directory_revision_id')->nullable()->constrained('demo_office_directory_revisions')->restrictOnDelete();
            $t->unsignedInteger('version');
            $t->string('state')->default('draft');
            $t->jsonb('content');
            $t->char('content_hash', 64);
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->unsignedInteger('lock_version')->default(1);
            $t->timestampsTz();
        });
        DB::statement("CREATE UNIQUE INDEX b3_published_version ON demonstration_document_versions (kind,module_id,COALESCE(region_id,0)) WHERE state='published'");
        DB::statement('CREATE UNIQUE INDEX b3_version_number ON demonstration_document_versions (kind,module_id,COALESCE(region_id,0),version)');
        Schema::create('demonstration_documents', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->foreignId('request_id')->constrained('issuance_requests')->restrictOnDelete();
            $t->foreignId('application_id')->constrained()->restrictOnDelete();
            $t->foreignId('submission_id')->constrained('application_submissions')->restrictOnDelete();
            $t->foreignId('decision_id')->constrained('application_decision_stages')->restrictOnDelete();
            $t->foreignId('definition_id')->constrained('issuance_definitions')->restrictOnDelete();
            $t->foreignId('module_id')->constrained('modules')->restrictOnDelete();
            $t->foreignId('region_id')->constrained()->restrictOnDelete();
            $t->foreignId('institution_id')->constrained()->restrictOnDelete();
            $t->string('certificate_type');
            $t->foreignId('template_id')->constrained('demonstration_document_versions')->restrictOnDelete();
            $t->foreignId('personnel_id')->constrained('demonstration_document_versions')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->uuid('attempt_key');
            $t->string('state');
            $t->jsonb('source_snapshot');
            $t->jsonb('render_snapshot');
            $t->timestampTz('started_at');
            $t->timestampTz('completed_at')->nullable();
            $t->string('storage_key')->nullable();
            $t->char('checksum', 64)->nullable();
            $t->unsignedBigInteger('byte_size')->nullable();
            $t->unsignedInteger('page_count')->nullable();
            $t->unique(['request_id', 'certificate_type']);
            $t->timestampsTz();
        });
        Schema::create('demonstration_document_attempts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('demonstration_documents')->restrictOnDelete();
            $t->uuid('attempt_key')->unique();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('template_id')->constrained('demonstration_document_versions')->restrictOnDelete();
            $t->foreignId('personnel_id')->constrained('demonstration_document_versions')->restrictOnDelete();
            $t->jsonb('render_snapshot');
            $t->timestampTz('created_at')->useCurrent();
        });
        Schema::create('demonstration_document_history', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->nullable()->constrained('demonstration_documents')->restrictOnDelete();
            $t->foreignId('version_id')->nullable()->constrained('demonstration_document_versions')->restrictOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('event');
            $t->uuid('attempt_key')->nullable();
            $t->timestampTz('created_at')->useCurrent();
        });
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION guard_b3_version() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE office jsonb;
BEGIN
 IF TG_OP='DELETE' THEN RAISE EXCEPTION 'B3 versions cannot be deleted'; END IF;
 IF NEW.kind NOT IN ('template','personnel') OR NEW.state NOT IN ('draft','reviewed','published','withdrawn') THEN RAISE EXCEPTION 'Invalid version'; END IF;
 IF NOT EXISTS(SELECT 1 FROM modules WHERE id=NEW.module_id AND code IN ('registration','acsa')) OR NEW.content_hash !~ '^[0-9a-f]{64}$' THEN RAISE EXCEPTION 'Invalid module or hash'; END IF;
 IF NEW.kind='template' AND (NEW.content->>'layout' IS DISTINCT FROM 'b3-letter-v1' OR jsonb_typeof(NEW.content->'wording') IS DISTINCT FROM 'string' OR length(NEW.content->>'wording') NOT BETWEEN 1 AND 1500 OR (NEW.content->>'wording') ~ '[<>]' OR jsonb_typeof(NEW.content->'fields') IS DISTINCT FROM 'array' OR jsonb_array_length(NEW.content->'fields')>20 OR (NEW.content-ARRAY['layout','wording','fields'])<>'{}'::jsonb) THEN RAISE EXCEPTION 'Invalid controlled template'; END IF;
 IF TG_OP='INSERT' AND NEW.state<>'draft' THEN RAISE EXCEPTION 'Start in draft'; END IF;
 IF TG_OP='UPDATE' THEN
  IF (to_jsonb(NEW)-ARRAY['state','lock_version','updated_at']) IS DISTINCT FROM (to_jsonb(OLD)-ARRAY['state','lock_version','updated_at']) OR NEW.lock_version<>OLD.lock_version+1 OR NOT ((OLD.state='draft' AND NEW.state='reviewed') OR (OLD.state='reviewed' AND NEW.state='published') OR (OLD.state='published' AND NEW.state='withdrawn')) THEN RAISE EXCEPTION 'Immutable version or invalid transition'; END IF;
 END IF;
 IF NEW.kind='template' AND (NEW.region_id IS NOT NULL OR NEW.directory_revision_id IS NOT NULL) THEN RAISE EXCEPTION 'Invalid template binding'; END IF;
 IF NEW.kind='personnel' THEN
  SELECT offices->(NEW.content->>'office_key') INTO office FROM demo_office_directory_revisions WHERE id=NEW.directory_revision_id;
  IF NEW.region_id IS NULL OR office IS NULL OR office IS DISTINCT FROM NEW.content->'office' THEN RAISE EXCEPTION 'Invalid directory snapshot'; END IF;
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER b3_version_guard BEFORE INSERT OR UPDATE OR DELETE ON demonstration_document_versions FOR EACH ROW EXECUTE FUNCTION guard_b3_version();
CREATE OR REPLACE FUNCTION guard_b3_document() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE r issuance_requests; a applications; d application_decision_stages; t demonstration_document_versions; p demonstration_document_versions;
BEGIN
 IF TG_OP='DELETE' THEN RAISE EXCEPTION 'B3 originals cannot be deleted'; END IF;
 IF TG_OP='UPDATE' THEN
  IF OLD.state='completed' THEN RAISE EXCEPTION 'Completed original is immutable'; END IF;
  IF (to_jsonb(NEW)-ARRAY['state','template_id','personnel_id','actor_id','attempt_key','render_snapshot','started_at','completed_at','storage_key','checksum','byte_size','page_count','updated_at']) IS DISTINCT FROM (to_jsonb(OLD)-ARRAY['state','template_id','personnel_id','actor_id','attempt_key','render_snapshot','started_at','completed_at','storage_key','checksum','byte_size','page_count','updated_at']) THEN RAISE EXCEPTION 'Immutable source binding'; END IF;
  IF NOT ((OLD.state='processing' AND NEW.state IN ('failed','completed')) OR (OLD.state='failed' AND NEW.state='processing')) THEN RAISE EXCEPTION 'Invalid document transition'; END IF;
  IF OLD.state='processing' AND (NEW.attempt_key<>OLD.attempt_key OR NEW.render_snapshot<>OLD.render_snapshot OR NEW.template_id<>OLD.template_id OR NEW.personnel_id<>OLD.personnel_id OR NEW.actor_id<>OLD.actor_id OR NEW.started_at<>OLD.started_at) THEN RAISE EXCEPTION 'Attempt binding changed'; END IF;
 ELSE
  IF NEW.state<>'processing' THEN RAISE EXCEPTION 'Document must start processing'; END IF;
 END IF;
 SELECT * INTO r FROM issuance_requests WHERE id=NEW.request_id;
 SELECT * INTO a FROM applications WHERE id=r.application_id;
 SELECT * INTO d FROM application_decision_stages WHERE id=r.decision_id;
 IF r.state IS DISTINCT FROM 'submitted' OR a.state IS DISTINCT FROM 'approved' OR d.action IS DISTINCT FROM 'approved' OR a.final_decision_id IS DISTINCT FROM r.decision_id OR a.current_submission_id IS DISTINCT FROM r.submission_id OR a.current_working_revision_id IS NOT NULL
 OR ROW(NEW.application_id,NEW.submission_id,NEW.decision_id,NEW.definition_id,NEW.module_id,NEW.region_id,NEW.institution_id,NEW.certificate_type) IS DISTINCT FROM ROW(r.application_id,r.submission_id,r.decision_id,r.definition_id,r.module_id,r.region_id,r.institution_id,r.certificate_type)
 OR NEW.source_snapshot IS DISTINCT FROM jsonb_build_object('identity',r.identity_snapshot,'definition',r.definition_snapshot,'answers',r.answers,'payload_hash',r.payload_hash) THEN RAISE EXCEPTION 'B3 source coherence'; END IF;
 SELECT * INTO t FROM demonstration_document_versions WHERE id=NEW.template_id;
 SELECT * INTO p FROM demonstration_document_versions WHERE id=NEW.personnel_id;
 IF NEW.render_snapshot->>'renderer' IS DISTINCT FROM 'b3-letter-v1' OR NEW.render_snapshot->>'date' IS DISTINCT FROM to_char(NEW.started_at AT TIME ZONE 'Asia/Manila','YYYY-MM-DD') OR (NEW.render_snapshot-ARRAY['renderer','template','personnel','identity','date'])<>'{}'::jsonb THEN RAISE EXCEPTION 'Invalid render contract'; END IF;
 IF NEW.state<>'failed' AND (t.kind<>'template' OR p.kind<>'personnel' OR t.state<>'published' OR p.state<>'published' OR t.module_id<>r.module_id OR p.module_id<>r.module_id OR p.region_id<>r.region_id OR NEW.render_snapshot->'template' IS DISTINCT FROM t.content OR NEW.render_snapshot->'personnel' IS DISTINCT FROM p.content OR NEW.render_snapshot->'identity' IS DISTINCT FROM r.identity_snapshot) THEN RAISE EXCEPTION 'B3 version coherence'; END IF;
 IF NEW.state='completed' THEN
  IF NEW.storage_key IS DISTINCT FROM ('demonstration-documents/'||NEW.public_id||'/'||NEW.attempt_key||'.pdf') OR NEW.checksum !~ '^[0-9a-f]{64}$' OR NEW.checksum IS NULL OR NEW.byte_size IS NULL OR NEW.byte_size<100 OR NEW.page_count IS NULL OR NEW.page_count<1 OR NEW.page_count>20 OR NEW.completed_at IS NULL THEN RAISE EXCEPTION 'Invalid completion metadata'; END IF;
 ELSE
  IF NEW.storage_key IS NOT NULL OR NEW.checksum IS NOT NULL OR NEW.byte_size IS NOT NULL OR NEW.page_count IS NOT NULL OR NEW.completed_at IS NOT NULL THEN RAISE EXCEPTION 'Uncompleted artifact metadata'; END IF;
 END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER b3_document_guard BEFORE INSERT OR UPDATE OR DELETE ON demonstration_documents FOR EACH ROW EXECUTE FUNCTION guard_b3_document();
CREATE OR REPLACE FUNCTION guard_b3_attempt() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE d demonstration_documents;
BEGIN
 SELECT * INTO d FROM demonstration_documents WHERE id=NEW.document_id;
 IF d.state<>'processing' OR ROW(NEW.attempt_key,NEW.actor_id,NEW.template_id,NEW.personnel_id) IS DISTINCT FROM ROW(d.attempt_key,d.actor_id,d.template_id,d.personnel_id) OR NEW.render_snapshot IS DISTINCT FROM d.render_snapshot THEN RAISE EXCEPTION 'Invalid B3 attempt binding'; END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER b3_attempt_binding BEFORE INSERT ON demonstration_document_attempts FOR EACH ROW EXECUTE FUNCTION guard_b3_attempt();
CREATE OR REPLACE FUNCTION require_b3_attempt() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE d demonstration_documents;
BEGIN
 SELECT * INTO d FROM demonstration_documents WHERE id=NEW.id;
 IF NOT EXISTS(SELECT 1 FROM demonstration_document_attempts a WHERE a.document_id=d.id AND a.attempt_key=d.attempt_key AND a.actor_id=d.actor_id AND a.template_id=d.template_id AND a.personnel_id=d.personnel_id AND a.render_snapshot=d.render_snapshot) THEN RAISE EXCEPTION 'B3 attempt required'; END IF;
 RETURN NEW;
END $$;
CREATE CONSTRAINT TRIGGER b3_attempt_required AFTER INSERT OR UPDATE ON demonstration_documents DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION require_b3_attempt();
CREATE TRIGGER b3_attempt_immutable BEFORE UPDATE OR DELETE ON demonstration_document_attempts FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
CREATE TRIGGER b3_history_immutable BEFORE UPDATE OR DELETE ON demonstration_document_history FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
SQL);
    }

    public function down(): void
    {
        if (DB::table('demonstration_document_history')->exists()) {
            throw new RuntimeException('Recorded B3 history requires separate disposition.');
        }
        Schema::dropIfExists('demonstration_document_history');
        Schema::dropIfExists('demonstration_document_attempts');
        Schema::dropIfExists('demonstration_documents');
        Schema::dropIfExists('demonstration_document_versions');
        DB::statement('DROP FUNCTION IF EXISTS guard_b3_document()');
        DB::statement('DROP FUNCTION IF EXISTS guard_b3_version()');
        DB::statement('DROP FUNCTION IF EXISTS guard_b3_attempt()');
        DB::statement('DROP FUNCTION IF EXISTS require_b3_attempt()');
    }
};
