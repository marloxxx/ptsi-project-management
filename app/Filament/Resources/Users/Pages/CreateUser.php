<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Services\UserServiceInterface;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected UserServiceInterface $userService;

    public function boot(UserServiceInterface $userService): void
    {
        $this->userService = $userService;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $roles = $data['roles'] ?? [];
        unset($data['roles']);

        return $this->userService->create($data, $roles ?: null);
    }
}
