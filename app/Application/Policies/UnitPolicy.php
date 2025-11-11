<?php

declare(strict_types=1);

namespace App\Application\Policies;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UnitPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('units.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('units.view');
    }

    public function create(User $user): bool
    {
        return $user->can('units.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('units.update');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('units.delete');
    }

    public function restore(User $user, Unit $unit): bool
    {
        return $user->can('units.restore');
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return $user->can('units.force-delete');
    }
}
