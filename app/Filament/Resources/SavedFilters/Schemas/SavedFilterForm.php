<?php

declare(strict_types=1);

namespace App\Filament\Resources\SavedFilters\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class SavedFilterForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $userProjects = ($user instanceof User) ? $user->projects()->pluck('name', 'id')->toArray() : [];

        return $schema
            ->components([
                Section::make('Filter Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Filter Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('My Custom Filter'),

                                Select::make('visibility')
                                    ->label('Visibility')
                                    ->options([
                                        'private' => 'Private (Only Me)',
                                        'team' => 'Team (Project Members)',
                                        'project' => 'Project (All Project Members)',
                                        'public' => 'Public (Everyone)',
                                    ])
                                    ->default('private')
                                    ->required()
                                    ->native(false)
                                    ->helperText('Control who can see and use this filter.'),

                                Select::make('project_id')
                                    ->label('Project')
                                    ->options($userProjects)
                                    ->searchable()
                                    ->nullable()
                                    ->helperText('Optional: Scope this filter to a specific project.'),

                                TextInput::make('query')
                                    ->label('Filter Query (JSON)')
                                    ->helperText('Enter filter criteria as JSON. Example: {"status_id": 1, "priority_id": 2}')
                                    ->json()
                                    ->required()
                                    ->columnSpan([
                                        'sm' => 2,
                                    ]),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 2,
                            ]),
                    ])
                    ->columns(1),
            ]);
    }
}
