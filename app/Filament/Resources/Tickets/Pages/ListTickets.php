<?php

declare(strict_types=1);

namespace App\Filament\Resources\Tickets\Pages;

use App\Filament\Resources\Tickets\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->visible(fn (): bool => static::getResource()::canCreate()),
            ExportAction::make()
                ->exports([
                    ExcelExport::make('tickets')->fromTable(),
                ])
                ->visible(fn (): bool => static::getResource()::canViewAny()),
        ];
    }
}
