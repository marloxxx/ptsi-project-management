<?php

namespace App\Filament\Resources\Users\Tables;

use App\Domain\Services\UserServiceInterface;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Actions\Impersonate;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('unit'))
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('unit.name')
                    ->label('Unit')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->placeholder('Belum ditetapkan'),
                TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize(fn (User $record): bool => self::currentUser()?->can('view', $record) ?? false),
                EditAction::make()
                    ->authorize(fn (User $record): bool => self::currentUser()?->can('update', $record) ?? false),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->authorize(fn (User $record): bool => self::currentUser()?->can('delete', $record) ?? false)
                    ->action(fn (User $record, UserServiceInterface $userService) => $userService->delete($record->id))
                    ->successNotificationTitle('User deleted'),
                Impersonate::make()
                    ->visible(fn (User $record): bool => self::currentUser()?->can('users.view') ?? false),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorize(fn (): bool => self::currentUser()?->can('users.delete') ?? false),
                    ForceDeleteBulkAction::make()
                        ->authorize(fn (): bool => self::currentUser()?->can('users.force-delete') ?? false),
                    RestoreBulkAction::make()
                        ->authorize(fn (): bool => self::currentUser()?->can('users.restore') ?? false),
                ]),
            ]);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
