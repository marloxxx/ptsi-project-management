<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Services\ProjectServiceInterface;
use App\Models\Epic;
use App\Models\Project;
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
                    ->visible(fn (): bool => $this->userCan('epics.create')),
            ])
            ->recordActions([
                ViewAction::make()
                    ->visible(fn (): bool => $this->userCan('epics.view')),
                EditAction::make()
                    ->visible(fn (): bool => $this->userCan('epics.update')),
                DeleteAction::make()
                    ->visible(fn (): bool => $this->userCan('epics.delete'))
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
        return $this->projectService->addEpic($this->resolveProjectId(), $data);
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
