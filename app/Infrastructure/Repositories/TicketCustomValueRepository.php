<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Repositories\TicketCustomValueRepositoryInterface;
use App\Models\Ticket;
use App\Models\TicketCustomValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TicketCustomValueRepository implements TicketCustomValueRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TicketCustomValue
    {
        return TicketCustomValue::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TicketCustomValue $customValue, array $data): TicketCustomValue
    {
        $customValue->update($data);

        return $customValue->fresh();
    }

    public function delete(TicketCustomValue $customValue): bool
    {
        return (bool) $customValue->delete();
    }

    public function find(int $id): ?TicketCustomValue
    {
        return TicketCustomValue::find($id);
    }

    /**
     * @return Collection<int, TicketCustomValue>
     */
    public function forTicket(int $ticketId): Collection
    {
        return TicketCustomValue::where('ticket_id', $ticketId)
            ->with('customField')
            ->get();
    }

    public function findByTicketAndField(int $ticketId, int $customFieldId): ?TicketCustomValue
    {
        return TicketCustomValue::where('ticket_id', $ticketId)
            ->where('custom_field_id', $customFieldId)
            ->first();
    }

    /**
     * @param  array<int, array<string, mixed>>  $values
     */
    public function syncForTicket(Ticket $ticket, array $values): void
    {
        DB::transaction(function () use ($ticket, $values): void {
            // Delete existing values not in the new set
            $customFieldIds = array_column($values, 'custom_field_id');
            TicketCustomValue::where('ticket_id', $ticket->id)
                ->whereNotIn('custom_field_id', $customFieldIds)
                ->delete();

            // Update or create values
            foreach ($values as $valueData) {
                TicketCustomValue::updateOrCreate(
                    [
                        'ticket_id' => $ticket->id,
                        'custom_field_id' => $valueData['custom_field_id'],
                    ],
                    [
                        'value' => $valueData['value'] ?? null,
                    ]
                );
            }
        });
    }
}
