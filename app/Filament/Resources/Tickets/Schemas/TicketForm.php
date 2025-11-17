<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Schemas;

use App\Models\Epic;
use App\Models\Project;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket Details')
                    ->icon(Heroicon::OutlinedTicket)
                    ->schema([
                        Grid::make([
                            'sm' => 2,
                            'xl' => 3,
                        ])
                            ->schema([
                                Select::make('project_id')
                                    ->label('Project')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn (): array => Project::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->reactive()
                                    ->afterStateUpdated(fn (callable $set) => $set('ticket_status_id', null))
                                    ->helperText('Select the project this ticket belongs to.'),

                                Select::make('ticket_status_id')
                                    ->label('Status')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(function (callable $get): array {
                                        $projectId = $get('project_id');

                                        if (! $projectId) {
                                            return [];
                                        }

                                        return TicketStatus::query()
                                            ->where('project_id', $projectId)
                                            ->orderBy('sort_order')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->helperText('Current status of the ticket.'),

                                Select::make('priority_id')
                                    ->label('Priority')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn (): array => TicketPriority::query()
                                        ->orderBy('sort_order')
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->helperText('Priority level for this ticket.'),

                                Select::make('epic_id')
                                    ->label('Epic')
                                    ->searchable()
                                    ->preload()
                                    ->options(function (callable $get): array {
                                        $projectId = $get('project_id');

                                        if (! $projectId) {
                                            return [];
                                        }

                                        return Epic::query()
                                            ->where('project_id', $projectId)
                                            ->orderBy('sort_order')
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->nullable()
                                    ->helperText('Optional epic this ticket belongs to.'),

                                TextInput::make('uuid')
                                    ->label('Ticket ID')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($context): bool => $context === 'edit' || $context === 'view')
                                    ->helperText('Auto-generated unique ticket identifier.'),

                                TextInput::make('name')
                                    ->label('Ticket Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan([
                                        'sm' => 2,
                                        'xl' => 3,
                                    ])
                                    ->placeholder('Enter a descriptive title for this ticket'),

                                Select::make('issue_type')
                                    ->label('Issue Type')
                                    ->required()
                                    ->default('Task')
                                    ->options([
                                        'Bug' => 'Bug',
                                        'Task' => 'Task',
                                        'Story' => 'Story',
                                        'Epic' => 'Epic',
                                    ])
                                    ->helperText('Type of issue this ticket represents.'),

                                Select::make('parent_id')
                                    ->label('Parent Ticket')
                                    ->searchable()
                                    ->preload()
                                    ->options(function (callable $get): array {
                                        $projectId = $get('project_id');
                                        $currentTicketId = $get('id');

                                        if (! $projectId) {
                                            return [];
                                        }

                                        $query = \App\Models\Ticket::query()
                                            ->where('project_id', $projectId)
                                            ->orderBy('name');

                                        // Exclude current ticket to prevent self-reference
                                        if ($currentTicketId) {
                                            $query->where('id', '!=', $currentTicketId);
                                        }

                                        return $query->pluck('name', 'id')->toArray();
                                    })
                                    ->nullable()
                                    ->helperText('Optional parent ticket for sub-tasks.'),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Description')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->schema([
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(6)
                            ->maxLength(5000)
                            ->columnSpanFull()
                            ->placeholder('Provide detailed information about this ticket...'),
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
                                    ->label('Start Date')
                                    ->nullable()
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? Carbon::parse($state)->format('Y-m-d') : null),

                                DatePicker::make('due_date')
                                    ->native(false)
                                    ->weekStartsOnMonday()
                                    ->label('Due Date')
                                    ->nullable()
                                    ->after('start_date')
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state !== null ? Carbon::parse($state)->format('Y-m-d') : null),
                            ])
                            ->columns([
                                'sm' => 2,
                            ]),

                        Section::make('Assignment')
                            ->icon(Heroicon::OutlinedUsers)
                            ->schema([
                                Select::make('assignee_ids')
                                    ->label('Assignees')
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->options(fn (): array => User::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray())
                                    ->helperText('Assign team members who will work on this ticket.')
                                    ->columnSpanFull(),
                            ])
                            ->columns([
                                'sm' => 1,
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
