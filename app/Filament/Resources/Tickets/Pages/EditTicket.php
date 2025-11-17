<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Services\TicketServiceInterface;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected TicketServiceInterface $ticketService;

    public function boot(TicketServiceInterface $ticketService): void
    {
        $this->ticketService = $ticketService;
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()->visible(fn (): bool => static::getResource()::canDelete($this->record))->requiresConfirmation()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record instanceof Ticket) {
            $this->record->loadMissing(['assignees', 'customValues.customField']);
            $data['assignee_ids'] = $this->record->assignees->pluck('id')->map(fn ($id): int => (int) $id)->all();

            // Load custom field values
            $customFields = [];
            foreach ($this->record->customValues as $customValue) {
                $customFields[$customValue->custom_field_id] = $customValue->value;
            }
            $data['custom_fields'] = $customFields;
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Ticket) {
            throw new InvalidArgumentException('Expected Ticket model.');
        }
        $assigneeIds = null;
        if (array_key_exists('assignee_ids', $data)) {
            $assigneeIds = array_values(array_map(fn ($id): int => (int) $id, array_filter($data['assignee_ids'] ?? [], fn ($id): bool => $id !== null && $id !== '')));
        }
        unset($data['assignee_ids']);

        return $this->ticketService->update((int) $record->getKey(), $data, $assigneeIds);
    }

    protected function handleRecordDeletion(Model $record): void
    {
        /** @var Ticket $record */
        $this->ticketService->delete((int) $record->getKey());
    }
}
