<?php

namespace Tests\Feature\Http\Resources;

use App\Enums\Approval\ApprovalType;
use App\Enums\Circle\CircleRole;
use App\Enums\User\Role;
use App\Http\Resources\ApprovalResource;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ApprovalResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sends_what_kind_of_approval_it_is_where_it_stands_why_and_since_when(): void
    {
        $this->freezeSecond();
        $circle = Circle::factory()->rejected('There is already a circle for tool sharing.')->create();
        $approval = $circle->approval->fresh();

        $this->assertSame([
            'id' => $approval->id,
            'type' => ['value' => 'new_circle', 'label' => 'New circle'],
            'status' => ['value' => 'rejected', 'label' => 'Not approved'],
            'reason' => 'There is already a circle for tool sharing.',
            'requested_at' => now()->toIso8601String(),
            'can' => ['decide' => false, 'cancel' => false],
        ], $this->sent($approval));
    }

    public function test_tells_the_viewer_whether_they_can_decide_on_it(): void
    {
        $waiting = Circle::factory()->pending()->create()->approval->fresh();

        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        $this->assertSame(['decide' => true, 'cancel' => false], $this->sent($waiting)['can']);
    }

    public function test_tells_the_viewer_whether_they_can_cancel_it(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->ownerOf($circle);
        $invite = Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $owner);

        $this->actingAs($owner);

        $this->assertSame(['decide' => false, 'cancel' => true], $this->sent($invite->fresh())['can']);
    }

    public function test_sends_who_it_is_about_and_who_asked_only_once_they_are_loaded(): void
    {
        $circle = Circle::factory()->create();
        $invitee = User::factory()->create(['first_name' => 'Alan', 'last_name' => 'Turing']);
        $owner = $this->ownerOf($circle);
        $owner->update(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $invite = Approval::open(ApprovalType::Invite, $circle, $invitee, $owner);

        $this->assertArrayNotHasKey('user', $this->sent($invite->fresh()));
        $this->assertArrayNotHasKey('requester', $this->sent($invite->fresh()));

        $sent = $this->sent($invite->fresh()->load(['user', 'requester']));

        $this->assertSame(['first_name' => 'Alan', 'last_name' => 'Turing'], $sent['user']);
        $this->assertSame(['first_name' => 'Ada', 'last_name' => 'Lovelace'], $sent['requester']);
    }

    public function test_sends_no_one_as_who_asked_once_their_account_is_gone(): void
    {
        $approval = Circle::factory()->pending()->create()->approval;
        $approval->update(['requested_by' => null]);

        $this->assertNull($this->sent($approval->fresh()->load('requester'))['requester']);
    }

    public function test_sums_up_the_thing_waiting_for_approval_only_once_it_is_loaded(): void
    {
        $circle = Circle::factory()->pending()->create(['name' => 'Seed Exchange', 'description' => 'Swapping seeds.']);

        $this->assertArrayNotHasKey('subject', $this->sent($circle->approval->fresh()));
        $this->assertSame(
            ['name' => 'Seed Exchange', 'description' => 'Swapping seeds.', 'url' => route('circles.show', $circle)],
            $this->sent($circle->approval->fresh()->load('approvable'))['subject'],
        );
    }

    public function test_tells_the_person_invited_only_that_a_cancelled_invite_expired(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->ownerOf($circle);
        $invitee = User::factory()->create();
        $invite = Approval::open(ApprovalType::Invite, $circle, $invitee, $owner);
        $invite->cancel($owner);

        $this->actingAs($invitee);

        $this->assertSame(['value' => 'expired', 'label' => 'Expired'], $this->sent($invite->fresh())['status']);
    }

    public function test_tells_everyone_else_a_cancelled_invite_was_cancelled(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->ownerOf($circle);
        $invite = Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $owner);
        $invite->cancel($owner);

        $this->actingAs($owner);

        $this->assertSame(['value' => 'cancelled', 'label' => 'Cancelled'], $this->sent($invite->fresh())['status']);
    }

    /**
     * The approval as a page receives it.
     *
     * @return array<string, mixed>
     */
    private function sent(Approval $approval): array
    {
        return $this->asSent(ApprovalResource::make($approval));
    }

    private function ownerOf(Circle $circle): User
    {
        $owner = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);

        return $owner;
    }
}
