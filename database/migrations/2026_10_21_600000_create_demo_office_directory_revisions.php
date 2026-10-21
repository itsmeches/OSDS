<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demo_office_directory_revisions', function (Blueprint $table) {
            $table->id();
            $table->jsonb('offices');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();
        });
        DB::statement('CREATE TRIGGER demo_office_directory_append_only BEFORE UPDATE OR DELETE ON demo_office_directory_revisions FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation()');
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_office_directory_revisions');
    }
};
