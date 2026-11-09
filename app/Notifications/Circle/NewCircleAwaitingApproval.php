<?php

namespace App\Notifications\Circle;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells staff that a member has created a circle and it's waiting for their approval. Sent through the queue.
 */
class NewCircleAwaitingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Circle $circle,
        public readonly User $creator,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // TODO: link to the staff approvals page once it's built; staff can open a waiting circle's page meanwhile.
        return (new MailMessage)
            ->subject('A new circle is waiting for approval')
            ->line("{$this->creator->first_name} {$this->creator->last_name} has created the circle \"{$this->circle->name}\", and it needs a staff member to approve it before anyone else can see it.")
            ->line($this->circle->description)
            ->action('Review the circle', route('circles.show', $this->circle));
    }
}
