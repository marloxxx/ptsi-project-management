<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Repositories\SavedFilterRepositoryInterface;
use App\Domain\Services\SavedFilterServiceInterface;
use App\Models\SavedFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SavedFilterService implements SavedFilterServiceInterface
{
    public function __construct(
        protected SavedFilterRepositoryInterface $savedFilterRepository
    ) {}

    /**
     * @return Collection<int, SavedFilter>
     */
    public function list(?int $projectId = null): Collection
    {
        $userId = Auth::id();
        if (! $userId) {
            return new Collection;
        }

        return $this->savedFilterRepository->accessibleForUser((int) $userId, $projectId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SavedFilter
    {
        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            if (! $user) {
                throw new \RuntimeException('User must be authenticated to create saved filters');
            }

            // Set owner if not provided
            if (! isset($data['owner_type']) || ! isset($data['owner_id'])) {
                $data['owner_type'] = \App\Models\User::class;
                $data['owner_id'] = $user->id;
            }

            // Default visibility to private if not set
            if (! isset($data['visibility'])) {
                $data['visibility'] = 'private';
            }

            $savedFilter = $this->savedFilterRepository->create($data);

            activity()
                ->performedOn($savedFilter)
                ->causedBy($user)
                ->log('Saved filter created');

            return $savedFilter;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $filterId, array $data): SavedFilter
    {
        return DB::transaction(function () use ($filterId, $data) {
            $savedFilter = $this->savedFilterRepository->find($filterId);
            if (! $savedFilter) {
                throw new \RuntimeException("Saved filter with ID {$filterId} not found");
            }

            $user = Auth::user();
            if (! $user) {
                throw new \RuntimeException('User must be authenticated to update saved filters');
            }

            $savedFilter = $this->savedFilterRepository->update($savedFilter, $data);

            activity()
                ->performedOn($savedFilter)
                ->causedBy($user)
                ->log('Saved filter updated');

            return $savedFilter;
        });
    }

    public function delete(int $filterId): bool
    {
        return DB::transaction(function () use ($filterId) {
            $savedFilter = $this->savedFilterRepository->find($filterId);
            if (! $savedFilter) {
                throw new \RuntimeException("Saved filter with ID {$filterId} not found");
            }

            $user = Auth::user();
            if (! $user) {
                throw new \RuntimeException('User must be authenticated to delete saved filters');
            }

            $result = $this->savedFilterRepository->delete($savedFilter);

            activity()
                ->performedOn($savedFilter)
                ->causedBy($user)
                ->log('Saved filter deleted');

            return $result;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getFilterQuery(int $filterId): array
    {
        $savedFilter = $this->savedFilterRepository->find($filterId);
        if (! $savedFilter) {
            throw new \RuntimeException("Saved filter with ID {$filterId} not found");
        }

        $query = $savedFilter->query;
        if (! is_array($query)) {
            return [];
        }

        /** @var array<string, mixed> $query */
        return $query;
    }
}
