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

class ProjectMemberAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Project $project,
        private readonly ?User $assignedBy = null,
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
        $assignedBy = $this->assignedBy->name ?? __('the project team');

        return (new MailMessage)
            ->subject(__('You were added to :project', ['project' => $this->project->name]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('there')]))
            ->line(__('You have been added to the project ":project" by :assignedBy.', [
                'project' => $this->project->name,
                'assignedBy' => $assignedBy,
            ]))
            ->action(__('Open Project'), url(ProjectResource::getUrl('view', ['record' => $this->project])))
            ->line(__('Let the team know if you have any questions!'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_member_assigned',
            'project_id' => $this->project->getKey(),
            'project_name' => $this->project->name,
            'assigned_by_id' => $this->assignedBy?->getKey(),
            'assigned_by_name' => $this->assignedBy?->name,
            'url' => ProjectResource::getUrl('view', ['record' => $this->project]),
        ];
    }
}
