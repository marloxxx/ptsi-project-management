<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Policies\ActivityLogPolicy;
use App\Application\Policies\RolePolicy;
use App\Application\Policies\SprintPolicy;
use App\Application\Policies\UnitPolicy;
use App\Application\Policies\UserPolicy;
use App\Models\Sprint;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Unit::class => UnitPolicy::class,
        Activity::class => ActivityLogPolicy::class,
        Sprint::class => SprintPolicy::class,
    ];
}
