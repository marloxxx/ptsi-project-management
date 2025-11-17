<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Domain\Services\CustomFieldServiceInterface;
use App\Models\Project;
use App\Models\ProjectCustomField;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class CustomFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'customFields';

    protected static ?string $title = 'Custom Fields';

    protected static ?string $pluralLabel = 'Custom Fields';

    protected static ?string $modelLabel = 'Custom Field';

    protected CustomFieldServiceInterface $customFieldService;

    public function boot(CustomFieldServiceInterface $customFieldService): void
    {
        $this->customFieldService = $customFieldService;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Field Configuration')
                ->schema([
                    Grid::make([
                        'sm' => 2,
                    ])->schema([
                        TextInput::make('key')
                            ->label('Field Key')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->helperText('Unique identifier for this field (e.g., "client_name", "estimated_hours")')
                            ->alphaDash()
                            ->regex('/^[a-z0-9_]+$/')
                            ->validationMessages([
                                'regex' => 'Key must contain only lowercase letters, numbers, and underscores.',
                            ]),

                        TextInput::make('label')
                            ->label('Field Label')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Display name for this field'),
                    ]),

                    Grid::make([
                        'sm' => 2,
                    ])->schema([
                        Select::make('type')
                            ->label('Field Type')
                            ->required()
                            ->options([
                                'text' => 'Text',
                                'number' => 'Number',
                                'select' => 'Select (Dropdown)',
                                'date' => 'Date',
                            ])
                            ->default('text')
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set) => $set('options', null)),

                        TextInput::make('order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ]),

                    Section::make('Options')
                        ->schema([
                            Textarea::make('options')
                                ->label('Select Options')
                                ->helperText('Enter options for select/dropdown fields, one per line.')
                                ->rows(4)
                                ->visible(fn (callable $get) => $get('type') === 'select')
                                ->dehydrateStateUsing(function (?string $state): ?array {
                                    if (empty($state)) {
                                        return null;
                                    }

                                    $options = array_filter(
                                        array_map('trim', explode("\n", $state)),
                                        fn (string $option): bool => ! empty($option)
                                    );

                                    return empty($options) ? null : array_values($options);
                                })
                                ->formatStateUsing(function (?array $state): string {
                                    if (empty($state)) {
                                        return '';
                                    }

                                    return implode("\n", $state);
                                }),
                        ])
                        ->visible(fn (callable $get) => $get('type') === 'select')
                        ->collapsible(),

                    Grid::make([
                        'sm' => 2,
                    ])->schema([
                        Toggle::make('required')
                            ->label('Required Field')
                            ->default(false)
                            ->helperText('Users must fill this field when creating/editing tickets'),

                        Toggle::make('active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive fields are hidden from ticket forms'),
                    ]),
                ])
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->heading('Custom Fields')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->orderBy('order'))
            ->columns([
                TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'text' => 'gray',
                        'number' => 'blue',
                        'select' => 'green',
                        'date' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('order')
                    ->label('Order')
                    ->sortable()
                    ->alignRight()
                    ->placeholder('-'),

                IconColumn::make('required')
                    ->label('Required')
                    ->boolean(),

                IconColumn::make('active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->authorize(fn (): bool => $this->canCreate())
                    ->visible(fn (): bool => $this->canCreate()),
            ])
            ->recordActions([
                EditAction::make()
                    ->authorize(fn (ProjectCustomField $record): bool => $this->canEditRecord($record))
                    ->visible(fn (ProjectCustomField $record): bool => $this->canEditRecord($record)),
                DeleteAction::make()
                    ->authorize(fn (ProjectCustomField $record): bool => $this->canDeleteRecord($record))
                    ->visible(fn (ProjectCustomField $record): bool => $this->canDeleteRecord($record))
                    ->requiresConfirmation(),
            ])
            ->emptyStateHeading('No custom fields configured')
            ->emptyStateDescription('Create custom fields to capture additional information specific to this project.');
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
            'key' => Arr::get($data, 'key'),
            'label' => Arr::get($data, 'label'),
            'type' => Arr::get($data, 'type', 'text'),
            'required' => (bool) Arr::get($data, 'required', false),
            'active' => (bool) Arr::get($data, 'active', true),
            'order' => (int) Arr::get($data, 'order', 0),
        ];

        // Handle options for select fields (already processed by dehydrateStateUsing)
        if (Arr::get($data, 'type') === 'select') {
            $options = Arr::get($data, 'options');
            $payload['options'] = is_array($options) && ! empty($options) ? array_values($options) : null;
        } else {
            $payload['options'] = null;
        }

        return $this->customFieldService->createField((int) $project->getKey(), $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof ProjectCustomField) {
            throw new InvalidArgumentException('Expected ProjectCustomField model.');
        }

        $payload = [
            'key' => Arr::get($data, 'key', $record->key),
            'label' => Arr::get($data, 'label', $record->label),
            'type' => Arr::get($data, 'type', $record->type),
            'required' => (bool) Arr::get($data, 'required', $record->required),
            'active' => (bool) Arr::get($data, 'active', $record->active),
            'order' => (int) Arr::get($data, 'order', $record->order),
        ];

        // Handle options for select fields (already processed by dehydrateStateUsing)
        if (Arr::get($data, 'type') === 'select') {
            $options = Arr::get($data, 'options');
            $payload['options'] = is_array($options) && ! empty($options) ? array_values($options) : null;
        } else {
            $payload['options'] = null;
        }

        return $this->customFieldService->updateField((int) $record->getKey(), $payload);
    }

    protected function handleRecordDeletion(Model $record): void
    {
        if (! $record instanceof ProjectCustomField) {
            throw new InvalidArgumentException('Expected ProjectCustomField model.');
        }

        $this->customFieldService->deleteField((int) $record->getKey());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Format options array to textarea format for editing
        if (isset($data['options']) && is_array($data['options'])) {
            $data['options'] = implode("\n", $data['options']);
        }

        return $data;
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

        // Check permission
        return $user->hasPermissionTo('projects.manage-custom-fields') || $user->can('update', $project);
    }

    protected function canEditRecord(Model $record): bool
    {
        if (! $record instanceof ProjectCustomField) {
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

        return $user->can('update', $record->project);
    }

    protected function canDeleteRecord(Model $record): bool
    {
        if (! $record instanceof ProjectCustomField) {
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

        return $user->can('update', $record->project);
    }
}
