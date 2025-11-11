<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'from_ticket_status_id',
        'to_ticket_status_id',
        'note',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fromStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'from_ticket_status_id');
    }

    public function toStatus(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'to_ticket_status_id');
    }
}
