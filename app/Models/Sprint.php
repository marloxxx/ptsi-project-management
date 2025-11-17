<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sprint extends Model
{
    /** @use HasFactory<\Database\Factories\SprintFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'goal',
        'state',
        'start_date',
        'end_date',
        'closed_at',
        'created_by',
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
            'closed_at' => 'datetime',
            'created_by' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, Sprint>
     */
    public function project(): BelongsTo
    {
        /** @var BelongsTo<Project, Sprint> $relation */
        $relation = $this->belongsTo(Project::class);

        return $relation;
    }

    /**
     * @return BelongsTo<User, Sprint>
     */
    public function creator(): BelongsTo
    {
        /** @var BelongsTo<User, Sprint> $relation */
        $relation = $this->belongsTo(User::class, 'created_by');

        return $relation;
    }

    /**
     * @return HasMany<Ticket, Sprint>
     */
    public function tickets(): HasMany
    {
        /** @var HasMany<Ticket, Sprint> $relation */
        $relation = $this->hasMany(Ticket::class);

        return $relation;
    }

    public function isActive(): bool
    {
        return $this->state === 'Active';
    }

    public function isClosed(): bool
    {
        return $this->state === 'Closed';
    }

    public function isPlanned(): bool
    {
        return $this->state === 'Planned';
    }
}
