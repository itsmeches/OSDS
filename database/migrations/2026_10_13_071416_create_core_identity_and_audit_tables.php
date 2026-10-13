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
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code', 32)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('official_id')->nullable();
            $table->foreignId('primary_region_id')->nullable()->constrained('regions')->restrictOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
            $table->index(['primary_region_id', 'name']);
        });
        DB::statement('CREATE UNIQUE INDEX institutions_official_id_unique ON institutions (official_id) WHERE official_id IS NOT NULL');

        Schema::create('user_region_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->restrictOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'region_id', 'starts_at'], 'user_region_assignment_unique');
            $table->index(['user_id', 'region_id', 'revoked_at']);
        });

        Schema::create('institution_representatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained()->restrictOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'institution_id', 'starts_at'], 'institution_representative_unique');
            $table->index(['user_id', 'institution_id', 'revoked_at']);
        });

        Schema::create('scoped_authorization_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->restrictOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->restrictOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'permission_id', 'revoked_at'], 'scoped_grants_active_lookup');
        });

        Schema::create('audit_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('public_id')->unique();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('resource_type', 150)->nullable();
            $table->string('resource_id', 100)->nullable();
            $table->foreignId('region_id')->nullable()->constrained('regions')->restrictOnDelete();
            $table->foreignId('institution_id')->nullable()->constrained('institutions')->restrictOnDelete();
            $table->string('outcome', 20);
            $table->uuid('correlation_id')->index();
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['resource_type', 'resource_id', 'created_at'], 'audit_resource_timeline');
            $table->index(['actor_id', 'created_at']);
        });

        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('active', 'inactive'))");
        DB::statement("ALTER TABLE institutions ADD CONSTRAINT institutions_status_check CHECK (status IN ('active', 'inactive'))");
        DB::statement('ALTER TABLE user_region_assignments ADD CONSTRAINT user_region_dates_check CHECK (ends_at IS NULL OR ends_at > starts_at)');
        DB::statement('ALTER TABLE institution_representatives ADD CONSTRAINT institution_rep_dates_check CHECK (ends_at IS NULL OR ends_at > starts_at)');
        DB::statement('ALTER TABLE scoped_authorization_grants ADD CONSTRAINT scoped_grant_scope_check CHECK ((region_id IS NOT NULL)::int + (institution_id IS NOT NULL)::int = 1)');
        DB::statement('ALTER TABLE scoped_authorization_grants ADD CONSTRAINT scoped_grant_dates_check CHECK (ends_at IS NULL OR ends_at > starts_at)');
        DB::statement("ALTER TABLE audit_events ADD CONSTRAINT audit_outcome_check CHECK (outcome IN ('success', 'denied', 'failure'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('scoped_authorization_grants');
        Schema::dropIfExists('institution_representatives');
        Schema::dropIfExists('user_region_assignments');
        Schema::dropIfExists('institutions');
        Schema::dropIfExists('regions');
    }
};
