<?php

namespace Tests\Feature\Notifications\Circle;

use App\Enums\Role;
use App\Models\Circle;
use App\Models\User;
use App\Notifications\Circle\NewCircleAwaitingApproval;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NewCircleAwaitingApprovalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_is_emailed_through_the_queue(): void
    {
        $staff = User::factory()->create(['role' => Role::Admin]);
        $circle = Circle::factory()->create();
        $notification = new NewCircleAwaitingApproval($circle, User::factory()->create());
        Queue::fake();

        $staff->notify($notification);

        $this->assertSame(['mail'], $notification->via($staff));
        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job): bool => $job->notification instanceof NewCircleAwaitingApproval
            && $job->notification->circle->is($circle)
            && $job->notifiables->contains($staff));
    }

    public function test_tells_staff_which_circle_is_waiting_and_who_created_it_with_a_link_to_review_it(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);
        $creator = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $mail = (new NewCircleAwaitingApproval($circle, $creator))->toMail(User::factory()->create(['role' => Role::Admin]));

        $this->assertSame('A new circle is waiting for approval', $mail->subject);
        $this->assertSame([
            'Ada Lovelace has created the circle "Exchange", and it needs a staff member to approve it before anyone else can see it.',
            'Share skills and lend tools.',
        ], $mail->introLines);
        $this->assertSame('Review the circle', $mail->actionText);
        $this->assertSame(route('circles.show', $circle), $mail->actionUrl);
    }
}
