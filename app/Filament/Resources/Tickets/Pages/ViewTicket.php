<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Pages;

use App\Domain\Services\TicketServiceInterface;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected TicketServiceInterface $ticketService;

    public function boot(TicketServiceInterface $ticketService): void
    {
        $this->ticketService = $ticketService;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make()->visible(fn (): bool => static::getResource()::canEdit($this->record)),
                DeleteAction::make()->visible(fn (): bool => static::getResource()::canDelete($this->record))->requiresConfirmation()->action(function (Model $record): void {
                    if (! $record instanceof Ticket) {
                        throw new InvalidArgumentException('Expected Ticket model.');
                    }
                    $this->ticketService->delete((int) $record->getKey());
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
            ])->button()->label('Actions'),
        ];
    }
}
