<?php

declare(strict_types=1);

namespace App\Filament\Resources\SavedFilters;

use App\Filament\Resources\SavedFilters\Pages\CreateSavedFilter;
use App\Filament\Resources\SavedFilters\Pages\EditSavedFilter;
use App\Filament\Resources\SavedFilters\Pages\ListSavedFilters;
use App\Filament\Resources\SavedFilters\Schemas\SavedFilterForm;
use App\Filament\Resources\SavedFilters\Tables\SavedFiltersTable;
use App\Models\SavedFilter;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class SavedFilterResource extends Resource
{
    protected static ?string $model = SavedFilter::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFunnel;

    protected static UnitEnum|string|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return SavedFilterForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SavedFiltersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSavedFilters::route('/'),
            'create' => CreateSavedFilter::route('/create'),
            'edit' => EditSavedFilter::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->can('viewAny', SavedFilter::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->can('create', SavedFilter::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        if (! $record instanceof SavedFilter) {
            return false;
        }

        return self::currentUser()?->can('update', $record) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        if (! $record instanceof SavedFilter) {
            return false;
        }

        return self::currentUser()?->can('delete', $record) ?? false;
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
