<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Domain\Services\ProjectServiceInterface;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Model;

class ViewProject extends ViewRecord
{
    protected static string $resource = ProjectResource::class;

    protected ProjectServiceInterface $projectService;

    public function boot(ProjectServiceInterface $projectService): void
    {
        $this->projectService = $projectService;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                EditAction::make()
                    ->visible(fn (): bool => static::getResource()::canEdit($this->record)),
                DeleteAction::make()
                    ->visible(fn (): bool => static::getResource()::canDelete($this->record))
                    ->requiresConfirmation()
                    ->action(function (Model $record): void {
                        /** @var Project $record */
                        $this->projectService->delete((int) $record->getKey());
                        $this->redirect(static::getResource()::getUrl('index'));
                    }),
            ])->button()->label('Actions'),
        ];
    }
}
