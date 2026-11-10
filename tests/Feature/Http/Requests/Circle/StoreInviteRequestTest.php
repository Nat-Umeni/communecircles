<?php

namespace Tests\Feature\Http\Requests\Circle;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Enums\Role;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreInviteRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_someone_who_declined_an_earlier_invite_can_be_invited_again(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->ownerOf($circle);
        $invitee = User::factory()->create(['email' => 'ada@example.com']);
        Approval::open(ApprovalType::Invite, $circle, $invitee, $owner)->reject($invitee);

        $response = $this->actingAs($owner)->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertValid();
        $this->assertSame(ApprovalStatus::Pending, $circle->approvals()->where('type', ApprovalType::Invite)->sole()->status);
    }

    public function test_someone_whose_invite_was_cancelled_can_be_invited_again(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->ownerOf($circle);
        $invitee = User::factory()->create(['email' => 'ada@example.com']);
        Approval::open(ApprovalType::Invite, $circle, $invitee, $owner)->cancel($owner);

        $response = $this->actingAs($owner)->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertValid();
        $this->assertSame(ApprovalStatus::Pending, $circle->approvals()->where('type', ApprovalType::Invite)->sole()->status);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    #[DataProvider('invalidEmails')]
    public function test_needs_an_email_address(array $details): void
    {
        $circle = Circle::factory()->create();

        $response = $this->actingAs($this->ownerOf($circle))->post(route('circles.invites.store', $circle), $details);

        $response->assertInvalid(['email']);
        $this->assertNoInvites($circle);
    }

    public function test_rejects_an_email_address_that_is_not_a_members(): void
    {
        $circle = Circle::factory()->create();

        $response = $this->actingAs($this->ownerOf($circle))->post(route('circles.invites.store', $circle), ['email' => 'nobody@example.com']);

        $response->assertInvalid(['email' => "There's no account with that email address. They'll need to join Commune Circles first."]);
        $this->assertNoInvites($circle);
    }

    public function test_rejects_someone_already_in_the_circle(): void
    {
        $circle = Circle::factory()->create();
        $member = User::factory()->create(['email' => 'ada@example.com']);
        $circle->members()->attach($member, ['role' => CircleRole::Member]);

        $response = $this->actingAs($this->ownerOf($circle))->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertInvalid(['email' => "They're already in this circle."]);
        $this->assertNoInvites($circle);
    }

    public function test_rejects_someone_whose_invite_is_still_waiting(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->ownerOf($circle);
        $invitee = User::factory()->create(['email' => 'ada@example.com']);
        $invite = Approval::open(ApprovalType::Invite, $circle, $invitee, $owner);

        $response = $this->actingAs($owner)->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertInvalid(['email' => "They've already been invited, and the invite is still waiting."]);
        $this->assertTrue($invite->updated_at->equalTo($invite->fresh()->updated_at));
    }

    public function test_points_to_the_request_when_the_person_has_already_asked_to_join(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create(['email' => 'ada@example.com']);
        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);

        $response = $this->actingAs($this->ownerOf($circle))->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertInvalid(['email' => "They've already asked to join, so you can accept their request instead."]);
        $this->assertNoInvites($circle);
    }

    public function test_plain_members_cannot_invite_people(): void
    {
        $circle = Circle::factory()->create();
        $member = User::factory()->create();
        $circle->members()->attach($member, ['role' => CircleRole::Member]);
        User::factory()->create(['email' => 'ada@example.com']);

        $response = $this->actingAs($member)->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertForbidden();
        $this->assertNoInvites($circle);
    }

    public function test_staff_cannot_invite_people_into_a_circle_they_do_not_run(): void
    {
        $circle = Circle::factory()->create();
        User::factory()->create(['email' => 'ada@example.com']);

        $response = $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]))->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertForbidden();
        $this->assertNoInvites($circle);
    }

    public function test_the_owner_cannot_invite_people_before_the_circle_is_approved(): void
    {
        $circle = Circle::factory()->pending()->create();
        User::factory()->create(['email' => 'ada@example.com']);

        $response = $this->actingAs($this->ownerOf($circle))->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertForbidden();
        $this->assertNoInvites($circle);
    }

    public function test_a_circle_that_is_not_approved_is_not_found_for_someone_outside_it(): void
    {
        $circle = Circle::factory()->pending()->create();
        User::factory()->create(['email' => 'ada@example.com']);

        $response = $this->actingAs(User::factory()->create())->post(route('circles.invites.store', $circle), ['email' => 'ada@example.com']);

        $response->assertNotFound();
        $this->assertNoInvites($circle);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidEmails(): array
    {
        return [
            'no email' => [[]],
            'an empty email' => [['email' => '']],
            'not an email address' => [['email' => 'ada-at-example']],
            'not text' => [['email' => ['ada@example.com']]],
        ];
    }

    private function assertNoInvites(Circle $circle): void
    {
        $this->assertFalse($circle->approvals()->where('type', ApprovalType::Invite)->exists());
    }

    private function ownerOf(Circle $circle): User
    {
        $owner = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);

        return $owner;
    }
}
