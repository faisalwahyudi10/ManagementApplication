<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Policies\ActivityPolicy;
use App\Policies\ExceptionPolicy;
use BezhanSalleh\FilamentExceptions\Models\Exception;
use Illuminate\Support\Facades\Gate;
use Spatie\Activitylog\Models\Activity;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::after(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : false;
        });

        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Exception::class, ExceptionPolicy::class);
    }
}