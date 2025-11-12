<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Services\TicketServiceInterface;
use App\Filament\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected TicketServiceInterface $ticketService;

    public function boot(TicketServiceInterface $ticketService): void
    {
        $this->ticketService = $ticketService;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $assigneeIds = array_values(array_map(fn ($id): int => (int) $id, array_filter($data['assignee_ids'] ?? [], fn ($id): bool => $id !== null && $id !== '')));
        unset($data['assignee_ids']);

        return $this->ticketService->create($data, $assigneeIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
