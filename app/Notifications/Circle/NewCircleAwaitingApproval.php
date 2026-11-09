<?php

namespace App\Notifications\Circle;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells staff that a circle is waiting for their approval: newly created, or changed after they turned it down.
 * Sent through the queue.
 */
class NewCircleAwaitingApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Circle $circle,
        /** Whoever asked for approval: the creator, or whoever changed the circle after it was turned down. */
        public readonly User $requester,
        /** Whether it comes back after staff turned it down, rather than being new. */
        public readonly bool $resubmitted = false,
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
        $name = "{$this->requester->first_name} {$this->requester->last_name}";

        return (new MailMessage)
            ->subject($this->resubmitted ? 'A circle is waiting for approval again' : 'A new circle is waiting for approval')
            ->line($this->resubmitted
                ? "{$name} has changed the circle \"{$this->circle->name}\" since it was turned down, and it needs a staff member to look at it again."
                : "{$name} has created the circle \"{$this->circle->name}\", and it needs a staff member to approve it before anyone else can see it.")
            ->line($this->circle->description)
            ->action('Review circles waiting for approval', route('approvals.index'));
    }
}
