<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Models\TicketHistory;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'Status History';

    protected static ?string $pluralLabel = 'Status History';

    protected static ?string $modelLabel = 'Status Change';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->heading('Status History')
            ->description('Complete history of status transitions for this ticket')
            ->modifyQueryUsing(fn(Builder $query): Builder => $query
                ->with(['actor', 'fromStatus', 'toStatus'])
                ->orderByDesc('created_at'))
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M d, Y \a\t H:i')
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('actor.name')
                    ->label('Changed By')
                    ->badge()
                    ->color('primary')
                    ->placeholder('System')
                    ->searchable(),

                TextColumn::make('fromStatus.name')
                    ->label('From Status')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn(?string $state): string => $state ?? 'Initial')
                    ->placeholder('Initial'),

                TextColumn::make('arrow')
                    ->label('')
                    ->formatStateUsing(fn(): string => '→')
                    ->alignCenter()
                    ->weight('font-bold')
                    ->color('gray'),

                TextColumn::make('toStatus.name')
                    ->label('To Status')
                    ->badge()
                    ->color(function (TicketHistory $record): string {
                        $toStatus = $record->toStatus;

                        return ($toStatus !== null ? $toStatus->color : null) ?? 'primary';
                    }),

                TextColumn::make('note')
                    ->label('Note')
                    ->limit(50)
                    ->wrap()
                    ->searchable()
                    ->placeholder('-')
                    ->tooltip(fn(TicketHistory $record): ?string => $record->note ? $record->note : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped()
            ->headerActions([])
            ->recordActions([])
            ->emptyStateHeading('No status history')
            ->emptyStateDescription('Status changes will appear here once the ticket status is updated.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    public function form(Schema $schema): Schema
    {
        // History is read-only, no form needed
        return $schema->components([]);
    }
}
