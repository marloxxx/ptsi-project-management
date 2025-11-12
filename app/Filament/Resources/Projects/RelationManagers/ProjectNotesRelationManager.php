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
                        ->options(fn(): array => User::query()
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
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->orderByDesc('note_date'))
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
                    ->visible(fn(): bool => self::currentUser()?->can('project-notes.create') ?? false),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn(): bool => self::currentUser()?->can('project-notes.view') ?? false),
                EditAction::make()
                    ->visible(fn(): bool => self::currentUser()?->can('project-notes.update') ?? false),
                DeleteAction::make()
                    ->visible(fn(): bool => self::currentUser()?->can('project-notes.delete') ?? false)
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
        /** @var Project $project */
        $project = $this->getOwnerRecord();

        return $this->projectService->addNote((int) $project->getKey(), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var ProjectNote $record */
        return $this->projectService->updateNote((int) $record->getKey(), $data);
    }

    protected function handleRecordDeletion(Model $record): void
    {
        /** @var ProjectNote $record */
        $this->projectService->deleteNote((int) $record->getKey());
    }

    /**
     * Get the current user.
     */
    private function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
