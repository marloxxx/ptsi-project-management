<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'ticket_status_id',
        'priority_id',
        'epic_id',
        'sprint_id',
        'created_by',
        'uuid',
        'name',
        'issue_type',
        'parent_id',
        'description',
        'start_date',
        'due_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'created_by' => 'integer',
            'parent_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (empty($ticket->uuid)) {
                $prefix = Project::query()
                    ->whereKey($ticket->project_id)
                    ->value('ticket_prefix') ?? 'TKT';

                $ticket->uuid = sprintf(
                    '%s-%s',
                    strtoupper($prefix),
                    strtoupper(Str::random(6))
                );
            }

            if (empty($ticket->created_by) && Auth::check()) {
                $userId = Auth::id();

                if ($userId !== null) {
                    $ticket->created_by = (int) $userId;
                }
            }
        });
    }

    /**
     * @return BelongsTo<Project, Ticket>
     */
    public function project(): BelongsTo
    {
        /** @var BelongsTo<Project, Ticket> $relation */
        $relation = $this->belongsTo(Project::class);

        return $relation;
    }

    /**
     * @return BelongsTo<TicketStatus, Ticket>
     */
    public function status(): BelongsTo
    {
        /** @var BelongsTo<TicketStatus, Ticket> $relation */
        $relation = $this->belongsTo(TicketStatus::class, 'ticket_status_id');

        return $relation;
    }

    /**
     * @return BelongsTo<TicketPriority, Ticket>
     */
    public function priority(): BelongsTo
    {
        /** @var BelongsTo<TicketPriority, Ticket> $relation */
        $relation = $this->belongsTo(TicketPriority::class, 'priority_id');

        return $relation;
    }

    /**
     * @return BelongsTo<Epic, Ticket>
     */
    public function epic(): BelongsTo
    {
        /** @var BelongsTo<Epic, Ticket> $relation */
        $relation = $this->belongsTo(Epic::class);

        return $relation;
    }

    /**
     * @return BelongsTo<User, Ticket>
     */
    public function creator(): BelongsTo
    {
        /** @var BelongsTo<User, Ticket> $relation */
        $relation = $this->belongsTo(User::class, 'created_by');

        return $relation;
    }

    /**
     * @return BelongsToMany<User, Ticket>
     *
     * @phpstan-return BelongsToMany<User, Ticket, \Illuminate\Database\Eloquent\Relations\Pivot>
     */
    public function assignees(): BelongsToMany
    {
        /** @var BelongsToMany<User, Ticket, \Illuminate\Database\Eloquent\Relations\Pivot> $relation */
        $relation = $this->belongsToMany(User::class, 'ticket_users')
            ->withTimestamps();

        return $relation;
    }

    /**
     * @return HasMany<TicketComment, Ticket>
     */
    public function comments(): HasMany
    {
        /** @var HasMany<TicketComment, Ticket> $relation */
        $relation = $this->hasMany(TicketComment::class);

        return $relation;
    }

    /**
     * @return HasMany<TicketHistory, Ticket>
     */
    public function histories(): HasMany
    {
        /** @var HasMany<TicketHistory, Ticket> $relation */
        $relation = $this->hasMany(TicketHistory::class);

        return $relation;
    }

    /**
     * @return BelongsTo<Sprint, Ticket>
     */
    public function sprint(): BelongsTo
    {
        /** @var BelongsTo<Sprint, Ticket> $relation */
        $relation = $this->belongsTo(Sprint::class);

        return $relation;
    }

    /**
     * @return BelongsTo<Ticket, Ticket>
     */
    public function parent(): BelongsTo
    {
        /** @var BelongsTo<Ticket, Ticket> $relation */
        $relation = $this->belongsTo(Ticket::class, 'parent_id');

        return $relation;
    }

    /**
     * @return HasMany<Ticket, Ticket>
     */
    public function children(): HasMany
    {
        /** @var HasMany<Ticket, Ticket> $relation */
        $relation = $this->hasMany(Ticket::class, 'parent_id');

        return $relation;
    }

    /**
     * @return HasMany<TicketDependency, Ticket>
     */
    public function dependencies(): HasMany
    {
        /** @var HasMany<TicketDependency, Ticket> $relation */
        $relation = $this->hasMany(TicketDependency::class, 'ticket_id');

        return $relation;
    }

    /**
     * @return HasMany<TicketDependency, Ticket>
     */
    public function dependents(): HasMany
    {
        /** @var HasMany<TicketDependency, Ticket> $relation */
        $relation = $this->hasMany(TicketDependency::class, 'depends_on_ticket_id');

        return $relation;
    }

    /**
     * Get tickets that this ticket depends on (blocks/relates).
     *
     * @return HasManyThrough<Ticket, TicketDependency, Ticket>
     */
    public function dependsOnTickets(): HasManyThrough
    {
        /** @var HasManyThrough<Ticket, TicketDependency, Ticket> $relation */
        $relation = $this->hasManyThrough(
            Ticket::class,
            TicketDependency::class,
            'ticket_id',
            'id',
            'id',
            'depends_on_ticket_id'
        );

        return $relation;
    }

    /**
     * Get tickets that depend on this ticket.
     *
     * @return HasManyThrough<Ticket, TicketDependency, Ticket>
     */
    public function blockingTickets(): HasManyThrough
    {
        /** @var HasManyThrough<Ticket, TicketDependency, Ticket> $relation */
        $relation = $this->hasManyThrough(
            Ticket::class,
            TicketDependency::class,
            'depends_on_ticket_id',
            'id',
            'id',
            'ticket_id'
        );

        return $relation;
    }

    /**
     * @return HasMany<TicketCustomValue, Ticket>
     */
    public function customValues(): HasMany
    {
        /** @var HasMany<TicketCustomValue, Ticket> $relation */
        $relation = $this->hasMany(TicketCustomValue::class);

        return $relation;
    }
}
