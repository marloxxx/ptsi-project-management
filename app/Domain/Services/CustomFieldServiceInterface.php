<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\ProjectCustomField;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

interface CustomFieldServiceInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createField(int $projectId, array $data): ProjectCustomField;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateField(int $fieldId, array $data): ProjectCustomField;

    public function deleteField(int $fieldId): bool;

    /**
     * @return Collection<int, ProjectCustomField>
     */
    public function getFieldsForProject(int $projectId): Collection;

    /**
     * @return Collection<int, ProjectCustomField>
     */
    public function getActiveFieldsForProject(int $projectId): Collection;

    /**
     * @param  array<int, array<string, mixed>>  $values
     */
    public function syncCustomValuesForTicket(Ticket $ticket, array $values): void;

    /**
     * Generate form schema components for custom fields.
     *
     * @return array<int, \Filament\Schemas\Components\Component>
     */
    public function generateFormSchemaForProject(int $projectId): array;
}
