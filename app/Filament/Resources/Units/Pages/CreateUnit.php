<?php

declare(strict_types=1);

namespace App\Filament\Resources\Units\Pages;

use App\Domain\Services\UnitServiceInterface;
use App\Filament\Resources\Units\UnitResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUnit extends CreateRecord
{
    protected static string $resource = UnitResource::class;

    protected UnitServiceInterface $unitService;

    public function boot(UnitServiceInterface $unitService): void
    {
        $this->unitService = $unitService;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->unitService->create($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Unit created';
    }
}
