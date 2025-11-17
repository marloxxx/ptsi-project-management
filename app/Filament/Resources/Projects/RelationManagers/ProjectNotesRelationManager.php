<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Services\ProjectServiceInterface;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class ProjectNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'notes';

    protected static ?string $recordTitleAttribute = 'title';

    protected ProjectServiceInterface $projectService;

    public function boot(ProjectServiceInterface $projectService): void
    {
        $this->projectService = $projectService;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Note Details')
                ->schema([
                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255),

                    DatePicker::make('note_date')
                        ->label('Note Date')
                        ->required(),

                    Select::make('created_by')
                        ->label('Author')
                        ->native(false)
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => User::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray())
                        ->helperText('Defaults to the current user when left empty.'),

                    Textarea::make('body')
                        ->label('Content')
                        ->rows(6)
                        ->required()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->heading('Project Notes')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderByDesc('note_date'))
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('note_date')
                    ->label('Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('author.name')
                    ->label('Author')
                    ->badge()
                    ->color('primary')
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => $this->canCreate())
                    ->visible(fn (): bool => $this->canCreate())
                    ->mutateDataUsing(function (array $data): array {
                        // Ensure created_by is set if not provided
                        if (! array_key_exists('created_by', $data) || $data['created_by'] === null) {
                            $user = $this->currentUser();
                            if ($user) {
                                $data['created_by'] = $user->getKey();
                            } else {
                                // Fallback to Auth::id() if currentUser() returns null
                                $userId = Auth::id();
                                if ($userId) {
                                    $data['created_by'] = $userId;
                                }
                            }
                        }

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize(fn (ProjectNote $record): bool => $this->canViewRecord($record))
                    ->visible(fn (ProjectNote $record): bool => $this->canViewRecord($record)),
                EditAction::make()
                    ->authorize(fn (ProjectNote $record): bool => $this->canEditRecord($record))
                    ->visible(fn (ProjectNote $record): bool => $this->canEditRecord($record)),
                DeleteAction::make()
                    ->authorize(fn (ProjectNote $record): bool => $this->canDeleteRecord($record))
                    ->visible(fn (ProjectNote $record): bool => $this->canDeleteRecord($record))
                    ->requiresConfirmation(),
            ])
            ->emptyStateHeading('No notes yet')
            ->emptyStateDescription('Capture key decisions, risks, or summaries as project notes.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $project = $this->getOwnerRecord();

        if (! $project instanceof Project) {
            throw new InvalidArgumentException('Unable to resolve project context.');
        }

        // created_by should already be set by mutateDataUsing() on CreateAction
        // This is just a fallback in case it's still not set
        if (! array_key_exists('created_by', $data) || $data['created_by'] === null) {
            $user = $this->currentUser();
            if ($user) {
                $data['created_by'] = $user->getKey();
            } else {
                // Fallback to Auth::id() if currentUser() returns null
                $userId = Auth::id();
                if ($userId) {
                    $data['created_by'] = $userId;
                }
            }
        }

        return $this->projectService->addNote((int) $project->getKey(), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof ProjectNote) {
            throw new InvalidArgumentException('Expected ProjectNote model.');
        }

        return $this->projectService->updateNote((int) $record->getKey(), $data);
    }

    protected function handleRecordDeletion(Model $record): void
    {
        if (! $record instanceof ProjectNote) {
            throw new InvalidArgumentException('Expected ProjectNote model.');
        }

        $this->projectService->deleteNote((int) $record->getKey());
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected function canCreate(): bool
    {
        $user = $this->currentUser();
        $project = $this->getOwnerRecord();

        if (! $user || ! $project instanceof Project) {
            return false;
        }

        // Load members if not loaded
        if (! $project->relationLoaded('members')) {
            $project->load('members');
        }

        // Check if user is project member
        if (! $project->members->contains('id', $user->getKey())) {
            return false;
        }

        // Admin yang adalah project member selalu boleh
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return true;
        }

        // Check permission - use permission string directly for RelationManager context
        return $user->hasPermissionTo('project-notes.create');
    }

    protected function canViewRecord(Model $record): bool
    {
        if (! $record instanceof ProjectNote) {
            return false;
        }

        $user = $this->currentUser();

        if (! $user) {
            return false;
        }

        // Load project and members if not loaded
        if (! $record->relationLoaded('project')) {
            $record->load('project.members');
        }

        return $user->can('view', $record);
    }

    protected function canEditRecord(Model $record): bool
    {
        if (! $record instanceof ProjectNote) {
            return false;
        }

        $user = $this->currentUser();

        if (! $user) {
            return false;
        }

        // Load project and members if not loaded
        if (! $record->relationLoaded('project')) {
            $record->load('project.members');
        }

        // Admin yang adalah project member selalu boleh
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            if ($record->project->members->contains('id', $user->getKey())) {
                return true;
            }
        }

        return $user->can('update', $record);
    }

    protected function canDeleteRecord(Model $record): bool
    {
        if (! $record instanceof ProjectNote) {
            return false;
        }

        $user = $this->currentUser();

        if (! $user) {
            return false;
        }

        // Load project and members if not loaded
        if (! $record->relationLoaded('project')) {
            $record->load('project.members');
        }

        // Admin yang adalah project member selalu boleh
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            if ($record->project->members->contains('id', $user->getKey())) {
                return true;
            }
        }

        return $user->can('delete', $record);
    }
}
