<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_issuance_form_revisions', function (Blueprint $table) {
            $table->id();
            $table->jsonb('definition');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });
        DB::statement('CREATE TRIGGER demo_issuance_forms_append_only BEFORE UPDATE OR DELETE ON demo_issuance_form_revisions FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation()');
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_issuance_form_revisions');
    }
};
