<?php

namespace Tests\Feature\Database\Seeders;

use App\Enums\Approval\ApprovalStatus;
use App\Enums\Approval\ApprovalType;
use App\Enums\Circle\CircleRole;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Database\Seeders\CircleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * NOTE: one test, so the whole circle seed runs once (each test would re-seed inside its own rolled-back transaction);
 * each assertion says which part of the seed it checks.
 */
class CircleSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeds_circles_in_every_state_with_lists_long_enough_to_page_through(): void
    {
        $this->seed([UserSeeder::class, CircleSeeder::class]);

        $this->assertCirclesInEveryState();
        $this->assertEachCircleStartedByItsOwner();
        $this->assertMostCirclesAreOnTheMap();
        $this->assertTheBigCircle();
        $this->assertTheCircleOwnersOtherCircles();
        $this->assertTheCircleMembersRequestAndInvites();
    }

    private function assertCirclesInEveryState(): void
    {
        $this->assertSame(CircleSeeder::APPROVED + 1, Circle::query()->approved()->count(), 'approved circles, the big one included');
        $this->assertSame(CircleSeeder::WAITING + 1, $this->newCircles(ApprovalStatus::Pending), "circles waiting for staff, the circle owner's included");
        $this->assertSame(CircleSeeder::REJECTED + 1, $this->newCircles(ApprovalStatus::Rejected), "circles turned down, the circle owner's included");
        $this->assertFalse(
            Approval::query()->where('type', ApprovalType::NewCircle)->where('status', ApprovalStatus::Rejected)->whereNull('reason')->exists(),
            'every circle turned down says why',
        );
    }

    private function assertEachCircleStartedByItsOwner(): void
    {
        foreach (Circle::query()->with('approval.user')->get() as $circle) {
            $this->assertSame(CircleRole::Owner, $circle->membershipOf($circle->approval->user)?->role, "{$circle->name} is owned by whoever started it");
        }

        $this->assertGreaterThan(1, Circle::query()->whereNotNull('location_name')->count(), 'the big circle and some others say where they are');
    }

    private function assertMostCirclesAreOnTheMap(): void
    {
        $this->assertGreaterThan(
            Circle::query()->count() / 2,
            Circle::query()->whereNotNull('latitude')->whereNotNull('longitude')->count(),
            'most circles are near a GeoNames town, with coordinates for the explore map',
        );
        $this->assertGreaterThan(1, Circle::query()->distinct()->count('country_code'), 'the circles are in more than one country');
        $big = Circle::query()->where('name', CircleSeeder::BIG_CIRCLE)->sole();
        $this->assertSame(['Fernhill', 'Totnes'], [$big->location_name, $big->place_name], 'the big circle is on its land near Totnes');
    }

    private function assertTheBigCircle(): void
    {
        $big = Circle::query()->where('name', CircleSeeder::BIG_CIRCLE)->sole();

        $this->assertSame(CircleRole::Owner, $big->membershipOf($this->account(UserSeeder::CIRCLE_OWNER))?->role, 'the circle owner owns the big circle');
        $this->assertSame(CircleRole::Admin, $big->membershipOf($this->account(UserSeeder::CIRCLE_ADMIN))?->role, 'the circle admin is its admin');
        $this->assertSame(CircleRole::Member, $big->membershipOf($this->account(UserSeeder::CIRCLE_MEMBER))?->role, 'the circle member is a member');
        $this->assertSame(CircleSeeder::BIG_CIRCLE_MEMBERS, $big->members()->count(), "the big circle's members");
        $this->assertSame(CircleSeeder::BIG_CIRCLE_REQUESTS, $this->waiting($big, ApprovalType::JoinRequest), "the big circle's waiting requests to join");
        $this->assertSame(CircleSeeder::BIG_CIRCLE_INVITES, $this->waiting($big, ApprovalType::Invite), "the big circle's waiting invites");
        $this->assertTrue($big->approvals()->where('type', ApprovalType::JoinRequest)->where('status', ApprovalStatus::Rejected)->exists(), 'some requests to join were declined');
        $this->assertTrue($big->approvals()->where('type', ApprovalType::Invite)->where('status', ApprovalStatus::Cancelled)->exists(), 'an invite was withdrawn');
    }

    private function assertTheCircleOwnersOtherCircles(): void
    {
        $theirs = Approval::query()->where('type', ApprovalType::NewCircle)->where('user_id', $this->account(UserSeeder::CIRCLE_OWNER)->id);

        $this->assertSame(1, (clone $theirs)->where('status', ApprovalStatus::Pending)->count(), 'the circle owner has one circle waiting');
        $this->assertNotNull((clone $theirs)->where('status', ApprovalStatus::Rejected)->sole()->reason, 'and one turned down, saying why');
    }

    private function assertTheCircleMembersRequestAndInvites(): void
    {
        $theirs = Approval::query()->where('user_id', $this->account(UserSeeder::CIRCLE_MEMBER)->id);
        $circles = [
            (clone $theirs)->where('type', ApprovalType::JoinRequest)->where('status', ApprovalStatus::Pending)->sole()->approvable_id,
            (clone $theirs)->where('type', ApprovalType::Invite)->where('status', ApprovalStatus::Pending)->sole()->approvable_id,
            (clone $theirs)->where('type', ApprovalType::Invite)->where('status', ApprovalStatus::Cancelled)->sole()->approvable_id,
        ];

        $this->assertCount(3, array_unique($circles), "the circle member's request, invite and expired invite are for three circles");
        $this->assertNotContains(Circle::query()->where('name', CircleSeeder::BIG_CIRCLE)->value('id'), $circles, "none of them is the big circle, which they're in");
    }

    private function newCircles(ApprovalStatus $status): int
    {
        return Approval::query()->where('type', ApprovalType::NewCircle)->where('status', $status)->count();
    }

    private function waiting(Circle $circle, ApprovalType $type): int
    {
        return $circle->approvals()->pending()->where('type', $type)->count();
    }

    private function account(string $email): User
    {
        return User::query()->where('email', $email)->sole();
    }
}
