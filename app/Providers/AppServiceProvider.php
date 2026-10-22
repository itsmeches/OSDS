<?php

namespace App\Providers;

use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\Region;
use App\Models\ScopedAuthorizationGrant;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Observers\CoreIdentityObserver;
use Illuminate\Foundation\Console\ServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (PHP_OS_FAMILY === 'Windows' && $this->app->runningInConsole()) {
            // Laravel filters the dev server's environment. PHP multipart
            // uploads need the same writable temporary directory as the parent.
            ServeCommand::$passthroughVariables = array_values(array_unique([
                ...ServeCommand::$passthroughVariables, 'TEMP', 'TMP', 'TMPDIR',
            ]));
        }
        foreach ([User::class, Region::class, Institution::class, UserRegionAssignment::class, InstitutionRepresentative::class, ScopedAuthorizationGrant::class] as $model) {
            $model::observe(CoreIdentityObserver::class);
        }
    }
}
