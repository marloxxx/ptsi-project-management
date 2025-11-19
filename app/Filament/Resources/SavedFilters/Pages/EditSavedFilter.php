<?php

declare(strict_types=1);

namespace App\Filament\Resources\SavedFilters\Pages;

use App\Domain\Services\SavedFilterServiceInterface;
use App\Filament\Resources\SavedFilters\SavedFilterResource;
use App\Models\SavedFilter;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSavedFilter extends EditRecord
{
    protected static string $resource = SavedFilterResource::class;

    protected SavedFilterServiceInterface $savedFilterService;

    public function boot(SavedFilterServiceInterface $savedFilterService): void
    {
        $this->savedFilterService = $savedFilterService;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SavedFilter $savedFilter */
        $savedFilter = $record;

        return $this->savedFilterService->update($savedFilter->id, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Saved filter updated';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => static::getResource()::canDelete($this->record))
                ->requiresConfirmation()
                ->action(function () {
                    /** @var SavedFilter $record */
                    $record = $this->record;
                    $this->savedFilterService->delete($record->id);
                }),
        ];
    }
}
