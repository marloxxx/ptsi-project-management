<?php

namespace App\Filament\Resources\SavedFilters\Pages;

use App\Filament\Resources\SavedFilters\SavedFilterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavedFilters extends ListRecords
{
    protected static string $resource = SavedFilterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
