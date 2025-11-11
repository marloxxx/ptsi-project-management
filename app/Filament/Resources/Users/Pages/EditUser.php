<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Services\UserServiceInterface;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use STS\FilamentImpersonate\Actions\Impersonate;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected UserServiceInterface $userService;

    public function boot(UserServiceInterface $userService): void
    {
        $this->userService = $userService;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $roles = $data['roles'] ?? null;
        unset($data['roles']);

        $this->userService->update((int) $record->getKey(), $data, $roles);

        return $record->refresh();
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('view', $this->getRecord()) ?? false),
            Impersonate::make()
                ->record($this->getRecord())
                ->visible(fn (): bool => Auth::user()?->can('users.view') ?? false),
            DeleteAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('delete', $this->getRecord()) ?? false)
                ->requiresConfirmation()
                ->action(fn () => $this->userService->delete((int) $this->getRecord()->getKey())),
            ForceDeleteAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('users.force-delete') ?? false)
                ->requiresConfirmation()
                ->action(fn () => $this->userService->forceDelete((int) $this->getRecord()->getKey())),
            RestoreAction::make()
                ->authorize(fn (): bool => Auth::user()?->can('users.restore') ?? false)
                ->visible(fn (): bool => $this->getRecord()->trashed())
                ->action(fn () => $this->userService->restore((int) $this->getRecord()->getKey())),
        ];
    }
}
