<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketHistory extends Model
{
    /** @use HasFactory<\Database\Factories\TicketHistoryFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'from_ticket_status_id',
        'to_ticket_status_id',
        'note',
    ];

    /**
     * @return BelongsTo<Ticket, TicketHistory>
     */
    public function ticket(): BelongsTo
    {
        /** @var BelongsTo<Ticket, TicketHistory> $relation */
        $relation = $this->belongsTo(Ticket::class);

        return $relation;
    }

    /**
     * @return BelongsTo<User, TicketHistory>
     */
    public function actor(): BelongsTo
    {
        /** @var BelongsTo<User, TicketHistory> $relation */
        $relation = $this->belongsTo(User::class, 'user_id');

        return $relation;
    }

    /**
     * @return BelongsTo<TicketStatus, TicketHistory>
     */
    public function fromStatus(): BelongsTo
    {
        /** @var BelongsTo<TicketStatus, TicketHistory> $relation */
        $relation = $this->belongsTo(TicketStatus::class, 'from_ticket_status_id');

        return $relation;
    }

    /**
     * @return BelongsTo<TicketStatus, TicketHistory>
     */
    public function toStatus(): BelongsTo
    {
        /** @var BelongsTo<TicketStatus, TicketHistory> $relation */
        $relation = $this->belongsTo(TicketStatus::class, 'to_ticket_status_id');

        return $relation;
    }
}
