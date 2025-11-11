<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogs\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ActivityLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Section::make('Activity Information')
                            ->schema([
                                TextEntry::make('description')
                                    ->label('Description'),
                                TextEntry::make('event')
                                    ->label('Event')
                                    ->badge()
                                    ->formatStateUsing(function ($state, $record) {
                                        if ($state) {
                                            return $state;
                                        }

                                        // Extract event from description if event is null
                                        $description = strtolower($record->description ?? '');
                                        if (str_contains($description, 'created')) {
                                            return 'created';
                                        }
                                        if (str_contains($description, 'updated')) {
                                            return 'updated';
                                        }
                                        if (str_contains($description, 'deleted')) {
                                            return 'deleted';
                                        }

                                        return '-';
                                    })
                                    ->color(fn ($state) => match ($state) {
                                        'created' => 'success',
                                        'updated' => 'warning',
                                        'deleted' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('log_name')
                                    ->label('Log Name')
                                    ->badge(),
                                TextEntry::make('created_at')
                                    ->label('Date')
                                    ->dateTime(),
                            ])
                            ->columnSpan(1),
                        Section::make('Relationships')
                            ->schema([
                                TextEntry::make('causer.name')
                                    ->label('User')
                                    ->formatStateUsing(function ($record) {
                                        if ($record->causer) {
                                            return $record->causer->name ?? $record->causer->email ?? '-';
                                        }

                                        return 'System';
                                    }),
                                TextEntry::make('subject_type')
                                    ->label('Subject Type')
                                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-'),
                                TextEntry::make('subject_id')
                                    ->label('Subject ID')
                                    ->placeholder('-'),
                            ])
                            ->columnSpan(1),
                    ]),
                Section::make('Properties')
                    ->schema([
                        TextEntry::make('properties')
                            ->label('Properties')
                            ->formatStateUsing(function ($state) {
                                if (! $state) {
                                    return '-';
                                }

                                // Handle both Collection and array
                                $data = is_array($state) ? $state : $state->toArray();

                                return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            })
                            ->placeholder('-')
                            ->copyable()
                            ->copyMessage('Properties copied!')
                            ->copyMessageDuration(1500),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
