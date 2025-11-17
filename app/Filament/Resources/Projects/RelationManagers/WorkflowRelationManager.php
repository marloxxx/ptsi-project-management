<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Repositories\ProjectWorkflowRepositoryInterface;
use App\Models\Project;
use App\Models\ProjectWorkflow;
use App\Models\TicketStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WorkflowRelationManager extends RelationManager
{
    protected static string $relationship = 'workflow';

    protected static ?string $title = 'Workflow';

    protected static ?string $pluralLabel = 'Workflow';

    protected static ?string $modelLabel = 'Workflow';

    protected ProjectWorkflowRepositoryInterface $workflowRepository;

    public function boot(ProjectWorkflowRepositoryInterface $workflowRepository): void
    {
        $this->workflowRepository = $workflowRepository;
    }

    public function form(Schema $schema): Schema
    {
        /** @var Project $project */
        $project = $this->getOwnerRecord();
        $statuses = $project->ticketStatuses()->orderBy('sort_order')->get();

        $statusOptions = $statuses->pluck('name', 'id')->toArray();

        return $schema->components([
            Section::make('Workflow Configuration')
                ->description('Configure allowed status transitions for this project. If no workflow is defined, all transitions are allowed.')
                ->schema([
                    Select::make('initial_statuses')
                        ->label('Initial Statuses')
                        ->helperText('Statuses that can be assigned when creating a new ticket. Select which statuses can be used when creating new tickets.')
                        ->options($statusOptions)
                        ->multiple()
                        ->searchable(),

                    Section::make('Status Transitions')
                        ->description('Define allowed transitions between statuses')
                        ->schema(
                            $statuses->map(
                                fn(TicketStatus $status) => CheckboxList::make("transitions.{$status->id}")
                                    ->label("From: {$status->name}")
                                    ->helperText('Select allowed target statuses')
                                    ->options($statusOptions)
                                    ->columns(2)
                                    ->descriptions(
                                        $statuses->mapWithKeys(
                                            fn(TicketStatus $target) => [$target->id => $target->is_completed ? 'Completed' : 'In Progress']
                                        )->toArray()
                                    )
                            )->toArray()
                        ),
                ])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Workflow')
            ->description('Configure allowed status transitions for tickets in this project')
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->limit(1))
            ->columns([
                TextColumn::make('project.name')
                    ->label('Project')
                    ->sortable(),

                TextColumn::make('definition')
                    ->label('Transitions')
                    ->formatStateUsing(function (?array $definition) {
                        if (empty($definition['transitions'])) {
                            return 'All transitions allowed (no workflow defined)';
                        }

                        $count = count($definition['transitions'] ?? []);

                        return "{$count} transition(s) configured";
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->visible(function (): bool {
                        /** @var Project $project */
                        $project = $this->getOwnerRecord();

                        return $project->workflow === null;
                    })
                    ->using(function (array $data): Model {
                        /** @var Project $project */
                        $project = $this->getOwnerRecord();

                        $definition = [
                            'initial_statuses' => $data['initial_statuses'] ?? [],
                            'transitions' => $this->formatTransitions($data['transitions'] ?? []),
                        ];

                        return $this->workflowRepository->createOrUpdate($project, [
                            'definition' => $definition,
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (ProjectWorkflow $record, array $data): Model {
                        $definition = [
                            'initial_statuses' => $data['initial_statuses'] ?? [],
                            'transitions' => $this->formatTransitions($data['transitions'] ?? []),
                        ];

                        return $this->workflowRepository->update($record, [
                            'definition' => $definition,
                        ]);
                    }),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->using(function (ProjectWorkflow $record): bool {
                        return $this->workflowRepository->delete($record);
                    }),
            ])
            ->emptyStateHeading('No Workflow Configured')
            ->emptyStateDescription('Click "Create" to configure status transitions for this project.')
            ->emptyStateIcon('heroicon-o-arrows-right-left');
    }

    /**
     * Format transitions from form data.
     *
     * @param  array<string, array<int>>  $transitions
     * @return array<string, array<int>>
     */
    protected function formatTransitions(array $transitions): array
    {
        $formatted = [];

        foreach ($transitions as $fromStatusId => $toStatusIds) {
            if (! empty($toStatusIds)) {
                $formatted[(string) $fromStatusId] = array_map('intval', $toStatusIds);
            }
        }

        return $formatted;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Project $project */
        $project = $this->getOwnerRecord();
        /** @var ProjectWorkflow|null $workflow */
        $workflow = $project->workflow;

        if (! $workflow) {
            return $data;
        }

        $definition = $workflow->definition ?? [];

        $data['initial_statuses'] = $definition['initial_statuses'] ?? [];

        // Transform transitions to form format
        $transitions = $definition['transitions'] ?? [];
        if (! empty($transitions)) {
            foreach ($transitions as $fromStatusId => $toStatusIds) {
                $data["transitions.{$fromStatusId}"] = $toStatusIds;
            }
        }

        return $data;
    }
}
