<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Tables;

use App\Domain\Services\TicketServiceInterface;
use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['project', 'status', 'priority', 'epic', 'creator', 'assignees']))
            ->columns([
                TextColumn::make('uuid')
                    ->label('Ticket ID')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('name')
                    ->label('Ticket')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->wrap(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('status.name')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(function (Ticket $record): string {
                        $status = $record->status;
                        $color = $status?->color;

                        return $color ?? 'gray';
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

                TextColumn::make('priority.name')
                    ->label('Priority')
                    ->sortable()
                    ->badge()
                    ->color(function (Ticket $record): string {
                        $priority = $record->priority;
                        $color = $priority?->color;

                        return $color ?? 'gray';
                    })
                    ->formatStateUsing(fn (?string $state): string => $state ?? '-'),

                TextColumn::make('epic.name')
                    ->label('Epic')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-')
                    ->limit(30),

                TextColumn::make('creator.name')
                    ->label('Creator')
                    ->sortable()
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('assignees.name')
                    ->label('Assignees')
                    ->badge()
                    ->color('success')
                    ->separator(',')
                    ->limit(2)
                    ->formatStateUsing(function (Ticket $record): string {
                        $count = $record->assignees->count();

                        if ($count === 0) {
                            return 'Unassigned';
                        }

                        $names = $record->assignees->take(2)->pluck('name')->join(', ');

                        if ($count > 2) {
                            $remaining = $count - 2;
                            $names .= ' +'.$remaining;
                        }

                        return $names;
                    }),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('due_date')
                    ->label('Due')
                    ->date()
                    ->sortable()
                    ->placeholder('-')
                    ->color(fn (Ticket $record): ?string => $record->due_date && $record->due_date->isPast() ? 'danger' : null),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->filters([
                // Filters can be added here
            ])
            ->recordActions([
                Action::make('view')
                    ->label('View')
                    ->icon(Heroicon::OutlinedEye)
                    ->visible(fn (): bool => self::currentUser()?->can('tickets.view') ?? false)
                    ->url(fn (Ticket $record): string => TicketResource::getUrl('view', ['record' => $record])),
                Action::make('edit')
                    ->label('Edit')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->visible(fn (): bool => self::currentUser()?->can('tickets.update') ?? false)
                    ->url(fn (Ticket $record): string => TicketResource::getUrl('edit', ['record' => $record])),
                Action::make('delete')
                    ->label('Delete')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => self::currentUser()?->can('tickets.delete') ?? false)
                    ->action(function (Ticket $record, TicketServiceInterface $ticketService): void {
                        $ticketService->delete((int) $record->getKey());
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('bulk-delete')
                    ->label('Delete selected')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => self::currentUser()?->can('tickets.delete') ?? false)
                    ->action(function (Collection $records, TicketServiceInterface $ticketService): void {
                        $records->each(
                            fn (Ticket $ticket): bool => $ticketService->delete((int) $ticket->getKey())
                        );
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /**
     * Get the current user.
     */
    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
