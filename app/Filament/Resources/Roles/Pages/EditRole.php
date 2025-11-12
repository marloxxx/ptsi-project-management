<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Domain\Services\RoleServiceInterface;
use App\Filament\Resources\Roles\RoleResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected RoleServiceInterface $roleService;

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->authorize(fn (): bool => self::currentUser()?->can('roles.delete') ?? false)
                ->requiresConfirmation(),
        ];
    }

    public function boot(RoleServiceInterface $roleService): void
    {
        $this->roleService = $roleService;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Role $role */
        $role = $this->record;

        $data['permissions'] = $role->permissions->pluck('name')->toArray();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Role $record */
        $permissions = isset($data['permissions']) ? array_map('strval', (array) $data['permissions']) : null;
        unset($data['permissions']);

        $this->roleService->update((int) $record->getKey(), $data, $permissions);

        return $record->refresh();
    }

    protected function handleRecordDeletion(Model $record): void
    {
        /** @var Role $record */
        $this->roleService->delete((int) $record->getKey());
    }

    /**
     * Get the current user.
     */
    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
