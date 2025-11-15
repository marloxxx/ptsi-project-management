<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Schemas;

use App\Domain\Services\PermissionCatalogServiceInterface;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Role Details')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Role Name')
                                    ->unique(ignoreRecord: true)
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter role name')
                                    ->columnSpan([
                                        'sm' => 2,
                                    ]),

                                TextInput::make('guard_name')
                                    ->label('Guard Name')
                                    ->default((string) config('auth.defaults.guard'))
                                    ->nullable()
                                    ->maxLength(255)
                                    ->helperText('Leave empty to use default guard')
                                    ->columnSpan([
                                        'sm' => 2,
                                    ]),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 2,
                            ]),
                    ])
                    ->columnSpanFull(),
                Section::make('Permissions')
                    ->schema([
                        CheckboxList::make('permissions')
                            ->label('Permissions')
                            ->options(fn (PermissionCatalogServiceInterface $catalog): array => $catalog->groupedOptions())
                            ->default([])
                            ->searchable()
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
