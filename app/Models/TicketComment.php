<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketComment extends Model
{
    /** @use HasFactory<\Database\Factories\TicketCommentFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'is_internal',
        'body',
    ];

    protected $casts = [
        'is_internal' => 'bool',
    ];

    /**
     * @return BelongsTo<Ticket, TicketComment>
     */
    public function ticket(): BelongsTo
    {
        /** @var BelongsTo<Ticket, TicketComment> $relation */
        $relation = $this->belongsTo(Ticket::class);

        return $relation;
    }

    /**
     * @return BelongsTo<User, TicketComment>
     */
    public function author(): BelongsTo
    {
        /** @var BelongsTo<User, TicketComment> $relation */
        $relation = $this->belongsTo(User::class, 'user_id');

        return $relation;
    }
}
