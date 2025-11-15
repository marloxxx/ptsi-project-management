<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Domain\Services\ProjectServiceInterface;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class CreateProject extends CreateRecord
{
    protected static string $resource = ProjectResource::class;

    protected ProjectServiceInterface $projectService;

    public function boot(ProjectServiceInterface $projectService): void
    {
        $this->projectService = $projectService;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $memberIdsInput = is_array($data['member_ids'] ?? null) ? $data['member_ids'] : [];

        /** @var array<int, int> $memberIds */
        $memberIds = array_values(array_map(
            static fn ($id): int => (int) $id,
            array_filter(
                $memberIdsInput,
                static fn ($id): bool => $id !== null && $id !== ''
            )
        ));

        $statusPresetsInput = is_array($data['status_presets'] ?? null) ? $data['status_presets'] : [];

        /** @var array<int, array{name: ?string, color: string, is_completed: bool}> $statusPresets */
        $statusPresets = array_values(array_filter(
            array_map(
                static function (array $preset): array {
                    return [
                        'name' => Arr::get($preset, 'name'),
                        'color' => Arr::get($preset, 'color', '#2563EB'),
                        'is_completed' => (bool) Arr::get($preset, 'is_completed', false),
                    ];
                },
                $statusPresetsInput
            ),
            static fn (array $preset): bool => filled($preset['name'])
        ));

        unset($data['member_ids'], $data['status_presets']);

        /** @var Project $project */
        $project = $this->projectService->create($data, $memberIds, $statusPresets);

        return $project;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
