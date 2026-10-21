<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('downloadable_forms', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('title', 160);
            $table->text('description')->nullable();
            $table->foreignId('module_id')->nullable()->constrained('modules')->restrictOnDelete();
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestampTz('withdrawn_at')->nullable();
            $table->timestampsTz();
            $table->index(['withdrawn_at', 'module_id']);
        });
        Schema::create('downloadable_form_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('downloadable_form_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('version_label', 80);
            $table->string('path')->unique();
            $table->string('original_name');
            $table->string('extension', 8);
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
            $table->unique(['downloadable_form_id', 'version_number']);
        });
        Schema::table('downloadable_forms', fn (Blueprint $table) => $table->foreign('current_version_id')->references('id')->on('downloadable_form_versions')->restrictOnDelete());
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION validate_downloadable_form_version() RETURNS trigger AS $$ BEGIN
                IF NEW.current_version_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM downloadable_form_versions v WHERE v.id=NEW.current_version_id AND v.downloadable_form_id=NEW.id) THEN RAISE EXCEPTION 'Download version belongs to another form.'; END IF;
                RETURN NEW;
            END; $$ LANGUAGE plpgsql;
            CREATE TRIGGER downloadable_form_version_binding BEFORE INSERT OR UPDATE ON downloadable_forms FOR EACH ROW EXECUTE FUNCTION validate_downloadable_form_version();
            CREATE TRIGGER downloadable_form_versions_append_only BEFORE UPDATE OR DELETE ON downloadable_form_versions FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
        SQL);
    }

    public function down(): void
    {
        Schema::table('downloadable_forms', fn (Blueprint $table) => $table->dropForeign(['current_version_id']));
        Schema::dropIfExists('downloadable_form_versions');
        Schema::dropIfExists('downloadable_forms');
        DB::statement('DROP FUNCTION IF EXISTS validate_downloadable_form_version()');
    }
};
