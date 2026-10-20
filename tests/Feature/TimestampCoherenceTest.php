<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TimestampCoherenceTest extends TestCase
{
    public function test_non_utc_startup_defaults_reuse_and_reconnect_establish_utc_sessions(): void
    {
        $config = config('database.connections.pgsql');
        $this->assertSame('osds_test', $config['database']);
        $config['server_options'] = ['timezone' => 'Asia/Kuala_Lumpur'];
        config(['database.connections.sec006_utc' => $config]);
        try {
            $connection = DB::connection('sec006_utc');
            $first = $connection->getPdo();
            $this->assertSame('UTC', $connection->selectOne('SHOW TIME ZONE')->TimeZone);
            $this->assertSame($first, $connection->getPdo());
            DB::disconnect('sec006_utc');
            DB::reconnect('sec006_utc');
            $this->assertNotSame($first, DB::connection('sec006_utc')->getPdo());
            $this->assertSame('UTC', DB::connection('sec006_utc')->selectOne('SHOW TIME ZONE')->TimeZone);
        } finally {
            DB::purge('sec006_utc');
        }
    }

    public function test_read_only_legacy_binding_assessment_does_not_rewrite_history(): void
    {
        $config = config('database.connections.pgsql');
        $this->assertSame('osds_test', $config['database']);
        $config['timezone'] = 'Asia/Kuala_Lumpur';
        config(['database.connections.sec006_legacy' => $config]);
        try {
            $legacy = DB::connection('sec006_legacy');
            $instant = now();
            $naive = $instant->format('Y-m-d H:i:s');
            $explicit = $instant->toIso8601String();
            $sql = 'SELECT extract(epoch FROM (CAST(? AS timestamptz)-CAST(? AS timestamptz))) AS skew, CAST(? AS timestamp)::text AS wall_time, extract(epoch FROM CURRENT_TIMESTAMP) AS actual_epoch';
            $old = $legacy->selectOne($sql, [$naive, $explicit, $naive]);
            $new = DB::selectOne($sql, [$naive, $explicit, $naive]);
            $this->assertEqualsWithDelta(-28800, (float) $old->skew, 1);
            $this->assertEqualsWithDelta(0, (float) $new->skew, 1);
            $this->assertSame($old->wall_time, $new->wall_time, 'A timestamp-without-zone literal does not acquire a zone or get rewritten.');
            $this->assertEqualsWithDelta((float) $old->actual_epoch, (float) $new->actual_epoch, 5, 'Database clock/default instants do not change with session display zone.');
        } finally {
            DB::purge('sec006_legacy');
        }
    }

    public function test_existing_timestamp_column_types_are_preserved(): void
    {
        foreach (['users' => 'created_at', 'user_region_assignments' => 'starts_at', 'institution_representatives' => 'ends_at', 'publication_manifests' => 'effective_from', 'application_submissions' => 'submitted_at', 'evidence_reference_revisions' => 'created_at', 'synthetic_evidence_review_findings' => 'reviewed_at', 'audit_events' => 'created_at'] as $table => $column) {
            $type = DB::selectOne('SELECT data_type FROM information_schema.columns WHERE table_schema=current_schema() AND table_name=? AND column_name=?', [$table, $column]);
            $this->assertSame($table === 'users' ? 'timestamp without time zone' : 'timestamp with time zone', $type?->data_type, $table.'.'.$column);
        }
    }

    public function test_php_datetime_bindings_agree_with_postgresql_current_time(): void
    {
        // Read-only probe against PHPUnit's isolated PostgreSQL connection.
        $bound = DB::connection()->prepareBindings([now()])[0];
        $probe = DB::selectOne('SELECT current_setting(\'TimeZone\') AS session_zone, abs(extract(epoch FROM (CAST(? AS timestamptz) - CURRENT_TIMESTAMP))) AS skew_seconds', [$bound]);
        $this->assertLessThan(5, (float) $probe->skew_seconds, 'PHP datetime binding and PostgreSQL CURRENT_TIMESTAMP disagree; session zone: '.$probe->session_zone);
    }
}
