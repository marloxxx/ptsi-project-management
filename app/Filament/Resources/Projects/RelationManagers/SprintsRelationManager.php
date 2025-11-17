<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Services\SprintServiceInterface;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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

class SprintsRelationManager extends RelationManager
{
    protected static string $relationship = 'sprints';

    protected static ?string $recordTitleAttribute = 'name';

    protected SprintServiceInterface $sprintService;

    public function boot(SprintServiceInterface $sprintService): void
    {
        $this->sprintService = $sprintService;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sprint Details')
                ->schema([
                    TextInput::make('name')
                        ->label('Sprint Name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('goal')
                        ->label('Sprint Goal')
                        ->rows(3)
                        ->columnSpanFull(),

                    Grid::make([
                        'sm' => 2,
                    ])->schema([
                        Select::make('state')
                            ->label('State')
                            ->options([
                                'Planned' => 'Planned',
                                'Active' => 'Active',
                                'Closed' => 'Closed',
                            ])
                            ->default('Planned')
                            ->required(),

                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->nullable(),

                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->nullable()
                            ->after('start_date'),
                    ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Sprints')
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->orderBy('start_date', 'desc'))
            ->columns([
                TextColumn::make('name')
                    ->label('Sprint')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('state')
                    ->label('State')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Active' => 'success',
                        'Closed' => 'gray',
                        'Planned' => 'info',
                        default => 'gray',
                    })
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

                TextColumn::make('tickets_count')
                    ->label('Tickets')
                    ->counts('tickets')
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn(): bool => $this->canCreate())
                    ->visible(fn(): bool => $this->canCreate())
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
                    ->authorize(fn(Sprint $record): bool => $this->canViewRecord($record))
                    ->visible(fn(Sprint $record): bool => $this->canViewRecord($record)),
                EditAction::make()
                    ->authorize(fn(Sprint $record): bool => $this->canEditRecord($record))
                    ->visible(fn(Sprint $record): bool => $this->canEditRecord($record)),
                Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn(Sprint $record): bool => $this->canActivate($record) && $record->isPlanned())
                    ->action(function (Sprint $record): void {
                        $this->sprintService->activate($record);
                        Notification::make()
                            ->title('Sprint activated')
                            ->success()
                            ->send();
                    }),
                Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->authorize(fn(Sprint $record): bool => $this->canClose($record))
                    ->visible(fn(Sprint $record): bool => $this->canClose($record) && $record->isActive())
                    ->action(function (Sprint $record): void {
                        $this->sprintService->close($record);
                        Notification::make()
                            ->title('Sprint closed')
                            ->success()
                            ->send();
                    }),
                Action::make('reopen')
                    ->label('Reopen')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn(Sprint $record): bool => $this->canReopen($record) && $record->isClosed())
                    ->action(function (Sprint $record): void {
                        $this->sprintService->reopen($record);
                        Notification::make()
                            ->title('Sprint reopened')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->authorize(fn(Sprint $record): bool => $this->canDeleteRecord($record))
                    ->visible(fn(Sprint $record): bool => $this->canDeleteRecord($record))
                    ->requiresConfirmation(),
            ])
            ->emptyStateHeading('No sprints yet')
            ->emptyStateDescription('Sprints help organize work into time-boxed iterations.');
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

        return $this->sprintService->create($project, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Sprint) {
            throw new InvalidArgumentException('Expected Sprint model.');
        }

        return $this->sprintService->update($record, $data);
    }

    protected function handleRecordDeletion(Model $record): void
    {
        if (! $record instanceof Sprint) {
            throw new InvalidArgumentException('Expected Sprint model.');
        }

        // Unassign tickets before deleting sprint
        $record->tickets()->update(['sprint_id' => null]);

        // Delete sprint directly
        $record->delete();
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
        return $user->hasPermissionTo('sprints.create');
    }

    protected function canViewRecord(Model $record): bool
    {
        if (! $record instanceof Sprint) {
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
        if (! $record instanceof Sprint) {
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
        if (! $record instanceof Sprint) {
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

    protected function canActivate(Sprint $record): bool
    {
        $user = $this->currentUser();

        if (! $user) {
            return false;
        }

        // Load project and members if not loaded
        if (! $record->relationLoaded('project')) {
            $record->load('project.members');
        }

        return $user->can('activate', $record);
    }

    protected function canClose(Sprint $record): bool
    {
        $user = $this->currentUser();

        if (! $user) {
            return false;
        }

        // Load project and members if not loaded
        if (! $record->relationLoaded('project')) {
            $record->load('project.members');
        }

        return $user->can('close', $record);
    }

    protected function canReopen(Sprint $record): bool
    {
        $user = $this->currentUser();

        if (! $user) {
            return false;
        }

        // Load project and members if not loaded
        if (! $record->relationLoaded('project')) {
            $record->load('project.members');
        }

        return $user->can('reopen', $record);
    }
}
