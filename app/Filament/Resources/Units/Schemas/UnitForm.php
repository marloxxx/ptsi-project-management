<?php

declare(strict_types=1);

namespace App\Filament\Resources\Units\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UnitForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Unit Information')
                    ->schema([
                        Grid::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label('Unit Name')
                                    ->required()
                                    ->maxLength(120)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Direktorat Operasional'),

                                TextInput::make('code')
                                    ->label('Unit Code')
                                    ->required()
                                    ->minLength(2)
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('DO-01')
                                    ->helperText('Gunakan kode unik sesuai standar perusahaan.')
                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? strtoupper($state) : null),

                                TextInput::make('sinav_unit_id')
                                    ->label('SINAV Unit ID')
                                    ->maxLength(50)
                                    ->placeholder('Opsional — sesuai referensi SINAV')
                                    ->helperText('Biarkan kosong jika belum sinkron dengan SINAV.')
                                    ->columnSpan([
                                        'sm' => 2,
                                    ]),

                                Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'active' => 'Aktif',
                                        'inactive' => 'Nonaktif',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->native(false),
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
