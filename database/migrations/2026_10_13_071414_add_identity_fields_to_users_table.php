<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('public_id')->nullable()->unique()->after('id');
            $table->string('status', 20)->default('active')->index()->after('password');
            $table->timestampTz('deactivated_at')->nullable()->after('status');
            $table->foreignId('deactivated_by')->nullable()->after('deactivated_at')->constrained('users')->restrictOnDelete();
        });
        DB::statement('UPDATE users SET public_id = gen_random_uuid() WHERE public_id IS NULL');
        DB::statement('ALTER TABLE users ALTER COLUMN public_id SET NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deactivated_by');
            $table->dropColumn(['public_id', 'status', 'deactivated_at']);
        });
    }
};
