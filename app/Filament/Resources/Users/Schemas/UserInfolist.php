<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Personal Information')
                            ->icon(Heroicon::OutlinedUser)
                            ->schema([
                                TextEntry::make('name')
                                    ->icon(Heroicon::OutlinedUser)
                                    ->label('Full Name')
                                    ->weight('font-semibold')
                                    ->size('lg'),

                                TextEntry::make('email')
                                    ->icon(Heroicon::OutlinedEnvelope)
                                    ->label('Email Address')
                                    ->copyable()
                                    ->copyMessage('Email address copied')
                                    ->copyMessageDuration(1500),

                                TextEntry::make('unit.name')
                                    ->label('Unit')
                                    ->icon('heroicon-o-building-office-2')
                                    ->placeholder('Belum ditetapkan'),

                                IconEntry::make('email_verified_at')
                                    ->label('Email Verification Status')
                                    ->boolean()
                                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                                    ->falseIcon(Heroicon::OutlinedXCircle)
                                    ->trueColor('success')
                                    ->falseColor('danger')
                                    ->formatStateUsing(fn ($state) => $state !== null)
                                    ->helperText(fn ($record) => $record->email_verified_at
                                        ? 'Verified on '.$record->email_verified_at->format('M d, Y \a\t g:i A')
                                        : 'Email not verified'),

                                TextEntry::make('email_verified_at')
                                    ->label('Verified At')
                                    ->dateTime('M d, Y \a\t g:i A')
                                    ->placeholder('Not verified')
                                    ->icon(Heroicon::OutlinedCalendar)
                                    ->visible(fn ($record) => $record->email_verified_at !== null),
                            ]),

                        Section::make('Access & Permissions')
                            ->icon(Heroicon::OutlinedShieldCheck)
                            ->schema([
                                TextEntry::make('roles')
                                    ->label('Roles')
                                    ->badge()
                                    ->color('primary')
                                    ->separator(',')
                                    ->placeholder('No roles assigned')
                                    ->icon(Heroicon::OutlinedShieldCheck)
                                    ->getStateUsing(fn ($record) => $record->roles->pluck('name')->map(fn ($name) => ucwords(str_replace('_', ' ', $name)))->toArray()),

                                TextEntry::make('permissions')
                                    ->label('Direct Permissions')
                                    ->badge()
                                    ->color('success')
                                    ->separator(',')
                                    ->placeholder('No direct permissions')
                                    ->icon(Heroicon::OutlinedKey)
                                    ->getStateUsing(fn ($record) => $record->permissions->take(10)->pluck('name')->map(fn ($name) => ucwords(str_replace('_', ' ', $name)))->toArray())
                                    ->helperText(fn ($record) => $record->permissions->count() > 10
                                        ? 'Showing 10 of '.$record->permissions->count().' permissions. Edit user to see all.'
                                        : null),
                            ]),
                    ]),

                Section::make('Account Status')
                    ->icon(Heroicon::OutlinedInformationCircle)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                IconEntry::make('two_factor_confirmed_at')
                                    ->label('Two-Factor Authentication')
                                    ->boolean()
                                    ->trueIcon(Heroicon::OutlinedLockClosed)
                                    ->falseIcon(Heroicon::OutlinedLockOpen)
                                    ->trueColor('success')
                                    ->falseColor('gray')
                                    ->formatStateUsing(fn ($state) => $state !== null)
                                    ->helperText(fn ($record) => $record->two_factor_confirmed_at
                                        ? 'Enabled on '.$record->two_factor_confirmed_at->format('M d, Y')
                                        : 'Not enabled'),

                                IconEntry::make('deleted_at')
                                    ->label('Account Status')
                                    ->boolean()
                                    ->trueIcon(Heroicon::OutlinedArchiveBox)
                                    ->falseIcon(Heroicon::OutlinedCheckCircle)
                                    ->trueColor('danger')
                                    ->falseColor('success')
                                    ->formatStateUsing(fn ($state) => $state === null)
                                    ->helperText(fn ($record) => $record->deleted_at
                                        ? 'Deleted on '.$record->deleted_at->format('M d, Y \a\t g:i A')
                                        : 'Active'),

                                TextEntry::make('id')
                                    ->label('User ID')
                                    ->badge()
                                    ->color('gray')
                                    ->copyable()
                                    ->copyMessage('User ID copied')
                                    ->copyMessageDuration(1500),
                            ]),
                    ])
                    ->collapsible(),

                Section::make('Timestamps')
                    ->icon(Heroicon::OutlinedClock)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('M d, Y \a\t g:i A')
                                    ->icon(Heroicon::OutlinedCalendar)
                                    ->placeholder('-'),

                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('M d, Y \a\t g:i A')
                                    ->icon(Heroicon::OutlinedPencilSquare)
                                    ->placeholder('-'),

                                TextEntry::make('deleted_at')
                                    ->label('Deleted At')
                                    ->dateTime('M d, Y \a\t g:i A')
                                    ->icon(Heroicon::OutlinedArchiveBox)
                                    ->placeholder('Not deleted')
                                    ->visible(fn ($record) => $record->deleted_at !== null),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }
}
