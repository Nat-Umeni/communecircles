<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Enums\Role;
use App\Models\Circle;
use App\Models\User;
use App\Notifications\Circle\JoinRequestReceived;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class JoinRequestControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_asks_to_join_and_goes_back_with_a_message(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();

        $response = $this->actingAs($asker)->from(route('circles.show', $circle))->post(route('circles.join-requests.store', $circle));

        $response->assertRedirect(route('circles.show', $circle));
        $response->assertInertiaFlash('status', 'Your request to join has been sent.');
        $request = $circle->approvals()->where('type', ApprovalType::JoinRequest)->sole();
        $this->assertSame(ApprovalStatus::Pending, $request->status);
        $this->assertTrue($request->user->is($asker));
        $this->assertTrue($request->requester->is($asker));
    }

    public function test_emails_only_the_circles_owners_and_admins(): void
    {
        Notification::fake();
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $admin = $this->memberOf($circle, CircleRole::Admin);
        $member = $this->memberOf($circle, CircleRole::Member);
        $staff = User::factory()->create(['role' => Role::SuperAdmin]);
        $asker = User::factory()->create();

        $this->actingAs($asker)->post(route('circles.join-requests.store', $circle));

        Notification::assertSentTo([$owner, $admin], JoinRequestReceived::class, fn (JoinRequestReceived $notification): bool => $notification->circle->is($circle)
            && $notification->asker->is($asker));
        Notification::assertNotSentTo([$member, $staff, $asker], JoinRequestReceived::class);
    }

    public function test_someone_already_in_the_circle_cannot_ask_to_join_it(): void
    {
        Notification::fake();
        $circle = Circle::factory()->create();
        $this->memberOf($circle, CircleRole::Owner);
        $member = $this->memberOf($circle, CircleRole::Member);

        $response = $this->actingAs($member)->post(route('circles.join-requests.store', $circle));

        $response->assertForbidden();
        $this->assertFalse($circle->approvals()->where('type', ApprovalType::JoinRequest)->exists());
        Notification::assertNothingSent();
    }

    public function test_a_circle_that_is_not_approved_is_not_found(): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->actingAs(User::factory()->create())->post(route('circles.join-requests.store', $circle));

        $response->assertNotFound();
        $this->assertFalse($circle->approvals()->where('type', ApprovalType::JoinRequest)->exists());
    }

    public function test_guests_are_sent_to_log_in(): void
    {
        $response = $this->post(route('circles.join-requests.store', Circle::factory()->create()));

        $response->assertRedirect(route('login'));
    }

    /**
     * A new user who belongs to the circle with the given role.
     */
    private function memberOf(Circle $circle, CircleRole $role): User
    {
        $user = User::factory()->create();
        $circle->members()->attach($user, ['role' => $role]);

        return $user;
    }
}
