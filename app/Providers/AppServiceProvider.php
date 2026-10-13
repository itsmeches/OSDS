<?php

namespace App\Providers;

use App\Models\Institution;
use App\Models\InstitutionRepresentative;
use App\Models\Region;
use App\Models\ScopedAuthorizationGrant;
use App\Models\User;
use App\Models\UserRegionAssignment;
use App\Observers\CoreIdentityObserver;
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
        foreach ([User::class, Region::class, Institution::class, UserRegionAssignment::class, InstitutionRepresentative::class, ScopedAuthorizationGrant::class] as $model) {
            $model::observe(CoreIdentityObserver::class);
        }
    }
}
