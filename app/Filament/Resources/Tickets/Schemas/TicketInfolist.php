<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Ticket;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->icon(Heroicon::OutlinedTicket)
                    ->schema([
                        TextEntry::make('uuid')
                            ->label('Ticket ID')
                            ->badge()
                            ->color('primary')
                            ->copyable()
                            ->copyMessage('Copied')
                            ->copyMessageDuration(1500),

                        TextEntry::make('name')
                            ->label('Ticket Name')
                            ->weight('font-semibold')
                            ->size('lg'),

                        TextEntry::make('project.name')
                            ->label('Project')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('status.name')
                            ->label('Status')
                            ->badge()
                            ->color(fn(Ticket $record): string => $record->status?->color ?? 'gray'),

                        TextEntry::make('priority.name')
                            ->label('Priority')
                            ->badge()
                            ->color(fn(Ticket $record): string => $record->priority?->color ?? 'gray'),

                        TextEntry::make('epic.name')
                            ->label('Epic')
                            ->badge()
                            ->color('warning')
                            ->placeholder('No epic assigned'),

                        TextEntry::make('description')
                            ->label('Description')
                            ->placeholder('No description provided.')
                            ->columnSpanFull(),
                    ])
                    ->columns([
                        'sm' => 2,
                        'lg' => 3,
                    ]),

                Section::make('Timeline')
                    ->icon(Heroicon::OutlinedCalendar)
                    ->schema([
                        TextEntry::make('start_date')
                            ->label('Start Date')
                            ->date()
                            ->placeholder('-'),

                        TextEntry::make('due_date')
                            ->label('Due Date')
                            ->date()
                            ->placeholder('-')
                            ->color(fn(Ticket $record): ?string => $record->due_date && $record->due_date->isPast() ? 'danger' : null),
                    ])
                    ->columns(2),

                Section::make('Team')
                    ->icon(Heroicon::OutlinedUsers)
                    ->schema([
                        TextEntry::make('creator.name')
                            ->label('Created By')
                            ->badge()
                            ->color('gray')
                            ->placeholder('-'),

                        TextEntry::make('assignees.name')
                            ->label('Assignees')
                            ->badge()
                            ->color('success')
                            ->separator(',')
                            ->formatStateUsing(function (Ticket $record): string {
                                if ($record->assignees->isEmpty()) {
                                    return 'Unassigned';
                                }

                                return $record->assignees->pluck('name')->join(', ');
                            }),
                    ])
                    ->columns(2),

                Section::make('Audit')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Grid::make([
                            'sm' => 2,
                        ])
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('M d, Y \a\t H:i')
                                    ->placeholder('-'),

                                TextEntry::make('updated_at')
                                    ->label('Updated At')
                                    ->dateTime('M d, Y \a\t H:i')
                                    ->placeholder('-'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
