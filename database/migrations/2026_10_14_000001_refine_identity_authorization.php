<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_status_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('pending_verification', 'active', 'suspended', 'inactive'))");

        Schema::table('institution_representatives', function (Blueprint $table) {
            $table->timestampTz('verified_at')->nullable()->after('granted_by');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->restrictOnDelete();
        });
        DB::statement('UPDATE institution_representatives SET verified_at = created_at WHERE verified_at IS NULL');

        Schema::create('account_status_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 24);
            $table->string('to_status', 24);
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->string('reason', 500);
            $table->uuid('correlation_id')->index();
            $table->timestampTz('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('nationwide_access_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at')->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('reason', 500);
            $table->timestamps();
            $table->index(['user_id', 'revoked_at', 'starts_at']);
        });
        DB::statement('ALTER TABLE nationwide_access_grants ADD CONSTRAINT nationwide_grant_dates_check CHECK (ends_at IS NULL OR ends_at > starts_at)');
        DB::statement('CREATE UNIQUE INDEX active_user_region_assignment_unique ON user_region_assignments (user_id, region_id) WHERE revoked_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX active_institution_representative_unique ON institution_representatives (user_id, institution_id) WHERE revoked_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX active_nationwide_access_unique ON nationwide_access_grants (user_id) WHERE revoked_at IS NULL');

        $modelType = DB::getPdo()->quote((new User)->getMorphClass());
        DB::statement("CREATE UNIQUE INDEX users_one_primary_role_unique ON model_has_roles (model_id, model_type) WHERE model_type = {$modelType}");
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION prevent_osds_append_only_mutation() RETURNS trigger AS $$
            BEGIN
                RAISE EXCEPTION 'Append-only records cannot be updated or deleted.';
            END;
            $$ LANGUAGE plpgsql;
            CREATE TRIGGER audit_events_append_only BEFORE UPDATE OR DELETE ON audit_events
                FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
            CREATE TRIGGER account_status_events_append_only BEFORE UPDATE OR DELETE ON account_status_events
                FOR EACH ROW EXECUTE FUNCTION prevent_osds_append_only_mutation();
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS account_status_events_append_only ON account_status_events');
        DB::statement('DROP TRIGGER IF EXISTS audit_events_append_only ON audit_events');
        DB::statement('DROP FUNCTION IF EXISTS prevent_osds_append_only_mutation()');
        DB::statement('DROP INDEX IF EXISTS users_one_primary_role_unique');
        Schema::dropIfExists('nationwide_access_grants');
        Schema::dropIfExists('account_status_events');
        Schema::table('institution_representatives', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn('verified_at');
        });
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_status_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status IN ('active', 'inactive'))");
    }
};
