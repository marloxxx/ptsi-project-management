<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Services\ProjectServiceInterface;
use App\Models\Epic;
use App\Models\Project;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class EpicsRelationManager extends RelationManager
{
    protected static string $relationship = 'epics';

    protected static ?string $recordTitleAttribute = 'name';

    protected ProjectServiceInterface $projectService;

    public function boot(ProjectServiceInterface $projectService): void
    {
        $this->projectService = $projectService;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Epic Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Epic Name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->columnSpanFull(),

                    Grid::make([
                        'sm' => 2,
                    ])->schema([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->nullable(),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->nullable()
                            ->after('start_date'),
                    ]),

                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->nullable(),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Epics')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderBy('sort_order'))
            ->columns([
                TextColumn::make('name')
                    ->label('Epic')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('end_date')
                    ->label('End')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignRight()
                    ->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => $this->canCreate())
                    ->visible(fn (): bool => $this->canCreate()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->authorize(fn (Epic $record): bool => $this->canViewRecord($record))
                    ->visible(fn (Epic $record): bool => $this->canViewRecord($record)),
                EditAction::make()
                    ->authorize(fn (Epic $record): bool => $this->canEditRecord($record))
                    ->visible(fn (Epic $record): bool => $this->canEditRecord($record)),
                DeleteAction::make()
                    ->authorize(fn (Epic $record): bool => $this->canDeleteRecord($record))
                    ->visible(fn (Epic $record): bool => $this->canDeleteRecord($record))
                    ->requiresConfirmation(),
            ])
            ->emptyStateHeading('No epics yet')
            ->emptyStateDescription('Epics help group tickets into larger deliverables.');
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

        return $this->projectService->addEpic((int) $project->getKey(), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Epic) {
            throw new InvalidArgumentException('Expected Epic model.');
        }

        return $this->projectService->updateEpic((int) $record->getKey(), $data);
    }

    protected function handleRecordDeletion(Model $record): void
    {
        if (! $record instanceof Epic) {
            throw new InvalidArgumentException('Expected Epic model.');
        }

        $this->projectService->deleteEpic((int) $record->getKey());
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
        return $user->hasPermissionTo('epics.create');
    }

    protected function canViewRecord(Model $record): bool
    {
        if (! $record instanceof Epic) {
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
        if (! $record instanceof Epic) {
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
        if (! $record instanceof Epic) {
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
