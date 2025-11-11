<?php

declare(strict_types=1);

namespace App\Filament\Resources\ActivityLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class ActivityLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['causer', 'subject']))
            ->columns([
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->wrap()
                    ->limit(50),
                TextColumn::make('causer.name')
                    ->label('User')
                    ->searchable()
                    ->default('-')
                    ->formatStateUsing(function ($record) {
                        if ($record->causer) {
                            return $record->causer->name ?? $record->causer->email ?? '-';
                        }

                        return 'System';
                    }),
                TextColumn::make('subject_type')
                    ->label('Subject Type')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->toggleable(),
                TextColumn::make('subject_id')
                    ->label('Subject ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('event')
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
                    })
                    ->toggleable(),
                TextColumn::make('log_name')
                    ->label('Log Name')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('log_name')
                    ->label('Log Name')
                    ->options(
                        fn () => Activity::query()
                            ->distinct()
                            ->whereNotNull('log_name')
                            ->pluck('log_name', 'log_name')
                            ->toArray()
                    ),
                SelectFilter::make('event')
                    ->label('Event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        $event = $data['value'];

                        return $query->where(function (Builder $q) use ($event) {
                            // Check event column first
                            $q->where('event', $event)
                                // Also check description if event is null
                                ->orWhere(function (Builder $subQuery) use ($event) {
                                    $subQuery->whereNull('event')
                                        ->where('description', 'like', "%{$event}%");
                                });
                        });
                    }),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('Created From')
                            ->native(false),
                        DatePicker::make('created_until')
                            ->label('Created Until')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make()
                    ->authorize(fn (Activity $record): bool => Auth::user()?->can('view', $record) ?? false),
            ]);
    }
}
