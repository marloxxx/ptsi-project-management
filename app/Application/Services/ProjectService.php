<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\EpicRepositoryInterface;
use App\Domain\Repositories\ExternalAccessTokenRepositoryInterface;
use App\Domain\Repositories\ProjectNoteRepositoryInterface;
use App\Domain\Repositories\ProjectRepositoryInterface;
use App\Domain\Repositories\TicketStatusRepositoryInterface;
use App\Domain\Services\ProjectServiceInterface;
use App\Models\Epic;
use App\Models\ExternalAccessToken;
use App\Models\Project;
use App\Models\ProjectNote;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ProjectService implements ProjectServiceInterface
{
    public function __construct(
        protected ProjectRepositoryInterface $projectRepository,
        protected TicketStatusRepositoryInterface $ticketStatusRepository,
        protected EpicRepositoryInterface $epicRepository,
        protected ProjectNoteRepositoryInterface $projectNoteRepository,
        protected ExternalAccessTokenRepositoryInterface $externalAccessTokenRepository
    ) {}

    public function list(array $filters = []): Collection
    {
        return $this->projectRepository->all(Arr::get($filters, 'with', []));
    }

    public function create(array $data, array $memberIds = [], array $statusPresets = []): Project
    {
        return DB::transaction(function () use ($data, $memberIds, $statusPresets) {
            /** @var Project $project */
            $project = $this->projectRepository->create($data);

            if (! empty($memberIds)) {
                $this->projectRepository->syncMembers($project, $memberIds);
            }

            $this->seedStatuses($project, $statusPresets);

            return $project->fresh(['ticketStatuses', 'members']);
        });
    }

    public function update(int $projectId, array $data, ?array $memberIds = null): Project
    {
        return DB::transaction(function () use ($projectId, $data, $memberIds) {
            $project = $this->findProjectOrFail($projectId);

            $project = $this->projectRepository->update($project, $data);

            if ($memberIds !== null) {
                $this->projectRepository->syncMembers($project, $memberIds);
            }

            return $project->fresh(['ticketStatuses', 'members']);
        });
    }

    public function delete(int $projectId): bool
    {
        return DB::transaction(function () use ($projectId) {
            $project = $this->findProjectOrFail($projectId);

            return $this->projectRepository->delete($project);
        });
    }

    public function addStatus(int $projectId, array $data): TicketStatus
    {
        $project = $this->findProjectOrFail($projectId);

        $statusData = array_merge([
            'color' => '#2563EB',
            'is_completed' => false,
            'sort_order' => $project->ticketStatuses()->max('sort_order') + 1,
        ], $data);

        return $this->ticketStatusRepository->create($project, $statusData);
    }

    public function updateStatus(int $statusId, array $data): TicketStatus
    {
        $status = $this->findStatusOrFail($statusId);

        return $this->ticketStatusRepository->update($status, $data);
    }

    public function removeStatus(int $statusId): bool
    {
        $status = $this->findStatusOrFail($statusId);

        if ($status->tickets()->exists()) {
            throw new RuntimeException('Cannot delete status while tickets still reference it.');
        }

        return $this->ticketStatusRepository->delete($status);
    }

    public function reorderStatuses(int $projectId, array $orderedIds): void
    {
        $project = $this->findProjectOrFail($projectId);

        $this->ticketStatusRepository->reorder($project, array_values($orderedIds));
    }

    public function addEpic(int $projectId, array $data): Epic
    {
        $payload = array_merge($data, ['project_id' => $projectId]);

        return $this->epicRepository->create($payload);
    }

    public function updateEpic(int $epicId, array $data): Epic
    {
        $epic = $this->findEpicOrFail($epicId);

        return $this->epicRepository->update($epic, $data);
    }

    public function deleteEpic(int $epicId): bool
    {
        $epic = $this->findEpicOrFail($epicId);

        if ($epic->tickets()->exists()) {
            throw new RuntimeException('Cannot delete epic while tickets still reference it.');
        }

        return $this->epicRepository->delete($epic);
    }

    public function addNote(int $projectId, array $data): ProjectNote
    {
        $payload = array_merge($data, [
            'project_id' => $projectId,
        ]);

        return $this->projectNoteRepository->create($payload);
    }

    public function updateNote(int $noteId, array $data): ProjectNote
    {
        $note = $this->findProjectNoteOrFail($noteId);

        return $this->projectNoteRepository->update($note, $data);
    }

    public function deleteNote(int $noteId): bool
    {
        $note = $this->findProjectNoteOrFail($noteId);

        return $this->projectNoteRepository->delete($note);
    }

    public function generateExternalAccess(int $projectId, ?string $label = null): ExternalAccessToken
    {
        $project = $this->findProjectOrFail($projectId);

        return DB::transaction(function () use ($project, $label) {
            if ($existing = $this->externalAccessTokenRepository->findActiveForProject($project->id)) {
                $this->externalAccessTokenRepository->delete($existing);
            }

            return $this->issueExternalToken($project->id, $label);
        });
    }

    public function rotateExternalAccess(int $tokenId): ExternalAccessToken
    {
        $token = $this->findExternalTokenOrFail($tokenId);

        return DB::transaction(function () use ($token) {
            $this->externalAccessTokenRepository->delete($token);

            return $this->issueExternalToken($token->project_id, $token->name);
        });
    }

    public function deactivateExternalAccess(int $tokenId): bool
    {
        $token = $this->findExternalTokenOrFail($tokenId);

        return (bool) $this->externalAccessTokenRepository->update($token, [
            'is_active' => false,
        ]);
    }

    protected function findProjectOrFail(int $projectId): Project
    {
        $project = $this->projectRepository->find($projectId);

        if (! $project) {
            throw new RuntimeException('Project not found.');
        }

        return $project;
    }

    protected function findStatusOrFail(int $statusId): TicketStatus
    {
        $status = $this->ticketStatusRepository->find($statusId);

        if (! $status) {
            throw new RuntimeException('Ticket status not found.');
        }

        return $status;
    }

    protected function findEpicOrFail(int $epicId): Epic
    {
        $epic = $this->epicRepository->find($epicId);

        if (! $epic) {
            throw new RuntimeException('Epic not found.');
        }

        return $epic;
    }

    protected function findProjectNoteOrFail(int $noteId): ProjectNote
    {
        $note = $this->projectNoteRepository->find($noteId);

        if (! $note) {
            throw new RuntimeException('Project note not found.');
        }

        return $note;
    }

    protected function findExternalTokenOrFail(int $tokenId): ExternalAccessToken
    {
        $token = $this->externalAccessTokenRepository->find($tokenId);

        if (! $token) {
            throw new RuntimeException('External access token not found.');
        }

        return $token;
    }

    protected function seedStatuses(Project $project, array $statusPresets): void
    {
        $presets = collect($statusPresets ?: $this->defaultStatuses());

        $presets->values()->each(function (array $status, int $index) use ($project): void {
            $this->ticketStatusRepository->create($project, [
                'name' => Arr::get($status, 'name'),
                'color' => Arr::get($status, 'color', '#2563EB'),
                'is_completed' => (bool) Arr::get($status, 'is_completed', false),
                'sort_order' => $index,
            ]);
        });
    }

    protected function issueExternalToken(int $projectId, ?string $label = null): ExternalAccessToken
    {
        $plainPassword = Str::random(12);

        $token = $this->externalAccessTokenRepository->create([
            'project_id' => $projectId,
            'name' => $label,
            'access_token' => Str::uuid()->toString(),
            'password' => Hash::make($plainPassword),
            'is_active' => true,
        ]);

        // expose generated password for the caller (not persisted)
        $token->setAttribute('plain_password', $plainPassword);

        return $token;
    }

    protected function defaultStatuses(): array
    {
        return [
            ['name' => 'Backlog', 'color' => '#64748B', 'is_completed' => false],
            ['name' => 'In Progress', 'color' => '#2563EB', 'is_completed' => false],
            ['name' => 'Review', 'color' => '#F59E0B', 'is_completed' => false],
            ['name' => 'Done', 'color' => '#16A34A', 'is_completed' => true],
        ];
    }
}

