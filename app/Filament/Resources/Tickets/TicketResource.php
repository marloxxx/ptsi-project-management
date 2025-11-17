<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets;

use App\Filament\Resources\Tickets\Pages\CreateTicket;
use App\Filament\Resources\Tickets\Pages\EditTicket;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Resources\Tickets\RelationManagers\TicketCommentsRelationManager;
use App\Filament\Resources\Tickets\Schemas\TicketForm;
use App\Filament\Resources\Tickets\Schemas\TicketInfolist;
use App\Filament\Resources\Tickets\Tables\TicketsTable;
use App\Models\Ticket;
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

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|UnitEnum|null $navigationGroup = 'Project Management';

    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool
    {
        return self::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return TicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TicketsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TicketInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [TicketCommentsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view' => ViewTicket::route('/{record}'),
            'edit' => EditTicket::route('/{record}/edit'),
        ];
    }

    public static function shouldPersistTableSortInSession(): bool
    {
        return true;
    }

    public static function canViewAny(): bool
    {
        return self::currentUser()?->can('viewAny', Ticket::class) ?? false;
    }

    public static function canCreate(): bool
    {
        return self::currentUser()?->can('create', Ticket::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        if (! $record instanceof Ticket) {
            return false;
        }

        return self::currentUser()?->can('update', $record) ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        if (! $record instanceof Ticket) {
            return false;
        }

        return self::currentUser()?->can('delete', $record) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return self::currentUser()?->can('delete', Ticket::class) ?? false;
    }

    public static function getNavigationLabel(): string
    {
        return 'Tickets';
    }

    public static function getPluralLabel(): string
    {
        return 'Tickets';
    }

    public static function getLabel(): string
    {
        return 'Ticket';
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    /**
     * @return Builder<Ticket>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['project', 'status', 'priority', 'epic', 'creator', 'assignees']);
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
