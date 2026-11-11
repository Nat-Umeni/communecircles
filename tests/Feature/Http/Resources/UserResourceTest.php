<?php

namespace Tests\Feature\Http\Resources;

use App\Enums\Circle\CircleRole;
use App\Enums\User\Role;
use App\Http\Resources\UserResource;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sends_only_someones_name_to_everyone_else(): void
    {
        $ada = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);

        $this->assertSame(['first_name' => 'Ada', 'last_name' => 'Lovelace'], $this->sent($ada));

        $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]));

        $this->assertSame(['first_name' => 'Ada', 'last_name' => 'Lovelace'], $this->sent($ada));
    }

    public function test_sends_the_signed_in_member_their_own_email_whether_it_is_confirmed_and_their_role(): void
    {
        $ada = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com', 'role' => Role::Admin]);

        $this->actingAs($ada);

        $this->assertSame([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'email_verified' => true,
            'role' => ['value' => 'admin', 'label' => 'Admin', 'internal' => true],
        ], $this->sent($ada));
    }

    public function test_tells_the_signed_in_member_when_they_have_not_confirmed_their_email(): void
    {
        $member = User::factory()->unverified()->create();

        $this->actingAs($member);

        $this->assertFalse($this->sent($member)['email_verified']);
    }

    public function test_sends_their_membership_when_they_come_from_a_circles_members(): void
    {
        $this->freezeSecond();
        $circle = Circle::factory()->create();
        $member = User::factory()->create();
        $circle->members()->attach($member, ['role' => CircleRole::Admin]);

        $this->assertSame([
            'id' => $circle->membershipOf($member)->id,
            'role' => ['value' => 'admin', 'label' => 'Admin'],
            'joined_at' => now()->toIso8601String(),
        ], $this->sent($circle->members()->sole())['membership']);
        $this->assertArrayNotHasKey('membership', $this->sent($member));
    }

    /**
     * The user as a page receives them.
     *
     * @return array<string, mixed>
     */
    private function sent(User $user): array
    {
        return $this->asSent(UserResource::make($user));
    }
}
