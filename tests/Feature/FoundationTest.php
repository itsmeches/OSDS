<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FoundationTest extends TestCase
{
    public function test_application_boots_and_landing_page_renders(): void
    {
        $this->withoutVite();
        $this->get('/')->assertOk()->assertSee('OSDS Management Platform');
    }

    public function test_framework_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_postgresql_connection_and_baseline_migrations(): void
    {
        $this->assertSame('pgsql', DB::connection()->getDriverName());
        $this->assertSame(1, (int) DB::selectOne('select 1 as result')->result);

        foreach (['migrations', 'users', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing baseline table: {$table}");
        }
    }

    public function test_panel_requires_authentication(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }

    public function test_authenticated_user_has_no_panel_grant_yet(): void
    {
        $user = new User(['name' => 'Test User', 'email' => 'test@example.invalid']);
        $user->id = 1;

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }
}
