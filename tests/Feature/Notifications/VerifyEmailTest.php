<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class VerifyEmailTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_is_sent_through_the_queue(): void
    {
        $member = User::factory()->unverified()->create();
        Queue::fake();

        $member->notify(new VerifyEmail);

        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof VerifyEmail
            && $job->notifiables->contains($member));
    }
}
