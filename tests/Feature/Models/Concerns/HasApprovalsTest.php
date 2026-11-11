<?php

namespace Tests\Feature\Models\Concerns;

use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * NOTE: a trait needs a model to live on, so these run through Circle, which uses it.
 */
class HasApprovalsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_has_all_its_approvals_of_every_kind(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);
        Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $this->ownerOf($circle));

        $this->assertEqualsCanonicalizing(
            ['new_circle', 'join_request', 'invite'],
            $circle->approvals()->pluck('type')->map->value->all(),
        );
    }

    public function test_waiting_approvals_are_the_ones_of_a_kind_still_waiting_oldest_first_with_who_they_are_about(): void
    {
        $this->freezeSecond();
        $circle = Circle::factory()->create();
        $first = $this->askToJoin($circle);
        $this->travel(1)->hour();
        $second = $this->askToJoin($circle);
        $this->askToJoin($circle)->reject($this->ownerOf($circle));
        Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $this->ownerOf($circle));

        $waiting = $circle->waitingApprovals(ApprovalType::JoinRequest);

        $this->assertSame([$first->id, $second->id], $waiting->modelKeys());
        $this->assertTrue($waiting->every(fn (Approval $approval): bool => $approval->relationLoaded('user')));
    }

    public function test_approval_for_is_that_persons_approval_of_a_kind(): void
    {
        $circle = Circle::factory()->create();
        $request = $this->askToJoin($circle);

        $this->assertTrue($circle->approvalFor(ApprovalType::JoinRequest, $request->user)?->is($request));
        $this->assertNull($circle->approvalFor(ApprovalType::Invite, $request->user));
        $this->assertNull($circle->approvalFor(ApprovalType::JoinRequest, User::factory()->create()));
    }

    public function test_approval_for_a_guest_is_nothing(): void
    {
        $this->assertNull(Circle::factory()->create()->approvalFor(ApprovalType::JoinRequest, null));
    }

    private function askToJoin(Circle $circle): Approval
    {
        $asker = User::factory()->create();

        return Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);
    }

    private function ownerOf(Circle $circle): User
    {
        $owner = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);

        return $owner;
    }
}
