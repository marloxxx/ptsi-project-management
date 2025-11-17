<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketCustomValue extends Model
{
    /** @use HasFactory<\Database\Factories\TicketCustomValueFactory> */
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'custom_field_id',
        'value',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * @return BelongsTo<Ticket, TicketCustomValue>
     */
    public function ticket(): BelongsTo
    {
        /** @var BelongsTo<Ticket, TicketCustomValue> $relation */
        $relation = $this->belongsTo(Ticket::class);

        return $relation;
    }

    /**
     * @return BelongsTo<ProjectCustomField, TicketCustomValue>
     */
    public function customField(): BelongsTo
    {
        /** @var BelongsTo<ProjectCustomField, TicketCustomValue> $relation */
        $relation = $this->belongsTo(ProjectCustomField::class, 'custom_field_id');

        return $relation;
    }
}
