<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Domain\Services\ProjectServiceInterface;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected ProjectServiceInterface $projectService;

    public function boot(ProjectServiceInterface $projectService): void
    {
        $this->projectService = $projectService;
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn(): bool => static::getResource()::canDelete($this->record))
                ->requiresConfirmation(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Project $record */
        $record = $this->record;

        $record->loadMissing('members');
        $data['member_ids'] = $record->members
            ->pluck('id')
            ->map(fn($id): int => (int) $id)
            ->all();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Project $record */
        $memberIds = null;

        if (array_key_exists('member_ids', $data)) {
            $memberIdsInput = is_array($data['member_ids']) ? $data['member_ids'] : [];

            /** @var array<int, int> $memberIds */
            $memberIds = array_values(array_map(
                static fn($id): int => (int) $id,
                array_filter(
                    $memberIdsInput,
                    static fn($id): bool => $id !== null && $id !== ''
                )
            ));
        }

        unset($data['member_ids'], $data['status_presets']);

        return $this->projectService->update((int) $record->getKey(), $data, $memberIds);
    }

    protected function handleRecordDeletion(Model $record): void
    {
        /** @var Project $record */
        $this->projectService->delete((int) $record->getKey());
    }
}
