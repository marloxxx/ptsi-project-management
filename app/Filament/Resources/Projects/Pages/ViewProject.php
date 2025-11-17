<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Domain\Services\ProjectServiceInterface;
use App\Filament\Pages\ProjectTimeline;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Widgets\SprintBurndownWidget;
use App\Filament\Widgets\SprintVelocityWidget;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
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
                Action::make('generateExternalAccess')
                    ->label('Generate External Access')
                    ->icon('heroicon-o-key')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => static::getResource()::canEdit($this->record))
                    ->action(function (Model $record): void {
                        /** @var Project $record */
                        $token = $this->projectService->generateExternalAccess((int) $record->getKey(), 'External Access');

                        $loginUrl = route('external.login', ['token' => $token->access_token]);
                        $dashboardUrl = route('external.dashboard', ['token' => $token->access_token]);
                        $plainPassword = (string) ($token->getAttribute('plain_password') ?? '');

                        $body = sprintf(
                            "Login URL: %s\nDashboard URL: %s\nPassword: %s",
                            $loginUrl,
                            $dashboardUrl,
                            $plainPassword
                        );

                        Notification::make()
                            ->title('External access generated')
                            ->body($body)
                            ->success()
                            ->send();
                    }),
                Action::make('rotateExternalAccess')
                    ->label('Rotate External Access')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->visible(function (): bool {
                        /** @var Project $project */
                        $project = $this->record;

                        return static::getResource()::canEdit($project) && $project->externalAccessToken()->exists();
                    })
                    ->action(function (Model $record): void {
                        /** @var Project $record */
                        $existing = $record->externalAccessToken()->first();

                        if (! $existing) {
                            Notification::make()
                                ->title('No active external access to rotate')
                                ->warning()
                                ->send();

                            return;
                        }

                        $token = $this->projectService->rotateExternalAccess((int) $existing->getKey());

                        $loginUrl = route('external.login', ['token' => $token->access_token]);
                        $dashboardUrl = route('external.dashboard', ['token' => $token->access_token]);
                        $plainPassword = (string) ($token->getAttribute('plain_password') ?? '');

                        $body = sprintf(
                            "New Login URL: %s\nNew Dashboard URL: %s\nNew Password: %s",
                            $loginUrl,
                            $dashboardUrl,
                            $plainPassword
                        );

                        Notification::make()
                            ->title('External access rotated')
                            ->body($body)
                            ->success()
                            ->send();
                    }),
                Action::make('deactivateExternalAccess')
                    ->label('Deactivate External Access')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(function (): bool {
                        /** @var Project $project */
                        $project = $this->record;

                        return static::getResource()::canEdit($project) && $project->externalAccessToken()->exists();
                    })
                    ->action(function (Model $record): void {
                        /** @var Project $record */
                        $existing = $record->externalAccessToken()->first();

                        if (! $existing) {
                            Notification::make()
                                ->title('No active external access to deactivate')
                                ->warning()
                                ->send();

                            return;
                        }

                        $this->projectService->deactivateExternalAccess((int) $existing->getKey());

                        Notification::make()
                            ->title('External access deactivated')
                            ->success()
                            ->send();
                    }),
            ])->button()->label('External Access'),
            Action::make('viewTimeline')
                ->label('View Timeline')
                ->icon('heroicon-o-calendar-days')
                ->color('info')
                ->url(function (): string {
                    /** @var Project $project */
                    $project = $this->record;

                    return ProjectTimeline::getUrl(['project' => $project->getKey()]);
                })
                ->openUrlInNewTab(),
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

    /**
     * @return array<int, string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            SprintVelocityWidget::class,
            SprintBurndownWidget::class,
        ];
    }
}
