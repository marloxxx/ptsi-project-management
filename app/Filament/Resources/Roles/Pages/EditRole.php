<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages;

use App\Domain\Services\RoleServiceInterface;
use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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
                ->authorize(fn (): bool => Auth::user()?->can('roles.delete') ?? false)
                ->requiresConfirmation(),
        ];
    }

    public function boot(RoleServiceInterface $roleService): void
    {
        $this->roleService = $roleService;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permissions'] = $this->record->permissions->pluck('name')->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $permissions = $data['permissions'] ?? null;
        unset($data['permissions']);

        $this->roleService->update((int) $record->getKey(), $data, $permissions);

        return $record->refresh();
    }

    protected function handleRecordDeletion(Model $record): void
    {
        $this->roleService->delete((int) $record->getKey());
    }
}
