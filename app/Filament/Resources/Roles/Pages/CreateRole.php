<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Domain\Services\RoleServiceInterface;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected RoleServiceInterface $roleService;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function boot(RoleServiceInterface $roleService): void
    {
        $this->roleService = $roleService;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        return $this->roleService->create($data, $permissions);
    }
}
