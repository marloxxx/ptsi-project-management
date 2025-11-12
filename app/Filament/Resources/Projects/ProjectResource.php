<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\EpicsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\ProjectNotesRelationManager;
use App\Filament\Resources\Projects\RelationManagers\TicketStatusesRelationManager;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Resources\Projects\Schemas\ProjectInfolist;
use App\Filament\Resources\Projects\Tables\ProjectsTable;
use App\Models\Project;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static string|UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return ProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProjectInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            TicketStatusesRelationManager::class,
            EpicsRelationManager::class,
            ProjectNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }

    public static function shouldPersistTableSortInSession(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->can('projects.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->can('projects.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        /** @var Project $record */
        return self::currentUser()?->can('projects.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        /** @var Project $record */
        return self::currentUser()?->can('projects.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return self::currentUser()?->can('projects.delete') ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return 'Projects';
    }

    public static function getPluralLabel(): string
    {
        return 'Projects';
    }

    public static function getLabel(): string
    {
        return 'Project';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    /**
     * @return Builder<Project>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('members')
            ->with(['ticketStatuses', 'members']);
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
