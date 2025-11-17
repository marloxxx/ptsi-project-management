<?php

declare(strict_types=1);

namespace App\Application\Policies;

use App\Models\SavedFilter;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SavedFilterPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        // Users can view saved filters they have access to
        return true;
    }

    public function view(User $user, SavedFilter $savedFilter): bool
    {
        // User can view if:
        // 1. They own it (private)
        // 2. It's public
        // 3. It's team/project visibility and user is member of the project
        if ($savedFilter->visibility === 'public') {
            return true;
        }

        if ($savedFilter->owner_type === User::class && $savedFilter->owner_id === $user->id) {
            return true;
        }

        if ($savedFilter->visibility === 'team' || $savedFilter->visibility === 'project') {
            if ($savedFilter->project_id) {
                return $savedFilter->project->members()->where('user_id', $user->id)->exists();
            }
        }

        return false;
    }

    public function create(User $user): bool
    {
        // Any authenticated user can create saved filters
        return true;
    }

    public function update(User $user, SavedFilter $savedFilter): bool
    {
        // User can update if they own it or have admin permissions
        if ($savedFilter->owner_type === User::class && $savedFilter->owner_id === $user->id) {
            return true;
        }

        return $user->can('saved-filters.update');
    }

    public function delete(User $user, SavedFilter $savedFilter): bool
    {
        // User can delete if they own it or have admin permissions
        if ($savedFilter->owner_type === User::class && $savedFilter->owner_id === $user->id) {
            return true;
        }

        return $user->can('saved-filters.delete');
    }
}
