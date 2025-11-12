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
                    ->visible(fn (): bool => $this->userCan('project-notes.create')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn (): bool => $this->userCan('project-notes.view')),
                EditAction::make()
                    ->visible(fn (): bool => $this->userCan('project-notes.update')),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->userCan('project-notes.delete'))
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
        return $this->projectService->addNote($this->resolveProjectId(), $data);
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

    private function resolveProjectId(): int
    {
        $project = $this->getOwnerRecord();

        if (! $project instanceof Project) {
            throw new InvalidArgumentException('Unable to resolve project context.');
        }

        return (int) $project->getKey();
    }

    private function userCan(string $permission): bool
    {
        return Auth::user()?->can($permission) ?? false;
    }
}
