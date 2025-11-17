<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\Policies\ActivityLogPolicy;
use App\Application\Policies\EpicPolicy;
use App\Application\Policies\ProjectNotePolicy;
use App\Application\Policies\ProjectPolicy;
use App\Application\Policies\RolePolicy;
use App\Application\Policies\SavedFilterPolicy;
use App\Application\Policies\SprintPolicy;
use App\Application\Policies\TicketCommentPolicy;
use App\Application\Policies\TicketPolicy;
use App\Application\Policies\TicketStatusPolicy;
use App\Application\Policies\UnitPolicy;
use App\Application\Policies\UserPolicy;
use App\Models\Epic;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\SavedFilter;
use App\Models\Sprint;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketStatus;
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
        Project::class => ProjectPolicy::class,
        Sprint::class => SprintPolicy::class,
        Epic::class => EpicPolicy::class,
        ProjectNote::class => ProjectNotePolicy::class,
        Ticket::class => TicketPolicy::class,
        TicketStatus::class => TicketStatusPolicy::class,
        TicketComment::class => TicketCommentPolicy::class,
        SavedFilter::class => SavedFilterPolicy::class,
    ];
}
