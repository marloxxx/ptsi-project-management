<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Services\UserServiceInterface;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use STS\FilamentImpersonate\Actions\Impersonate;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected UserServiceInterface $userService;

    public function boot(UserServiceInterface $userService): void
    {
        $this->userService = $userService;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof User) {
            throw new \InvalidArgumentException('Expected User model.');
        }

        $roles = isset($data['roles']) ? array_map('strval', (array) $data['roles']) : null;
        unset($data['roles']);

        $this->userService->update((int) $record->getKey(), $data, $roles);

        return $record->refresh();
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getUserRecord();

        return [
            ViewAction::make()
                ->authorize(fn (): bool => self::currentUser()?->can('view', $record) ?? false),
            Impersonate::make()
                ->record($record)
                ->visible(fn (): bool => self::currentUser()?->can('users.view') ?? false),
            DeleteAction::make()
                ->authorize(fn (): bool => self::currentUser()?->can('delete', $record) ?? false)
                ->requiresConfirmation()
                ->action(fn () => $this->userService->delete((int) $record->getKey())),
            ForceDeleteAction::make()
                ->authorize(fn (): bool => self::currentUser()?->can('users.force-delete') ?? false)
                ->requiresConfirmation()
                ->action(fn () => $this->userService->forceDelete((int) $record->getKey())),
            RestoreAction::make()
                ->authorize(fn (): bool => self::currentUser()?->can('users.restore') ?? false)
                ->visible(fn () => $record->trashed())
                ->action(fn () => $this->userService->restore((int) $record->getKey())),
        ];
    }

    private function getUserRecord(): User
    {
        /** @var User $record */
        $record = $this->getRecord();

        return $record;
    }
}
