<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Ticket;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * @implements WithMapping<Ticket>
 */
class TicketsExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * @return Collection<int, Ticket>
     */
    public function collection(): Collection
    {
        return Ticket::query()
            ->with(['project', 'status', 'priority', 'epic', 'creator', 'assignees'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'ID',
            'UUID',
            'Name',
            'Project',
            'Status',
            'Priority',
            'Epic',
            'Creator',
            'Assignees',
            'Start Date',
            'Due Date',
            'Created At',
        ];
    }

    /**
     * @param  Ticket  $ticket
     * @return array<int, string|null>
     */
    public function map($ticket): array
    {
        return [
            (string) $ticket->getKey(),
            $ticket->uuid,
            $ticket->name,
            $ticket->project?->name,
            $ticket->status?->name,
            $ticket->priority?->name,
            $ticket->epic?->name,
            $ticket->creator?->name,
            $ticket->assignees->pluck('name')->implode(', '),
            optional($ticket->start_date)?->format('Y-m-d'),
            optional($ticket->due_date)?->format('Y-m-d'),
            optional($ticket->created_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
