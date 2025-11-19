<?php

declare(strict_types=1);

namespace App\Filament\Resources\SavedFilters\Pages;

use App\Domain\Services\SavedFilterServiceInterface;
use App\Filament\Resources\SavedFilters\SavedFilterResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSavedFilter extends CreateRecord
{
    protected static string $resource = SavedFilterResource::class;

    protected SavedFilterServiceInterface $savedFilterService;

    public function boot(SavedFilterServiceInterface $savedFilterService): void
    {
        $this->savedFilterService = $savedFilterService;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return $this->savedFilterService->create($data);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Saved filter created';
    }
}
