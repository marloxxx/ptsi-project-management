<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketPriority extends Model
{
    /** @use HasFactory<\Database\Factories\TicketPriorityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'sort_order',
    ];

    /**
     * @return HasMany<Ticket, TicketPriority>
     */
    public function tickets(): HasMany
    {
        /** @var HasMany<Ticket, TicketPriority> $relation */
        $relation = $this->hasMany(Ticket::class, 'priority_id');

        return $relation;
    }
}
