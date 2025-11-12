<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\RelationManagers;

use App\Domain\Services\TicketServiceInterface;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class TicketCommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $recordTitleAttribute = 'body';

    protected TicketServiceInterface $ticketService;

    public function boot(TicketServiceInterface $ticketService): void
    {
        $this->ticketService = $ticketService;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Comment Details')->schema([
                Textarea::make('body')->label('Comment')->required()->rows(6)->columnSpanFull()->placeholder('Enter your comment...'),
                Checkbox::make('is_internal')->label('Internal Comment')->helperText('Internal comments are only visible to team members.')->default(false),
                Select::make('user_id')->label('Author')->native(false)->searchable()->preload()->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->toArray())->helperText('Defaults to the current user when left empty.'),
            ])->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->recordTitleAttribute('body')->heading('Comments')->modifyQueryUsing(fn (Builder $query): Builder => $query->orderByDesc('created_at'))->columns([
            TextColumn::make('author.name')->label('Author')->badge()->color('primary')->placeholder('-'),
            TextColumn::make('body')->label('Comment')->limit(100)->wrap()->searchable(),
            TextColumn::make('is_internal')->label('Type')->badge()->color(fn (TicketComment $record): string => $record->is_internal ? 'warning' : 'success')->formatStateUsing(fn (TicketComment $record): string => $record->is_internal ? 'Internal' : 'Public'),
            TextColumn::make('created_at')->label('Created')->dateTime('M d, Y \a\t H:i')->sortable(),
        ])->headerActions([CreateAction::make()->visible(fn (): bool => $this->currentUser()?->can('tickets.comment') ?? false)])->recordActions([
            ViewAction::make()->visible(fn (): bool => $this->currentUser()?->can('tickets.view') ?? false),
            EditAction::make()->visible(fn (): bool => $this->currentUser()?->can('tickets.comment') ?? false),
            DeleteAction::make()->visible(fn (): bool => $this->currentUser()?->can('tickets.comment') ?? false)->requiresConfirmation(),
        ])->emptyStateHeading('No comments yet')->emptyStateDescription('Add comments to track progress and communicate with the team.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->ticketService->addComment($this->resolveTicketId(), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var TicketComment $record */
        $record->update($data);

        return $record->fresh();
    }

    protected function handleRecordDeletion(Model $record): void
    {
        /** @var TicketComment $record */
        $this->ticketService->deleteComment((int) $record->getKey());
    }

    private function resolveTicketId(): int
    {
        $ticket = $this->getOwnerRecord();
        if (! $ticket instanceof Ticket) {
            throw new InvalidArgumentException('Unable to resolve ticket context.');
        }

        return (int) $ticket->getKey();
    }

    private function userCan(string $permission): bool
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
