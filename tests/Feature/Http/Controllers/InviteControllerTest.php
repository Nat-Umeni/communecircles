<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Models\Circle;
use App\Models\User;
use App\Notifications\Circle\InviteReceived;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InviteControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_invites_someone_by_email_and_goes_back_with_a_message(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->ownerOf($circle);
        $invitee = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com']);

        $response = $this->actingAs($owner)->from(route('circles.show', $circle))
            ->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertRedirect(route('circles.show', $circle));
        $response->assertInertiaFlash('status', 'Invite sent to Ada Lovelace.');
        $invite = $circle->approvals()->where('type', ApprovalType::Invite)->sole();
        $this->assertSame(ApprovalStatus::Pending, $invite->status);
        $this->assertTrue($invite->user->is($invitee));
        $this->assertTrue($invite->requester->is($owner));
    }

    public function test_emails_only_the_person_invited(): void
    {
        Notification::fake();
        $circle = Circle::factory()->create();
        $owner = $this->ownerOf($circle);
        $invitee = User::factory()->create(['email' => 'ada@example.com']);

        $this->actingAs($owner)->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        Notification::assertSentTo($invitee, InviteReceived::class, fn (InviteReceived $notification): bool => $notification->circle->is($circle)
            && $notification->inviter->is($owner));
        Notification::assertNotSentTo($owner, InviteReceived::class);
    }

    public function test_guests_are_sent_to_log_in(): void
    {
        $response = $this->post(route('circles.invites.store', Circle::factory()->create()), ['email' => 'ada@example.com']);

        $response->assertRedirect(route('login'));
    }

    private function ownerOf(Circle $circle): User
    {
        $owner = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);

        return $owner;
    }
}
