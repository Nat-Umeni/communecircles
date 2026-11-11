<?php

namespace Tests\Feature\Policies;

use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Enums\Role;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CirclePolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_can_browse_circles_and_see_their_public_pages(): void
    {
        $circle = Circle::factory()->create();

        $this->assertTrue(Gate::allows('viewAny', Circle::class));
        $this->assertTrue(Gate::allows('view', $circle));
    }

    public function test_signed_in_users_can_browse_circles_and_see_their_public_pages(): void
    {
        $circle = Circle::factory()->create();
        $outsider = User::factory()->create();

        $this->assertTrue($outsider->can('viewAny', Circle::class));
        $this->assertTrue($outsider->can('view', $circle));
    }

    #[DataProvider('unapproved')]
    public function test_a_circle_that_is_not_approved_is_hidden_from_guests_as_if_it_did_not_exist(string $state): void
    {
        $circle = Circle::factory()->{$state}()->create();

        $this->assertSame(404, Gate::inspect('view', $circle)->status());
    }

    #[DataProvider('unapproved')]
    public function test_a_circle_that_is_not_approved_is_hidden_from_members_of_other_circles(string $state): void
    {
        $circle = Circle::factory()->{$state}()->create();
        $outsider = $this->memberOf(Circle::factory()->create(), CircleRole::Owner);

        $this->assertSame(404, Gate::forUser($outsider)->inspect('view', $circle)->status());
    }

    #[DataProvider('unapproved')]
    public function test_its_own_members_can_see_a_circle_before_it_is_approved(string $state): void
    {
        $circle = Circle::factory()->{$state}()->create();

        $this->assertTrue($this->memberOf($circle, CircleRole::Owner)->can('view', $circle));
    }

    #[DataProvider('unapproved')]
    public function test_staff_can_see_a_circle_before_it_is_approved(string $state): void
    {
        $staff = User::factory()->create(['role' => Role::Admin]);

        $this->assertTrue($staff->can('view', Circle::factory()->{$state}()->create()));
    }

    public function test_nobody_can_ask_to_join_a_circle_that_is_not_approved(): void
    {
        $this->assertFalse(User::factory()->create()->can('requestToJoin', Circle::factory()->pending()->create()));
    }

    #[DataProvider('circleRoles')]
    public function test_every_member_can_view_their_circles_channels(CircleRole $role): void
    {
        $circle = Circle::factory()->create();

        $this->assertTrue($this->memberOf($circle, $role)->can('viewChannels', $circle));
    }

    public function test_a_member_of_another_circle_cannot_view_this_ones_channels(): void
    {
        $circle = Circle::factory()->create();
        $this->memberOf($circle, CircleRole::Owner);
        $outsider = $this->memberOf(Circle::factory()->create(), CircleRole::Owner);

        $this->assertFalse($outsider->can('viewChannels', $circle));
    }

    public function test_guests_cannot_view_a_circles_channels(): void
    {
        $this->assertFalse(Gate::allows('viewChannels', Circle::factory()->create()));
    }

    #[DataProvider('staffRoles')]
    public function test_internal_staff_can_view_any_circles_channels(Role $role): void
    {
        $staff = User::factory()->create(['role' => $role]);

        $this->assertTrue($staff->can('viewChannels', Circle::factory()->create()));
    }

    /**
     * @param  'Owner'|'Admin'  $role
     */
    #[DataProvider('managerRoleNames')]
    public function test_the_circles_owners_and_admins_can_see_who_is_in_it(string $role): void
    {
        $circle = Circle::factory()->create();

        $this->assertTrue($this->memberOf($circle, constant(CircleRole::class.'::'.$role))->can('viewMembers', $circle));
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_see_who_is_in_any_circle(Role $role): void
    {
        $staff = User::factory()->create(['role' => $role]);

        $this->assertTrue($staff->can('viewMembers', Circle::factory()->create()));
    }

    /**
     * @param  'Owner'|'Admin'  $role
     */
    #[DataProvider('managerRoleNames')]
    public function test_the_circles_owners_and_admins_can_see_its_waiting_requests_and_invites(string $role): void
    {
        $circle = Circle::factory()->create();

        $this->assertTrue($this->memberOf($circle, constant(CircleRole::class.'::'.$role))->can('viewWaitingApprovals', $circle));
    }

    public function test_a_plain_member_cannot_see_the_waiting_requests_and_invites(): void
    {
        $circle = Circle::factory()->create();

        $this->assertFalse($this->memberOf($circle, CircleRole::Member)->can('viewWaitingApprovals', $circle));
    }

    #[DataProvider('staffRoles')]
    public function test_staff_can_see_any_circles_waiting_requests_and_invites(Role $role): void
    {
        $this->assertTrue(User::factory()->create(['role' => $role])->can('viewWaitingApprovals', Circle::factory()->create()));
    }

    public function test_an_owner_of_another_circle_cannot_see_this_ones_waiting_requests_and_invites(): void
    {
        $outsider = $this->memberOf(Circle::factory()->create(), CircleRole::Owner);

        $this->assertFalse($outsider->can('viewWaitingApprovals', Circle::factory()->create()));
    }

    public function test_guests_cannot_see_a_circles_waiting_requests_and_invites(): void
    {
        $this->assertFalse(Gate::allows('viewWaitingApprovals', Circle::factory()->create()));
    }

    public function test_a_plain_member_cannot_see_who_is_in_their_circle(): void
    {
        $circle = Circle::factory()->create();

        $this->assertFalse($this->memberOf($circle, CircleRole::Member)->can('viewMembers', $circle));
    }

    public function test_an_owner_of_another_circle_cannot_see_who_is_in_this_one(): void
    {
        $circle = Circle::factory()->create();
        $outsider = $this->memberOf(Circle::factory()->create(), CircleRole::Owner);

        $this->assertFalse($outsider->can('viewMembers', $circle));
    }

    public function test_guests_cannot_see_who_is_in_a_circle(): void
    {
        $this->assertFalse(Gate::allows('viewMembers', Circle::factory()->create()));
    }

    public function test_members_who_confirmed_their_email_can_ask_to_join_a_circle_they_are_not_in(): void
    {
        $circle = Circle::factory()->create();
        $this->memberOf($circle, CircleRole::Owner);
        $outsider = $this->memberOf(Circle::factory()->create(), CircleRole::Owner);

        $this->assertTrue($outsider->can('requestToJoin', $circle));
    }

    #[DataProvider('circleRoles')]
    public function test_members_cannot_ask_to_join_a_circle_they_are_already_in(CircleRole $role): void
    {
        $circle = Circle::factory()->create();

        $this->assertFalse($this->memberOf($circle, $role)->can('requestToJoin', $circle));
    }

    public function test_members_who_have_not_confirmed_their_email_cannot_ask_to_join(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertFalse($user->can('requestToJoin', Circle::factory()->create()));
    }

    public function test_someone_whose_request_to_join_is_still_waiting_cannot_ask_again(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);

        $this->assertFalse($asker->can('requestToJoin', $circle));
    }

    public function test_someone_with_an_invite_waiting_cannot_ask_to_join_too(): void
    {
        $circle = Circle::factory()->create();
        $invitee = User::factory()->create();
        Approval::open(ApprovalType::Invite, $circle, $invitee, $this->memberOf($circle, CircleRole::Owner));

        $this->assertFalse($invitee->can('requestToJoin', $circle));
    }

    public function test_someone_whose_request_was_turned_down_can_ask_again(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker)->reject($this->memberOf($circle, CircleRole::Owner));

        $this->assertTrue($asker->can('requestToJoin', $circle));
    }

    public function test_someone_who_declined_an_invite_can_still_ask_to_join_later(): void
    {
        $circle = Circle::factory()->create();
        $invitee = User::factory()->create();
        Approval::open(ApprovalType::Invite, $circle, $invitee, $this->memberOf($circle, CircleRole::Owner))->reject($invitee);

        $this->assertTrue($invitee->can('requestToJoin', $circle));
    }

    /**
     * @param  'Owner'|'Admin'  $role
     */
    #[DataProvider('managerRoleNames')]
    public function test_the_circles_owners_and_admins_can_invite_people(string $role): void
    {
        $circle = Circle::factory()->create();

        $this->assertTrue($this->memberOf($circle, constant(CircleRole::class.'::'.$role))->can('invite', $circle));
    }

    #[DataProvider('unapproved')]
    public function test_nobody_can_invite_people_into_a_circle_that_is_not_approved(string $state): void
    {
        $circle = Circle::factory()->{$state}()->create();

        $this->assertFalse($this->memberOf($circle, CircleRole::Owner)->can('invite', $circle));
    }

    public function test_plain_members_cannot_invite_people(): void
    {
        $circle = Circle::factory()->create();

        $this->assertFalse($this->memberOf($circle, CircleRole::Member)->can('invite', $circle));
    }

    public function test_someone_outside_the_circle_cannot_invite_people(): void
    {
        $circle = Circle::factory()->create();
        $outsider = $this->memberOf(Circle::factory()->create(), CircleRole::Owner);

        $this->assertFalse($outsider->can('invite', $circle));
    }

    public function test_staff_cannot_invite_people_into_a_circle_they_do_not_run(): void
    {
        $this->assertFalse(User::factory()->create(['role' => Role::SuperAdmin])->can('invite', Circle::factory()->create()));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function managerRoleNames(): array
    {
        return [
            'owner' => ['Owner'],
            'admin' => ['Admin'],
        ];
    }

    public function test_guests_cannot_ask_to_join(): void
    {
        $this->assertFalse(Gate::allows('requestToJoin', Circle::factory()->create()));
    }

    public function test_members_can_create_circles_once_their_account_is_five_minutes_old(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();

        $this->travel(5)->minutes();

        $this->assertTrue($user->can('create', Circle::class));
    }

    public function test_members_can_start_another_circle_once_staff_have_decided_on_their_last_one(): void
    {
        $this->freezeSecond();
        $approved = Circle::factory()->create()->approval->user;
        $rejected = Circle::factory()->rejected()->create()->approval->user;

        $this->travel(5)->minutes();

        $this->assertTrue($approved->can('create', Circle::class));
        $this->assertTrue($rejected->can('create', Circle::class));
    }

    public function test_members_cannot_start_another_circle_while_one_is_waiting_for_approval_and_are_told_why(): void
    {
        $this->freezeSecond();
        $member = Circle::factory()->pending()->create()->approval->user;

        $this->travel(5)->minutes();

        $permission = Gate::forUser($member)->inspect('create', Circle::class);
        $this->assertTrue($permission->denied());
        $this->assertSame('You already have a circle waiting for approval. You can start another once the team has decided on it.', $permission->message());
    }

    public function test_members_cannot_create_circles_in_their_first_five_minutes_and_are_told_why(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();

        $this->travel(5 * 60 - 1)->seconds();

        $permission = Gate::forUser($user)->inspect('create', Circle::class);
        $this->assertTrue($permission->denied());
        $this->assertSame('New accounts can create a circle 5 minutes after joining.', $permission->message());
    }

    public function test_members_who_have_not_confirmed_their_email_cannot_create_circles_and_are_told_why(): void
    {
        $this->freezeSecond();
        $user = User::factory()->unverified()->create();

        $this->travel(5)->minutes();

        $permission = Gate::forUser($user)->inspect('create', Circle::class);
        $this->assertTrue($permission->denied());
        $this->assertSame('Confirm your email address to create a circle.', $permission->message());
    }

    public function test_asks_brand_new_accounts_to_confirm_their_email_before_mentioning_the_wait(): void
    {
        $user = User::factory()->unverified()->create();

        $permission = Gate::forUser($user)->inspect('create', Circle::class);

        $this->assertSame('Confirm your email address to create a circle.', $permission->message());
    }

    #[DataProvider('staffRoles')]
    public function test_internal_staff_can_create_circles_straight_away_even_before_confirming_their_email(Role $role): void
    {
        $staff = User::factory()->unverified()->create(['role' => $role]);

        $this->assertTrue($staff->can('create', Circle::class));
    }

    public function test_staff_can_start_circles_even_with_one_waiting_for_approval(): void
    {
        $staff = Circle::factory()->pending()->create()->approval->user;
        $staff->forceFill(['role' => Role::Admin])->save();

        $this->assertTrue($staff->can('create', Circle::class));
    }

    #[DataProvider('managerRoles')]
    public function test_only_owners_and_admins_can_update_their_circle(CircleRole $role, bool $isManager): void
    {
        $circle = Circle::factory()->create();

        $this->assertSame($isManager, $this->memberOf($circle, $role)->can('update', $circle));
    }

    public function test_an_owner_of_another_circle_cannot_update_this_one(): void
    {
        $circle = Circle::factory()->create();
        $this->memberOf($circle, CircleRole::Owner);
        $outsider = $this->memberOf(Circle::factory()->create(), CircleRole::Owner);

        $this->assertFalse($outsider->can('update', $circle));
    }

    public function test_the_owner_can_edit_their_rejected_circle_to_send_it_back_when_nothing_else_of_theirs_is_waiting(): void
    {
        $circle = Circle::factory()->rejected()->create();

        $this->assertTrue($this->ownerWhoStarted($circle)->can('update', $circle));
    }

    public function test_the_owner_can_edit_their_approved_circle_while_another_of_theirs_is_waiting(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->ownerWhoStarted($circle);
        $this->anotherCircleWaitingFor($owner);

        $this->assertTrue($owner->can('update', $circle));
    }

    public function test_the_owner_cannot_edit_their_rejected_circle_while_another_of_theirs_is_waiting(): void
    {
        $circle = Circle::factory()->rejected()->create();
        $owner = $this->ownerWhoStarted($circle);
        $this->anotherCircleWaitingFor($owner);

        $this->assertFalse($owner->can('update', $circle));
    }

    public function test_staff_can_edit_a_rejected_circle_even_while_another_of_its_starters_is_waiting(): void
    {
        $circle = Circle::factory()->rejected()->create();
        $this->anotherCircleWaitingFor($this->ownerWhoStarted($circle));

        $this->assertTrue(User::factory()->create(['role' => Role::Admin])->can('update', $circle));
    }

    #[DataProvider('staffRoles')]
    public function test_internal_staff_can_update_any_circle(Role $role): void
    {
        $staff = User::factory()->create(['role' => $role]);

        $this->assertTrue($staff->can('update', Circle::factory()->create()));
    }

    #[DataProvider('siteRoles')]
    public function test_only_internal_staff_can_delete_a_circle(Role $role, bool $isInternal): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->assertSame($isInternal, $user->can('delete', Circle::factory()->create()));
    }

    public function test_owners_cannot_delete_their_own_circle(): void
    {
        $circle = Circle::factory()->create();

        $this->assertFalse($this->memberOf($circle, CircleRole::Owner)->can('delete', $circle));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unapproved(): array
    {
        return [
            'waiting for approval' => ['pending'],
            'rejected' => ['rejected'],
        ];
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
     * @return array<string, array{Role}>
     */
    public static function staffRoles(): array
    {
        return [
            'admin' => [Role::Admin],
            'super admin' => [Role::SuperAdmin],
        ];
    }

    /**
     * @return array<string, array{CircleRole}>
     */
    public static function circleRoles(): array
    {
        return [
            'owner' => [CircleRole::Owner],
            'admin' => [CircleRole::Admin],
            'member' => [CircleRole::Member],
        ];
    }

    /**
     * @return array<string, array{CircleRole, bool}>
     */
    public static function managerRoles(): array
    {
        return [
            'owner' => [CircleRole::Owner, true],
            'admin' => [CircleRole::Admin, true],
            'member' => [CircleRole::Member, false],
        ];
    }

    /**
     * A new user who belongs to the circle with the given role.
     */
    /**
     * The person who started the circle (its approval is for them), as its owner.
     */
    private function ownerWhoStarted(Circle $circle): User
    {
        $owner = $circle->approval->user;
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);

        return $owner;
    }

    /**
     * Another new circle of theirs, still waiting for approval.
     */
    private function anotherCircleWaitingFor(User $user): void
    {
        Approval::open(ApprovalType::NewCircle, Circle::factory()->withoutApproval()->create(), $user, $user);
    }

    private function memberOf(Circle $circle, CircleRole $role): User
    {
        $user = User::factory()->create();
        $circle->members()->attach($user, ['role' => $role]);

        return $user;
    }
}
