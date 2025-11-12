<?php

declare(strict_types=1);

namespace App\Filament\Resources\Units\Tables;

use App\Domain\Services\UnitServiceInterface;
use App\Models\Unit;
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

class UnitsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Unit Name')
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('sinav_unit_id')
                    ->label('SINAV ID')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'active' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'active' ? 'Aktif' : 'Nonaktif')
                    ->sortable(),

                TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                    ]),
            ])
            ->recordTitleAttribute('name')
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (Unit $record): bool => self::currentUser()?->can('update', $record) ?? false),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->authorize(fn (Unit $record): bool => self::currentUser()?->can('delete', $record) ?? false)
                    ->action(fn (Unit $record, UnitServiceInterface $unitService) => $unitService->delete($record))
                    ->successNotificationTitle('Unit deleted'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->authorize(fn (): bool => self::currentUser()?->can('units.delete') ?? false)
                        ->action(
                            fn (Collection $records, UnitServiceInterface $unitService) => $records->each(
                                fn (Unit $unit) => $unitService->delete($unit)
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
