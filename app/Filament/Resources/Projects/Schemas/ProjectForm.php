<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Schemas;

use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Project Details')
                    ->icon(Heroicon::OutlinedFolder)
                    ->schema([
                        Grid::make([
                            'sm' => 2,
                            'xl' => 3,
                        ])
                            ->schema([
                                TextInput::make('name')
                                    ->label('Project Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Project X Implementation'),

                                TextInput::make('ticket_prefix')
                                    ->label('Ticket Prefix')
                                    ->required()
                                    ->maxLength(10)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Displayed before ticket numbers (e.g. PROJ-101).')
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? strtoupper($state) : null),

                                ColorPicker::make('color')
                                    ->label('Card Color')
                                    ->nullable()
                                    ->default('#184980'),

                                Textarea::make('description')
                                    ->label('Summary')
                                    ->rows(4)
                                    ->maxLength(2000)
                                    ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 3,
                                    ])
                                    ->placeholder('High-level overview, goals, or important notes for the team.'),
                            ]),
                    ])
                    ->columnSpanFull(),

                Grid::make([
                    'sm' => 2,
                ])
                    ->schema([
                        Section::make('Timeline')
                            ->icon(Heroicon::OutlinedCalendar)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->native(false)
                                    ->weekStartsOnMonday()
                                    ->live(onBlur: true)
                                    ->label('Start Date')
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? Carbon::parse($state)->format('Y-m-d') : null),

                                DatePicker::make('end_date')
                                    ->native(false)
                                    ->weekStartsOnMonday()
                                    ->live(onBlur: true)
                                    ->label('End Date')
                                    ->after('start_date')
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? Carbon::parse($state)->format('Y-m-d') : null),
                            ])
                            ->columns([
                                'sm' => 2,
                            ]),

                        Section::make('Team')
                            ->icon(Heroicon::OutlinedUsers)
                            ->schema([
                                Select::make('member_ids')
                                    ->label('Project Members')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn (): array => User::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->helperText('Assign team members who can work on tickets inside this project.')
                                    ->columnSpanFull(),
                            ])
                            ->columns([
                                'sm' => 1,
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Status Presets')
                    ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                    ->schema([
                        Repeater::make('status_presets')
                            ->label('Initial Ticket Statuses')
                            ->schema([
                                Grid::make([
                                    'sm' => 3,
                                ])
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Name')
                                            ->required()
                                            ->maxLength(50),

                                        ColorPicker::make('color')
                                            ->label('Color')
                                            ->default('#2563EB'),

                                        Select::make('is_completed')
                                            ->label('Completion State')
                                            ->options([
                                                '0' => 'In Progress',
                                                '1' => 'Completed',
                                            ])
                                            ->default('0')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->mutateDehydratedStateUsing(fn (?array $state): array => Collection::make($state)
                                ->filter(fn (?array $entry): bool => filled($entry['name'] ?? null))
                                ->map(fn (array $entry): array => [
                                    'name' => (string) $entry['name'],
                                    'color' => $entry['color'] ?? '#2563EB',
                                    'is_completed' => (bool) ((int) ($entry['is_completed'] ?? 0)),
                                ])
                                ->values()
                                ->all())
                            ->helperText('Leave empty to use the default Backlog/In Progress/Review/Done statuses.')
                            ->visible(fn ($context): bool => Arr::get((array) $context, 'operation') === 'create'),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
