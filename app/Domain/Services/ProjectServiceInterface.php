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
    public function list(array $filters = []): Collection;

    public function create(array $data, array $memberIds = [], array $statusPresets = []): Project;

    public function update(int $projectId, array $data, ?array $memberIds = null): Project;

    public function delete(int $projectId): bool;

    public function addStatus(int $projectId, array $data): TicketStatus;

    public function updateStatus(int $statusId, array $data): TicketStatus;

    public function removeStatus(int $statusId): bool;

    public function reorderStatuses(int $projectId, array $orderedIds): void;

    public function addEpic(int $projectId, array $data): Epic;

    public function updateEpic(int $epicId, array $data): Epic;

    public function deleteEpic(int $epicId): bool;

    public function addNote(int $projectId, array $data): ProjectNote;

    public function updateNote(int $noteId, array $data): ProjectNote;

    public function deleteNote(int $noteId): bool;

    public function generateExternalAccess(int $projectId, ?string $label = null): ExternalAccessToken;

    public function rotateExternalAccess(int $tokenId): ExternalAccessToken;

    public function deactivateExternalAccess(int $tokenId): bool;
}

