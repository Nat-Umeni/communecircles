<?php

namespace Tests\Feature\Notifications\Circle;

use App\Models\Circle;
use App\Models\User;
use App\Notifications\Circle\JoinRequestReceived;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class JoinRequestReceivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_is_emailed_through_the_queue(): void
    {
        $owner = User::factory()->create();
        $circle = Circle::factory()->create();
        $notification = new JoinRequestReceived($circle, User::factory()->create());
        Queue::fake();

        $owner->notify($notification);

        $this->assertSame(['mail'], $notification->via($owner));
        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof JoinRequestReceived
            && $job->notification->circle->is($circle)
            && $job->notifiables->contains($owner));
    }

    public function test_tells_the_circles_owners_and_admins_who_asked_to_join_with_a_link_to_the_circle(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange']);
        $asker = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $mail = (new JoinRequestReceived($circle, $asker))->toMail(User::factory()->create());

        $this->assertSame('Someone has asked to join Exchange', $mail->subject);
        $this->assertSame(['Ada Lovelace has asked to join "Exchange". As one of its owners or admins, you can accept or decline the request.'], $mail->introLines);
        $this->assertSame('Review the request', $mail->actionText);
        $this->assertSame(route('circles.show', $circle), $mail->actionUrl);
    }
}
