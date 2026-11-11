<?php

namespace Tests\Feature\Http\Requests\Circle;

use App\Enums\Circle\CircleRole;
use App\Enums\User\Role;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DeleteCircleRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_lets_staff_delete_a_circle(): void
    {
        $circle = Circle::factory()->create();
        $staff = User::factory()->create(['role' => Role::Admin]);

        $response = $this->actingAs($staff)->delete(route('circles.destroy', $circle));

        $response->assertRedirect();
        $this->assertModelMissing($circle);
    }

    public function test_refuses_the_circles_own_owner(): void
    {
        $circle = Circle::factory()->create();
        $owner = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);

        $response = $this->actingAs($owner)->delete(route('circles.destroy', $circle));

        $response->assertForbidden();
        $this->assertModelExists($circle);
    }
}
