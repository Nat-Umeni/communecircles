<?php

namespace Tests\Feature\Policies;

use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Enums\Role;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApprovalPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[DataProvider('siteRoles')]
    public function test_only_staff_see_the_queue_of_circles_waiting_for_approval(Role $role, bool $isStaff): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->assertSame($isStaff, $user->can('viewAny', Approval::class));
    }

    #[DataProvider('siteRoles')]
    public function test_only_staff_decide_on_a_new_circle(Role $role, bool $isStaff): void
    {
        $circle = Circle::factory()->pending()->create();

        $this->assertSame($isStaff, User::factory()->create(['role' => $role])->can('decide', $circle->approval));
    }

    public function test_a_new_circles_own_creator_cannot_approve_it(): void
    {
        $circle = Circle::factory()->pending()->create();
        $creator = $circle->approval->user;
        $circle->members()->attach($creator, ['role' => CircleRole::Owner]);

        $this->assertFalse($creator->can('decide', $circle->approval));
    }

    /**
     * @param  'Owner'|'Admin'|'Member'  $role
     */
    #[DataProvider('circleRoles')]
    public function test_only_the_circles_owners_and_admins_decide_on_a_request_to_join(string $role, bool $isManager): void
    {
        $circle = Circle::factory()->create();
        $request = $this->joinRequestFor($circle);
        $member = User::factory()->create();
        $circle->members()->attach($member, ['role' => constant(CircleRole::class.'::'.$role)]);

        $this->assertSame($isManager, $member->can('decide', $request));
    }

    public function test_staff_do_not_decide_on_a_request_to_join(): void
    {
        $request = $this->joinRequestFor(Circle::factory()->create());

        $this->assertFalse(User::factory()->create(['role' => Role::SuperAdmin])->can('decide', $request));
    }

    public function test_someone_cannot_decide_on_their_own_request_to_join(): void
    {
        $request = $this->joinRequestFor(Circle::factory()->create());

        $this->assertFalse($request->user->can('decide', $request));
    }

    public function test_only_the_person_invited_decides_on_an_invite(): void
    {
        $circle = Circle::factory()->create();
        $owner = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);
        $invitee = User::factory()->create();
        $invite = Approval::open(ApprovalType::Invite, $circle, $invitee, $owner);

        $this->assertTrue($invitee->can('decide', $invite));
        $this->assertFalse($owner->can('decide', $invite));
        $this->assertFalse(User::factory()->create(['role' => Role::SuperAdmin])->can('decide', $invite));
        $this->assertFalse(User::factory()->create()->can('decide', $invite));
    }

    /**
     * @param  Closure(): Circle  $decided
     */
    #[DataProvider('decided')]
    public function test_nobody_can_decide_on_something_already_decided(Closure $decided): void
    {
        $circle = $decided();

        $this->assertFalse(User::factory()->create(['role' => Role::SuperAdmin])->can('decide', $circle->approval));
    }

    /**
     * @return array<string, array{Role, bool}>
     */
    public static function siteRoles(): array
    {
        return [
            'user' => [Role::User, false],
            'admin' => [Role::Admin, true],
            'super admin' => [Role::SuperAdmin, true],
        ];
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function circleRoles(): array
    {
        return [
            'owner' => ['Owner', true],
            'admin' => ['Admin', true],
            'member' => ['Member', false],
        ];
    }

    /**
     * @return array<string, array{Closure(): Circle}>
     */
    public static function decided(): array
    {
        return [
            'approved' => [fn (): Circle => Circle::factory()->create()],
            'rejected' => [fn (): Circle => Circle::factory()->rejected()->create()],
        ];
    }

    /**
     * A new user's waiting request to join the circle.
     */
    private function joinRequestFor(Circle $circle): Approval
    {
        $asker = User::factory()->create();

        return Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);
    }
}
