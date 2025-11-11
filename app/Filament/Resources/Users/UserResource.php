<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->can('users.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->can('users.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        if (! $record instanceof User) {
            return false;
        }

        return self::currentUser()?->can('update', $record) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        if (! $record instanceof User) {
            return false;
        }

        return self::currentUser()?->can('delete', $record) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return self::currentUser()?->can('users.delete') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        if (! $record instanceof User) {
            return false;
        }

        return self::currentUser()?->can('users.force-delete') ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        return self::currentUser()?->can('users.force-delete') ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        if (! $record instanceof User) {
            return false;
        }

        return self::currentUser()?->can('users.restore') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return self::currentUser()?->can('users.restore') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    /**
     * @return Builder<User>
     */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getNavigationLabel(): string
    {
        return 'Users';
    }

    public static function getPluralLabel(): string
    {
        return 'Users';
    }

    public static function getLabel(): string
    {
        return 'User';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
