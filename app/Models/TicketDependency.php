<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketDependency extends Model
{
    /** @use HasFactory<\Database\Factories\TicketDependencyFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'depends_on_ticket_id',
        'type',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'ticket_id' => 'integer',
            'depends_on_ticket_id' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Ticket, TicketDependency>
     */
    public function ticket(): BelongsTo
    {
        /** @var BelongsTo<Ticket, TicketDependency> $relation */
        $relation = $this->belongsTo(Ticket::class);

        return $relation;
    }

    /**
     * @return BelongsTo<Ticket, TicketDependency>
     */
    public function dependsOnTicket(): BelongsTo
    {
        /** @var BelongsTo<Ticket, TicketDependency> $relation */
        $relation = $this->belongsTo(Ticket::class, 'depends_on_ticket_id');

        return $relation;
    }
}
