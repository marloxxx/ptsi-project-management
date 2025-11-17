<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Models\TicketHistory;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketHistoryRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'Status History';

    protected static ?string $pluralLabel = 'Status History';

    protected static ?string $modelLabel = 'Status Change';

    public string $viewMode = 'timeline';

    public function table(Table $table): Table
    {
        $table = $table
            ->recordTitleAttribute('id')
            ->heading('Status History')
            ->description('Complete history of status transitions for this ticket')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['actor', 'fromStatus', 'toStatus'])
                ->orderBy('created_at', 'asc'))
            ->defaultSort('created_at', 'asc')
            ->poll('30s')
            ->headerActions([
                Action::make('timeline')
                    ->label('Timeline')
                    ->icon(Heroicon::OutlinedClock)
                    ->color(fn (): string => $this->viewMode === 'timeline' ? 'primary' : 'gray')
                    ->outlined(fn (): bool => $this->viewMode !== 'timeline')
                    ->action(fn (): string => $this->viewMode = 'timeline'),
                Action::make('table')
                    ->label('Table')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->color(fn (): string => $this->viewMode === 'table' ? 'primary' : 'gray')
                    ->outlined(fn (): bool => $this->viewMode !== 'table')
                    ->action(fn (): string => $this->viewMode = 'table'),
                Action::make('calendar')
                    ->label('Calendar')
                    ->icon(Heroicon::OutlinedCalendarDays)
                    ->color(fn (): string => $this->viewMode === 'calendar' ? 'primary' : 'gray')
                    ->outlined(fn (): bool => $this->viewMode !== 'calendar')
                    ->action(fn (): string => $this->viewMode = 'calendar'),
            ])
            ->recordActions([])
            ->emptyStateHeading('No status history')
            ->emptyStateDescription('Status changes will appear here once the ticket status is updated.')
            ->emptyStateIcon('heroicon-o-clock');

        // Return different column layouts based on view mode
        return match ($this->viewMode) {
            'table' => $this->getTableView($table),
            'calendar' => $this->getCalendarView($table),
            default => $this->getTimelineView($table),
        };
    }

    protected function getTimelineView(Table $table): Table
    {
        return $table
            ->heading('Status History Timeline')
            ->columns([
                TextColumn::make('timeline')
                    ->label('Timeline')
                    ->html()
                    ->formatStateUsing(function (TicketHistory $record): string {
                        return view('filament.components.timeline-item', [
                            'record' => $record,
                        ])->render();
                    })
                    ->columnSpanFull(),
            ])
            ->contentGrid([
                'md' => 1,
            ]);
    }

    protected function getTableView(Table $table): Table
    {
        return $table
            ->heading('Status History - Table View')
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
                    ->formatStateUsing(fn (?string $state): string => $state ?? 'Initial')
                    ->placeholder('Initial'),

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
                    ->tooltip(fn (TicketHistory $record): ?string => $record->note ? $record->note : null),
            ])
            ->striped();
    }

    protected function getCalendarView(Table $table): Table
    {
        return $table
            ->heading('Status History - Calendar View')
            ->groups([
                Group::make('created_at')
                    ->date()
                    ->label('Date'),
            ])
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->date('M d, Y')
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('created_at')
                    ->label('Time')
                    ->formatStateUsing(fn (TicketHistory $record): string => $record->created_at->format('H:i'))
                    ->sortable(),

                TextColumn::make('actor.name')
                    ->label('Changed By')
                    ->badge()
                    ->color('primary')
                    ->placeholder('System'),

                TextColumn::make('transition')
                    ->label('Transition')
                    ->html()
                    ->formatStateUsing(function (TicketHistory $record): string {
                        $fromStatus = ($record->fromStatus !== null ? $record->fromStatus->name : null) ?? 'Initial';
                        $toStatus = ($record->toStatus !== null ? $record->toStatus->name : null) ?? 'Unknown';
                        $toStatusColor = ($record->toStatus !== null ? $record->toStatus->color : null) ?? '#3B82F6';

                        // Helper function to check if color is light (white/very light)
                        $isLightColor = function (string $color): bool {
                            // Remove # if present
                            $color = ltrim($color, '#');

                            // Handle 3-digit hex
                            if (strlen($color) === 3) {
                                $color = $color[0].$color[0].$color[1].$color[1].$color[2].$color[2];
                            }

                            // Convert to RGB
                            $r = hexdec(substr($color, 0, 2));
                            $g = hexdec(substr($color, 2, 2));
                            $b = hexdec(substr($color, 4, 2));

                            // Calculate relative luminance
                            $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

                            // Consider light if luminance > 0.7 (white/very light colors)
                            return $luminance > 0.7;
                        };

                        $textColorClass = $isLightColor($toStatusColor)
                            ? 'text-gray-800 dark:text-gray-900'
                            : 'text-white';

                        return sprintf(
                            '<span class="inline-flex items-center gap-1.5">'.
                                '<span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">%s</span>'.
                                '<span class="text-gray-400">→</span>'.
                                '<span class="px-2 py-1 rounded-full text-xs font-medium %s" style="background-color: %s;">%s</span>'.
                                '</span>',
                            htmlspecialchars($fromStatus),
                            htmlspecialchars($textColorClass),
                            htmlspecialchars($toStatusColor),
                            htmlspecialchars($toStatus)
                        );
                    }),

                TextColumn::make('note')
                    ->label('Note')
                    ->limit(30)
                    ->wrap()
                    ->placeholder('-'),
            ])
            ->striped();
    }

    public function form(Schema $schema): Schema
    {
        // History is read-only, no form needed
        return $schema->components([]);
    }
}
