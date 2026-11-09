<?php

namespace App\Notifications\Circle;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells someone that a circle's owner or admin has invited them to join it; they accept or decline on the circle's
 * page. Sent through the queue.
 */
class InviteReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Circle $circle,
        /** The owner or admin who sent the invite. */
        public readonly User $inviter,
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
        return (new MailMessage)
            ->subject("You've been invited to join {$this->circle->name}")
            ->line("{$this->inviter->first_name} {$this->inviter->last_name} has invited you to join \"{$this->circle->name}\".")
            ->line($this->circle->description)
            ->action('See the invite', route('circles.show', $this->circle));
    }
}
