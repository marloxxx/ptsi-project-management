<?php

declare(strict_types=1);

namespace App\Filament\Resources\Units\Pages;

use App\Domain\Services\UnitServiceInterface;
use App\Filament\Resources\Units\UnitResource;
use App\Models\Unit;
use Filament\Actions\DeleteAction;
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

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Unit $unit */
        $unit = $record;

        return $this->unitService->update($unit, $data);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Unit updated';
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn(): bool => static::getResource()::canDelete($this->record))
                ->requiresConfirmation(),
        ];
    }

    /**
     * @param  Unit  $record
     */
    protected function handleRecordDeletion(Model $record): void
    {
        /** @var Unit $unit */
        $unit = $record;

        $this->unitService->delete($unit);
    }
}
