<?php

namespace App\Notifications;

use App\Models\ProjectInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly ProjectInvitation $invitation) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Join '.$this->invitation->project->name.' on Hookroute')
            ->greeting('You have been invited')
            ->line($this->invitation->inviter->name.' invited you to the project '.$this->invitation->project->name.'.')
            ->action('Accept invitation', route('invitations.show', $this->invitation->token))
            ->line('This invitation expires '.$this->invitation->expires_at->diffForHumans().'.');
    }
}
