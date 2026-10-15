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
        Schema::table('evidence_reference_revisions', fn (Blueprint $t) => $t->uuid('public_id')->nullable()->unique());
        DB::table('evidence_reference_revisions')->orderBy('id')->each(fn ($r) => DB::table('evidence_reference_revisions')->where('id', $r->id)->update(['public_id' => (string) Str::uuid()]));
        Schema::table('evidence_reference_revisions', fn (Blueprint $t) => $t->uuid('public_id')->nullable(false)->change());
    }

    public function down(): void
    {
        Schema::table('evidence_reference_revisions', fn (Blueprint $t) => $t->dropColumn('public_id'));
    }
};
