<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Settings\PluginSettings;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ManagePlugins extends SettingsPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string $settings = PluginSettings::class;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Plugins';

    protected static ?string $title = 'Plugins';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plugin Management')
                    ->description('Enable or disable features on the fly. Changes take effect immediately.')
                    ->schema([
                        Grid::make()
                            ->schema([
                                Toggle::make('enable_activity_log')
                                    ->label('Activity Log')
                                    ->helperText('Track user actions and system events')
                                    ->default(true)
                                    ->columnSpan([
                                        'sm' => 1,
                                    ]),

                                Toggle::make('enable_media_library')
                                    ->label('Media Library')
                                    ->helperText('File upload and media management')
                                    ->default(true)
                                    ->columnSpan([
                                        'sm' => 1,
                                    ]),

                                Toggle::make('enable_excel_export')
                                    ->label('Excel Export')
                                    ->helperText('Import and export data via Excel')
                                    ->default(true)
                                    ->columnSpan([
                                        'sm' => 1,
                                    ]),

                                Toggle::make('enable_notifications')
                                    ->label('Notifications')
                                    ->helperText('Database notifications and alerts')
                                    ->default(true)
                                    ->columnSpan([
                                        'sm' => 1,
                                    ]),

                                Toggle::make('enable_two_factor_auth')
                                    ->label('Two-Factor Authentication')
                                    ->helperText('Enhanced security with 2FA/MFA')
                                    ->default(true)
                                    ->columnSpan([
                                        'sm' => 1,
                                    ]),

                                Toggle::make('enable_impersonation')
                                    ->label('User Impersonation')
                                    ->helperText('Allow admins to impersonate users')
                                    ->default(true)
                                    ->columnSpan([
                                        'sm' => 1,
                                    ]),
                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
