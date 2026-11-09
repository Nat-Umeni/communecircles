<?php

namespace App\Notifications\Circle;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a circle's owners and admins that someone has asked to join it. Staff don't get this: join requests are the
 * circle's own business. Sent through the queue.
 */
class JoinRequestReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Circle $circle,
        /** The person asking to join. */
        public readonly User $asker,
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
        // TODO: link to the asker's profile too once profiles exist, so the owners and admins can check who's asking.
        return (new MailMessage)
            ->subject("Someone has asked to join {$this->circle->name}")
            ->line("{$this->asker->first_name} {$this->asker->last_name} has asked to join \"{$this->circle->name}\". As one of its owners or admins, you can accept or decline the request.")
            ->action('Review the request', route('circles.show', $this->circle));
    }
}
