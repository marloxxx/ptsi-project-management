<?php

declare(strict_types=1);

namespace App\Domain\Services;

use App\Models\Epic;
use App\Models\ExternalAccessToken;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Collection;

interface ProjectServiceInterface
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Project>
     */
    public function list(array $filters = []): Collection;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $memberIds
     * @param  array<int, array<string, mixed>>  $statusPresets
     */
    public function create(array $data, array $memberIds = [], array $statusPresets = []): Project;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>|null  $memberIds
     */
    public function update(int $projectId, array $data, ?array $memberIds = null): Project;

    public function delete(int $projectId): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function addStatus(int $projectId, array $data): TicketStatus;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStatus(int $statusId, array $data): TicketStatus;

    public function removeStatus(int $statusId): bool;

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorderStatuses(int $projectId, array $orderedIds): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function addEpic(int $projectId, array $data): Epic;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEpic(int $epicId, array $data): Epic;

    public function deleteEpic(int $epicId): bool;

    /**
     * @param  array<string, mixed>  $data
     */
    public function addNote(int $projectId, array $data): ProjectNote;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateNote(int $noteId, array $data): ProjectNote;

    public function deleteNote(int $noteId): bool;

    public function generateExternalAccess(int $projectId, ?string $label = null): ExternalAccessToken;

    public function rotateExternalAccess(int $tokenId): ExternalAccessToken;

    public function deactivateExternalAccess(int $tokenId): bool;
}
