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

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pinned_at' => 'datetime',
    ];

    public function ticketStatuses(): HasMany
    {
        return $this->hasMany(TicketStatus::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    public function externalAccessToken(): HasOne
    {
        return $this->hasOne(ExternalAccessToken::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withTimestamps();
    }

    public function getIsPinnedAttribute(): bool
    {
        return ! is_null($this->pinned_at);
    }
}
