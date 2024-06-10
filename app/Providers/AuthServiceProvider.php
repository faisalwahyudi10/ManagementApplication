<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;

use App\Models\Permission;
use App\Policies\ActivityPolicy;
use App\Policies\ExceptionPolicy;
use Spatie\Activitylog\Models\Activity;
use BezhanSalleh\FilamentExceptions\Models\Exception;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
        Gate::before(function (Authorizable $user, string $ability) {
            if (! Str::contains($ability, ':')) {
                return null;
            }

            $permission = Permission::getPermission(['name' => $ability]);
            if (! $permission) {
                DB::transaction(function () use ($ability) {
                    $permission = Permission::create([
                        'name' => $ability,
                    ]);

                    $permission->assignRole('Super Admin');
                });
            }
        });

        Gate::after(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : false;
        });
    }
}