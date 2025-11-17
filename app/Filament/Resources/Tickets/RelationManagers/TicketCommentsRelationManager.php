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
        ])->headerActions([
            CreateAction::make()
                ->authorize(fn (): bool => $this->canCreate())
                ->visible(fn (): bool => $this->canCreate())
                ->mutateDataUsing(function (array $data): array {
                    // Ensure user_id is set if not provided
                    if (! array_key_exists('user_id', $data) || $data['user_id'] === null) {
                        $user = $this->currentUser();
                        if ($user) {
                            $data['user_id'] = $user->getKey();
                        } else {
                            // Fallback to Auth::id() if currentUser() returns null
                            $userId = Auth::id();
                            if ($userId) {
                                $data['user_id'] = $userId;
                            }
                        }
                    }

                    return $data;
                }),
        ])->recordActions([
            ViewAction::make()
                ->authorize(fn (TicketComment $record): bool => $this->canViewRecord($record))
                ->visible(fn (TicketComment $record): bool => $this->canViewRecord($record)),
            EditAction::make()
                ->authorize(fn (TicketComment $record): bool => $this->canEditRecord($record))
                ->visible(fn (TicketComment $record): bool => $this->canEditRecord($record)),
            DeleteAction::make()
                ->authorize(fn (TicketComment $record): bool => $this->canDeleteRecord($record))
                ->visible(fn (TicketComment $record): bool => $this->canDeleteRecord($record))
                ->requiresConfirmation(),
        ])->emptyStateHeading('No comments yet')->emptyStateDescription('Add comments to track progress and communicate with the team.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $ticket = $this->getOwnerRecord();

        if (! $ticket instanceof Ticket) {
            throw new InvalidArgumentException('Unable to resolve ticket context.');
        }

        // user_id should already be set by mutateDataUsing() on CreateAction
        // This is just a fallback in case it's still not set
        if (! array_key_exists('user_id', $data) || $data['user_id'] === null) {
            $user = $this->currentUser();
            if ($user) {
                $data['user_id'] = $user->getKey();
            } else {
                // Fallback to Auth::id() if currentUser() returns null
                $userId = Auth::id();
                if ($userId) {
                    $data['user_id'] = $userId;
                }
            }
        }

        return $this->ticketService->addComment((int) $ticket->getKey(), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof TicketComment) {
            throw new InvalidArgumentException('Expected TicketComment model.');
        }

        return $this->ticketService->updateComment((int) $record->getKey(), $data);
    }

    protected function handleRecordDeletion(Model $record): void
    {
        if (! $record instanceof TicketComment) {
            throw new InvalidArgumentException('Expected TicketComment model.');
        }

        $this->ticketService->deleteComment((int) $record->getKey());
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected function canCreate(): bool
    {
        $user = $this->currentUser();
        $ticket = $this->getOwnerRecord();

        if (! $user || ! $ticket instanceof Ticket) {
            return false;
        }

        // Load project and members if not loaded
        if (! $ticket->relationLoaded('project')) {
            $ticket->load('project.members');
        }

        // Check if user is project member
        if (! $ticket->project->members->contains('id', $user->getKey())) {
            return false;
        }

        // Admin yang adalah project member selalu boleh
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }

        // Check permission - use permission string directly for RelationManager context
        return $user->hasPermissionTo('tickets.comment');
    }

    protected function canViewRecord(Model $record): bool
    {
        if (! $record instanceof TicketComment) {
            return false;
        }

        $user = $this->currentUser();

        if (! $user) {
            return false;
        }

        // Load ticket and project with members if not loaded
        if (! $record->relationLoaded('ticket')) {
            $record->load('ticket.project.members');
        }

        return $user->can('view', $record);
    }

    protected function canEditRecord(Model $record): bool
    {
        if (! $record instanceof TicketComment) {
            return false;
        }

        $user = $this->currentUser();

        if (! $user) {
            return false;
        }

        // Load ticket and project with members if not loaded
        if (! $record->relationLoaded('ticket')) {
            $record->load('ticket.project.members');
        }

        // Admin yang adalah project member selalu boleh
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            if ($record->ticket->project->members->contains('id', $user->getKey())) {
                return true;
            }
        }

        return $user->can('update', $record);
    }

    protected function canDeleteRecord(Model $record): bool
    {
        if (! $record instanceof TicketComment) {
            return false;
        }

        $user = $this->currentUser();

        if (! $user) {
            return false;
        }

        // Load ticket and project with members if not loaded
        if (! $record->relationLoaded('ticket')) {
            $record->load('ticket.project.members');
        }

        // Admin yang adalah project member selalu boleh
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            if ($record->ticket->project->members->contains('id', $user->getKey())) {
                return true;
            }
        }

        return $user->can('delete', $record);
    }
}
