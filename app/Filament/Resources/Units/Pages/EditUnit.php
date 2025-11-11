<?php

declare(strict_types=1);

namespace App\Filament\Resources\Units\Pages;

use App\Domain\Services\UnitServiceInterface;
use App\Filament\Resources\Units\UnitResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected UnitServiceInterface $unitService;

    public function boot(UnitServiceInterface $unitService): void
    {
        $this->unitService = $unitService;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->unitService->update($record, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Unit updated';
    }

    protected function handleRecordDeletion(Model $record): void
    {
        $this->unitService->delete($record);
    }
}
