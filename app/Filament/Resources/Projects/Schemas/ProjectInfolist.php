<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\Project;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ProjectInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Overview')
                    ->icon(Heroicon::OutlinedFolder)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Project Name')
                            ->weight('font-semibold')
                            ->size('lg'),

                        TextEntry::make('ticket_prefix')
                            ->label('Ticket Prefix')
                            ->badge()
                            ->color('primary')
                            ->formatStateUsing(fn (?string $state): ?string => $state !== null ? strtoupper($state) : $state),

                        TextEntry::make('color')
                            ->label('Card Color')
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn (?string $state): ?string => $state),

                        TextEntry::make('description')
                            ->label('Summary')
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

                        TextEntry::make('end_date')
                            ->label('Target End Date')
                            ->date()
                            ->placeholder('-'),

                        IconEntry::make('pinned_at')
                            ->label('Pinned')
                            ->boolean()
                            ->trueIcon(Heroicon::OutlinedStar)
                            ->falseIcon(Heroicon::OutlinedStar)
                            ->trueColor('warning')
                            ->falseColor('gray')
                            ->formatStateUsing(fn ($state): bool => $state !== null)
                            ->helperText(fn (Project $record): string => $record->pinned_at !== null
                                ? 'Pinned at '.$record->pinned_at->format('M d, Y \a\t H:i')
                                : 'Not pinned'),
                    ])
                    ->columns(3),

                Section::make('Metrics')
                    ->icon(Heroicon::OutlinedChartBar)
                    ->schema([
                        TextEntry::make('members_count')
                            ->label('Team Members')
                            ->badge()
                            ->color('primary')
                            ->getStateUsing(fn (Project $record): int => $record->members->count()),

                        TextEntry::make('ticket_statuses_count')
                            ->label('Ticket Statuses')
                            ->badge()
                            ->color('info')
                            ->getStateUsing(fn (Project $record): int => $record->ticketStatuses->count()),

                        TextEntry::make('id')
                            ->label('Project ID')
                            ->badge()
                            ->color('gray')
                            ->copyable()
                            ->copyMessage('Copied')
                            ->copyMessageDuration(1500),
                    ])
                    ->columns(3),

                Section::make('Audit')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Grid::make([
                            'sm' => 3,
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

                                TextEntry::make('pinned_at')
                                    ->label('Pinned At')
                                    ->dateTime('M d, Y \a\t H:i')
                                    ->placeholder('Not pinned'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
