<?php

namespace App\Services\Applicants;

use Illuminate\Support\Facades\DB;

class ApplicantSandboxGuard
{
    public function allowed(): bool
    {
        if (! app()->environment(['local', 'testing', 'sandbox']) || app()->environment('production')) {
            return false;
        }
        if (app()->environment('testing')) {
            return true;
        }
        $database = (string) DB::connection()->getDatabaseName();

        return str_contains(strtolower($database), 'sandbox') || str_contains(strtolower($database), 'test');
    }
}
