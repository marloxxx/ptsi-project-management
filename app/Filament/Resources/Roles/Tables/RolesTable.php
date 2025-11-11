<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Tables;

use App\Domain\Services\RoleServiceInterface;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Role Name')
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->searchable()
                    ->sortable()
                    ->weight('font-medium'),

                TextColumn::make('guard_name')
                    ->label('Guard')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('success')
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize(fn (Role $record): bool => Auth::user()?->can('view', $record) ?? false),
                EditAction::make()
                    ->authorize(fn (Role $record): bool => Auth::user()?->can('update', $record) ?? false),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->authorize(fn (Role $record): bool => Auth::user()?->can('delete', $record) ?? false)
                    ->action(fn (Role $record, RoleServiceInterface $roleService) => $roleService->delete((int) $record->getKey())),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->authorize(fn (): bool => Auth::user()?->can('roles.delete') ?? false)
                        ->action(
                            fn (Collection $records, RoleServiceInterface $roleService) => $records->each(
                                fn (Role $role) => $roleService->delete((int) $role->getKey())
                            )
                        ),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
