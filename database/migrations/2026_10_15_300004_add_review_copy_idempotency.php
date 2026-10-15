<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('synthetic_evidence_review_cases', fn (Blueprint $table) => $table->uuid('idempotency_key')->nullable());
        DB::statement('DROP TRIGGER IF EXISTS synthetic_evidence_review_cases_append_only ON synthetic_evidence_review_cases');
        DB::table('synthetic_evidence_review_cases')->orderBy('id')->each(fn ($row) => DB::table('synthetic_evidence_review_cases')->where('id', $row->id)->update(['idempotency_key' => (string) Str::uuid()]));
        Schema::table('synthetic_evidence_review_cases', function (Blueprint $table) {
            $table->uuid('idempotency_key')->nullable(false)->change();
            $table->unique(['application_draft_id', 'applicant_id', 'idempotency_key'], 'synthetic_review_copy_idempotency_unique');
        });
        DB::statement('CREATE TRIGGER synthetic_evidence_review_cases_append_only BEFORE UPDATE OR DELETE ON synthetic_evidence_review_cases FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation()');
    }

    public function down(): void
    {
        Schema::table('synthetic_evidence_review_cases', fn (Blueprint $table) => $table->dropColumn('idempotency_key'));
    }
};
