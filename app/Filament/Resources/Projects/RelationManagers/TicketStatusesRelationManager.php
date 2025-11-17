<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Services\ProjectServiceInterface;
use App\Models\Project;
use App\Models\TicketStatus;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class TicketStatusesRelationManager extends RelationManager
{
    protected static string $relationship = 'ticketStatuses';

    protected static ?string $recordTitleAttribute = 'name';

    protected ProjectServiceInterface $projectService;

    public function boot(ProjectServiceInterface $projectService): void
    {
        $this->projectService = $projectService;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Status Details')
                ->schema([
                    Grid::make([
                        'sm' => 2,
                    ])->schema([
                        TextInput::make('name')
                            ->label('Status Name')
                            ->required()
                            ->maxLength(100),

                        ColorPicker::make('color')
                            ->label('Badge Color')
                            ->default('#2563EB'),
                    ]),

                    Toggle::make('is_completed')
                        ->label('Marks Completion')
                        ->default(false),

                    TextInput::make('sort_order')
                        ->label('Sort Order')
                        ->numeric()
                        ->nullable()
                        ->helperText('Leave blank to append to the end of the board.'),
                ])
                ->icon(Heroicon::OutlinedListBullet)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->heading('Ticket Statuses')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderBy('sort_order'))
            ->columns([
                TextColumn::make('name')
                    ->label('Status')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->alignRight()
                    ->placeholder('-'),

                IconColumn::make('is_completed')
                    ->label('Completed')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => $this->canCreate())
                    ->visible(fn (): bool => $this->canCreate()),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (TicketStatus $record): bool => $this->canEditRecord($record))
                    ->visible(fn (TicketStatus $record): bool => $this->canEditRecord($record)),
                DeleteAction::make()
                    ->authorize(fn (TicketStatus $record): bool => $this->canDeleteRecord($record))
                    ->visible(fn (TicketStatus $record): bool => $this->canDeleteRecord($record))
                    ->requiresConfirmation(),
            ])
            ->emptyStateHeading('No ticket statuses configured')
            ->emptyStateDescription('Statuses control board columns and completion flow. Create at least one for each workflow stage.');
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

        $payload = [
            'name' => Arr::get($data, 'name'),
            'color' => Arr::get($data, 'color', '#2563EB'),
            'is_completed' => (bool) Arr::get($data, 'is_completed', false),
        ];

        if (filled(Arr::get($data, 'sort_order'))) {
            $payload['sort_order'] = (int) $data['sort_order'];
        }

        return $this->projectService->addStatus((int) $project->getKey(), $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof TicketStatus) {
            throw new InvalidArgumentException('Expected TicketStatus model.');
        }

        $payload = [
            'name' => Arr::get($data, 'name', $record->name),
            'color' => Arr::get($data, 'color', $record->color),
            'is_completed' => (bool) Arr::get($data, 'is_completed', $record->is_completed),
        ];

        if (array_key_exists('sort_order', $data)) {
            $payload['sort_order'] = filled($data['sort_order']) ? (int) $data['sort_order'] : $record->sort_order;
        }

        return $this->projectService->updateStatus((int) $record->getKey(), $payload);
    }

    protected function handleRecordDeletion(Model $record): void
    {
        if (! $record instanceof TicketStatus) {
            throw new InvalidArgumentException('Expected TicketStatus model.');
        }

        $this->projectService->removeStatus((int) $record->getKey());
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
        return $user->hasPermissionTo('projects.manage-statuses');
    }

    protected function canEditRecord(Model $record): bool
    {
        if (! $record instanceof TicketStatus) {
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
        if (! $record instanceof TicketStatus) {
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
