<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    /** @use HasFactory<\Database\Factories\TicketStatusFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'color',
        'is_completed',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_completed' => 'bool',
        ];
    }

    /**
     * @return BelongsTo<Project, TicketStatus>
     */
    public function project(): BelongsTo
    {
        /** @var BelongsTo<Project, TicketStatus> $relation */
        $relation = $this->belongsTo(Project::class);

        return $relation;
    }

    /**
     * @return HasMany<Ticket, TicketStatus>
     */
    public function tickets(): HasMany
    {
        /** @var HasMany<Ticket, TicketStatus> $relation */
        $relation = $this->hasMany(Ticket::class);

        return $relation;
    }
}
