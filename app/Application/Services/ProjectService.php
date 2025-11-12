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
use Illuminate\Support\Facades\Auth;
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

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Project>
     */
    public function list(array $filters = []): Collection
    {
        return $this->projectRepository->all(Arr::get($filters, 'with', []));
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>  $memberIds
     * @param  array<int, array<string, mixed>>  $statusPresets
     */
    public function create(array $data, array $memberIds = [], array $statusPresets = []): Project
    {
        return DB::transaction(function () use ($data, $memberIds, $statusPresets) {
            /** @var Project $project */
            $project = $this->projectRepository->create($data);

            if (! empty($memberIds)) {
                $this->projectRepository->syncMembers($project, $memberIds);
            }

            $this->seedStatuses($project, $statusPresets);

            $projectWithRelations = $project->fresh(['ticketStatuses', 'members']);

            activity()
                ->performedOn($project)
                ->event('created')
                ->withProperties([
                    'member_ids' => array_values($memberIds),
                    'status_count' => $projectWithRelations?->ticketStatuses?->count(),
                ])
                ->log('Project created');

            return $projectWithRelations ?? $project;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, int>|null  $memberIds
     */
    public function update(int $projectId, array $data, ?array $memberIds = null): Project
    {
        return DB::transaction(function () use ($projectId, $data, $memberIds) {
            $project = $this->findProjectOrFail($projectId);

            $project = $this->projectRepository->update($project, $data);

            if ($memberIds !== null) {
                $this->projectRepository->syncMembers($project, $memberIds);
            }

            $projectWithRelations = $project->fresh(['ticketStatuses', 'members']);

            $properties = [
                'changed_attributes' => array_keys($data),
            ];

            if ($memberIds !== null) {
                $properties['member_ids'] = array_values($memberIds);
            }

            activity()
                ->performedOn($project)
                ->event('updated')
                ->withProperties($properties)
                ->log('Project updated');

            return $projectWithRelations ?? $project;
        });
    }

    public function delete(int $projectId): bool
    {
        return DB::transaction(function () use ($projectId) {
            $project = $this->findProjectOrFail($projectId);

            activity()
                ->performedOn($project)
                ->event('deleted')
                ->log('Project deleted');

            return $this->projectRepository->delete($project);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addStatus(int $projectId, array $data): TicketStatus
    {
        $project = $this->findProjectOrFail($projectId);

        $statusData = array_merge([
            'color' => '#2563EB',
            'is_completed' => false,
            'sort_order' => $project->ticketStatuses()->max('sort_order') + 1,
        ], $data);

        $status = $this->ticketStatusRepository->create($project, $statusData);

        activity()
            ->performedOn($status)
            ->event('created')
            ->withProperties([
                'project_id' => $project->id,
                'status_id' => $status->id,
            ])
            ->log('Project status created');

        return $status;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStatus(int $statusId, array $data): TicketStatus
    {
        $status = $this->findStatusOrFail($statusId);

        $updatedStatus = $this->ticketStatusRepository->update($status, $data);

        activity()
            ->performedOn($updatedStatus)
            ->event('updated')
            ->withProperties([
                'project_id' => $updatedStatus->project_id,
                'status_id' => $updatedStatus->id,
                'changed_attributes' => array_keys($data),
            ])
            ->log('Project status updated');

        return $updatedStatus;
    }

    public function removeStatus(int $statusId): bool
    {
        $status = $this->findStatusOrFail($statusId);

        if ($status->tickets()->exists()) {
            throw new RuntimeException('Cannot delete status while tickets still reference it.');
        }

        activity()
            ->performedOn($status)
            ->event('deleted')
            ->withProperties([
                'project_id' => $status->project_id,
                'status_id' => $status->id,
            ])
            ->log('Project status deleted');

        return $this->ticketStatusRepository->delete($status);
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorderStatuses(int $projectId, array $orderedIds): void
    {
        $project = $this->findProjectOrFail($projectId);

        $this->ticketStatusRepository->reorder($project, array_values($orderedIds));

        activity()
            ->performedOn($project)
            ->event('updated')
            ->withProperties([
                'status_order' => array_values($orderedIds),
            ])
            ->log('Project statuses reordered');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addEpic(int $projectId, array $data): Epic
    {
        $payload = array_merge($data, ['project_id' => $projectId]);

        $epic = $this->epicRepository->create($payload);

        activity()
            ->performedOn($epic)
            ->event('created')
            ->withProperties([
                'project_id' => $projectId,
                'epic_id' => $epic->id,
            ])
            ->log('Project epic created');

        return $epic;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEpic(int $epicId, array $data): Epic
    {
        $epic = $this->findEpicOrFail($epicId);

        $updatedEpic = $this->epicRepository->update($epic, $data);

        activity()
            ->performedOn($updatedEpic)
            ->event('updated')
            ->withProperties([
                'project_id' => $updatedEpic->project_id,
                'epic_id' => $updatedEpic->id,
                'changed_attributes' => array_keys($data),
            ])
            ->log('Project epic updated');

        return $updatedEpic;
    }

    public function deleteEpic(int $epicId): bool
    {
        $epic = $this->findEpicOrFail($epicId);

        if ($epic->tickets()->exists()) {
            throw new RuntimeException('Cannot delete epic while tickets still reference it.');
        }

        activity()
            ->performedOn($epic)
            ->event('deleted')
            ->withProperties([
                'project_id' => $epic->project_id,
                'epic_id' => $epic->id,
            ])
            ->log('Project epic deleted');

        return $this->epicRepository->delete($epic);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addNote(int $projectId, array $data): ProjectNote
    {
        $payload = array_merge($data, [
            'project_id' => $projectId,
        ]);

        if (! array_key_exists('created_by', $payload) || $payload['created_by'] === null) {
            $payload['created_by'] = Auth::id();
        }

        if (! array_key_exists('note_date', $payload) || $payload['note_date'] === null) {
            $payload['note_date'] = now();
        }

        $note = $this->projectNoteRepository->create($payload);

        activity()
            ->performedOn($note)
            ->event('created')
            ->withProperties([
                'project_id' => $projectId,
                'note_id' => $note->id,
            ])
            ->log('Project note created');

        return $note;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateNote(int $noteId, array $data): ProjectNote
    {
        $note = $this->findProjectNoteOrFail($noteId);

        $updatedNote = $this->projectNoteRepository->update($note, $data);

        activity()
            ->performedOn($updatedNote)
            ->event('updated')
            ->withProperties([
                'project_id' => $updatedNote->project_id,
                'note_id' => $updatedNote->id,
                'changed_attributes' => array_keys($data),
            ])
            ->log('Project note updated');

        return $updatedNote;
    }

    public function deleteNote(int $noteId): bool
    {
        $note = $this->findProjectNoteOrFail($noteId);

        activity()
            ->performedOn($note)
            ->event('deleted')
            ->withProperties([
                'project_id' => $note->project_id,
                'note_id' => $note->id,
            ])
            ->log('Project note deleted');

        return $this->projectNoteRepository->delete($note);
    }

    public function generateExternalAccess(int $projectId, ?string $label = null): ExternalAccessToken
    {
        $project = $this->findProjectOrFail($projectId);

        return DB::transaction(function () use ($project, $label) {
            $replacedTokenId = null;

            if ($existing = $this->externalAccessTokenRepository->findActiveForProject($project->id)) {
                $replacedTokenId = $existing->id;
                $this->externalAccessTokenRepository->delete($existing);
            }

            $token = $this->issueExternalToken($project->id, $label);

            activity()
                ->performedOn($project)
                ->event('external_access_generated')
                ->withProperties([
                    'token_id' => $token->id,
                    'label' => $token->name,
                    'replaced_token_id' => $replacedTokenId,
                ])
                ->log('Project external access token generated');

            return $token;
        });
    }

    public function rotateExternalAccess(int $tokenId): ExternalAccessToken
    {
        $token = $this->findExternalTokenOrFail($tokenId);
        $project = $this->findProjectOrFail($token->project_id);

        return DB::transaction(function () use ($token, $project) {
            $this->externalAccessTokenRepository->delete($token);

            $newToken = $this->issueExternalToken($token->project_id, $token->name);

            activity()
                ->performedOn($project)
                ->event('external_access_rotated')
                ->withProperties([
                    'token_id' => $newToken->id,
                    'previous_token_id' => $token->id,
                    'label' => $newToken->name,
                ])
                ->log('Project external access token rotated');

            return $newToken;
        });
    }

    public function deactivateExternalAccess(int $tokenId): bool
    {
        $token = $this->findExternalTokenOrFail($tokenId);

        $project = $this->findProjectOrFail($token->project_id);

        $updatedToken = $this->externalAccessTokenRepository->update($token, [
            'is_active' => false,
        ]);

        activity()
            ->performedOn($project)
            ->event('external_access_deactivated')
            ->withProperties([
                'token_id' => $updatedToken->id,
            ])
            ->log('Project external access token deactivated');

        return true;
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

    /**
     * @param  array<int, array<string, mixed>>  $statusPresets
     */
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

    /**
     * @return array<int, array{name: string, color: string, is_completed: bool}>
     */
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
