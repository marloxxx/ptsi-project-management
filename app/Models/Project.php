<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'ticket_prefix',
        'color',
        'start_date',
        'end_date',
        'pinned_at',
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
            'end_date' => 'date',
            'pinned_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<TicketStatus>
     *
     * @phpstan-return HasMany<TicketStatus, self>
     */
    public function ticketStatuses(): HasMany
    {
        /** @var HasMany<TicketStatus, self> $relation */
        $relation = $this->hasMany(TicketStatus::class);

        return $relation;
    }

    /**
     * @return HasMany<Ticket>
     *
     * @phpstan-return HasMany<Ticket, self>
     */
    public function tickets(): HasMany
    {
        /** @var HasMany<Ticket, self> $relation */
        $relation = $this->hasMany(Ticket::class);

        return $relation;
    }

    /**
     * @return HasMany<Epic>
     *
     * @phpstan-return HasMany<Epic, self>
     */
    public function epics(): HasMany
    {
        /** @var HasMany<Epic, self> $relation */
        $relation = $this->hasMany(Epic::class);

        return $relation;
    }

    /**
     * @return HasMany<ProjectNote>
     *
     * @phpstan-return HasMany<ProjectNote, self>
     */
    public function notes(): HasMany
    {
        /** @var HasMany<ProjectNote, self> $relation */
        $relation = $this->hasMany(ProjectNote::class);

        return $relation;
    }

    /**
     * @return HasOne<ExternalAccessToken>
     *
     * @phpstan-return HasOne<ExternalAccessToken, self>
     */
    public function externalAccessToken(): HasOne
    {
        /** @var HasOne<ExternalAccessToken, self> $relation */
        $relation = $this->hasOne(ExternalAccessToken::class);

        return $relation;
    }

    /**
     * @return BelongsToMany<User>
     *
     * @phpstan-return BelongsToMany<User, self>
     */
    public function members(): BelongsToMany
    {
        /** @var BelongsToMany<User, self> $relation */
        $relation = $this->belongsToMany(User::class, 'project_members')
            ->withTimestamps();

        return $relation;
    }

    public function getIsPinnedAttribute(): bool
    {
        return ! is_null($this->pinned_at);
    }
}
