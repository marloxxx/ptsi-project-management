<?php

declare(strict_types=1);

namespace App\Filament\Resources\Units;

use App\Filament\Resources\Units\Pages\CreateUnit;
use App\Filament\Resources\Units\Pages\EditUnit;
use App\Filament\Resources\Units\Pages\ListUnits;
use App\Filament\Resources\Units\Schemas\UnitForm;
use App\Filament\Resources\Units\Tables\UnitsTable;
use App\Models\Unit;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static UnitEnum|string|null $navigationGroup = 'Access Control';

    protected static ?int $navigationSort = 2;

    /**
     * @return list<string>
     */
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return UnitForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUnits::route('/'),
            'create' => CreateUnit::route('/create'),
            'edit' => EditUnit::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->can('units.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->can('units.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        /** @var Unit $record */
        return self::currentUser()?->can('units.update', $record) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var Unit $record */
        return self::currentUser()?->can('units.delete', $record) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return self::currentUser()?->can('units.delete') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return 'Units';
    }

    public static function getPluralLabel(): string
    {
        return 'Units';
    }

    public static function getLabel(): string
    {
        return 'Unit';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
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
