<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_is_sent_through_the_queue_with_its_token(): void
    {
        $member = User::factory()->create();
        Queue::fake();

        $member->notify(new ResetPassword('token-from-broker'));

        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof ResetPassword
            && $job->notification->token === 'token-from-broker'
            && $job->notifiables->contains($member));
    }
}
