<?php

namespace Tests\Feature\Notifications\Circle;

use App\Models\Circle;
use App\Models\User;
use App\Notifications\Circle\InviteReceived;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class InviteReceivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_is_emailed_through_the_queue(): void
    {
        $invitee = User::factory()->create();
        $circle = Circle::factory()->create();
        $notification = new InviteReceived($circle, User::factory()->create());
        Queue::fake();

        $invitee->notify($notification);

        $this->assertSame(['mail'], $notification->via($invitee));
        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof InviteReceived
            && $job->notification->circle->is($circle)
            && $job->notifiables->contains($invitee));
    }

    public function test_tells_the_person_who_invited_them_to_which_circle_with_a_link_to_accept_or_decline(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);
        $inviter = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $mail = (new InviteReceived($circle, $inviter))->toMail(User::factory()->create());

        $this->assertSame("You've been invited to join Exchange", $mail->subject);
        $this->assertSame([
            'Ada Lovelace has invited you to join "Exchange".',
            'Share skills and lend tools.',
        ], $mail->introLines);
        $this->assertSame('See the invite', $mail->actionText);
        $this->assertSame(route('circles.show', $circle), $mail->actionUrl);
    }
}
