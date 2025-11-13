<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectMemberRemoved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly ?User $removedBy = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $removedBy = $this->removedBy->name ?? __('the project team');

        return (new MailMessage)
            ->subject(__('You were removed from :project', ['project' => $this->project->name]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('there')]))
            ->line(__('You are no longer a member of the project ":project".', [
                'project' => $this->project->name,
            ]))
            ->line(__('This change was made by :removedBy.', ['removedBy' => $removedBy]))
            ->line(__('If you believe this was a mistake, please reach out to the project owner.'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_member_removed',
            'project_id' => $this->project->getKey(),
            'project_name' => $this->project->name,
            'removed_by_id' => $this->removedBy?->getKey(),
            'removed_by_name' => $this->removedBy?->name,
            'url' => ProjectResource::getUrl('view', ['record' => $this->project]),
        ];
    }
}
