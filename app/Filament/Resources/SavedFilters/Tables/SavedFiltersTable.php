<?php

declare(strict_types=1);

namespace App\Filament\Resources\SavedFilters\Tables;

use App\Domain\Services\SavedFilterServiceInterface;
use App\Models\SavedFilter;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SavedFiltersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Filter Name')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),

                TextColumn::make('visibility')
                    ->label('Visibility')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'private' => 'gray',
                        'team' => 'info',
                        'project' => 'warning',
                        'public' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('visibility')
                    ->label('Visibility')
                    ->options([
                        'private' => 'Private',
                        'team' => 'Team',
                        'project' => 'Project',
                        'public' => 'Public',
                    ]),
            ])
            ->recordTitleAttribute('name')
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (SavedFilter $record): bool => self::currentUser()?->can('update', $record) ?? false),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->authorize(fn (SavedFilter $record): bool => self::currentUser()?->can('delete', $record) ?? false)
                    ->action(fn (SavedFilter $record, SavedFilterServiceInterface $savedFilterService) => $savedFilterService->delete($record->id))
                    ->successNotificationTitle('Saved filter deleted'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->authorize(fn (): bool => self::currentUser()?->can('saved-filters.delete') ?? false)
                        ->action(
                            fn (Collection $records, SavedFilterServiceInterface $savedFilterService) => $records->each(
                                fn (SavedFilter $filter) => $savedFilterService->delete($filter->id)
                            )
                        ),
                ]),
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
