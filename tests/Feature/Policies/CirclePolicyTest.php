<?php

namespace Tests\Feature\Policies;

use App\Enums\CircleRole;
use App\Enums\Role;
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

    public function test_members_cannot_create_circles_in_their_first_five_minutes(): void
    {
        $this->freezeSecond();
        $user = User::factory()->create();

        $this->travel(5 * 60 - 1)->seconds();

        $this->assertFalse($user->can('create', Circle::class));
    }

    public function test_members_who_have_not_confirmed_their_email_cannot_create_circles(): void
    {
        $this->freezeSecond();
        $user = User::factory()->unverified()->create();

        $this->travel(5)->minutes();

        $this->assertFalse($user->can('create', Circle::class));
    }

    #[DataProvider('staffRoles')]
    public function test_internal_staff_can_create_circles_straight_away_even_before_confirming_their_email(Role $role): void
    {
        $staff = User::factory()->unverified()->create(['role' => $role]);

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
    private function memberOf(Circle $circle, CircleRole $role): User
    {
        $user = User::factory()->create();
        $circle->members()->attach($user, ['role' => $role]);

        return $user;
    }
}
