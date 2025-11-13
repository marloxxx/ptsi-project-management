<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Filament\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketCommentAdded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly Ticket $ticket,
        private readonly TicketComment $comment,
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
        $author = $this->comment->author->name ?? __('Someone');
        $ticketName = $this->ticket->name;

        return (new MailMessage)
            ->subject(__('New Comment on :ticket', ['ticket' => $ticketName]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name ?? __('there')]))
            ->line(__(':author added a new comment on ticket ":ticket".', [
                'author' => $author,
                'ticket' => $ticketName,
            ]))
            ->line(Str::limit((string) $this->comment->body, 160))
            ->action(__('View Ticket'), url(TicketResource::getUrl('view', ['record' => $this->ticket])))
            ->line(__('Thank you for staying up to date!'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_comment_added',
            'ticket_id' => $this->ticket->getKey(),
            'ticket_uuid' => $this->ticket->uuid,
            'ticket_name' => $this->ticket->name,
            'project_id' => $this->ticket->project_id,
            'project_name' => $this->ticket->project?->name,
            'comment_id' => $this->comment->getKey(),
            'comment_body' => $this->comment->body,
            'comment_author_id' => $this->comment->user_id,
            'comment_author_name' => $this->comment->author?->name,
            'is_internal' => (bool) $this->comment->is_internal,
            'url' => TicketResource::getUrl('view', ['record' => $this->ticket]),
        ];
    }
}
