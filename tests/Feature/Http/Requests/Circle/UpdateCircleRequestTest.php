<?php

namespace Tests\Feature\Http\Requests\Circle;

use App\Enums\Circle\CircleRole;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateCircleRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $details
     */
    #[DataProvider('invalidDetails')]
    public function test_rejects_invalid_details(array $details, string $field): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange']);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), [
            'name' => 'Tool library',
            'description' => 'Borrow tools from your neighbours.',
            ...$details,
        ]);

        $response->assertInvalid([$field]);
        $this->assertSame('Exchange', $circle->fresh()->name);
    }

    #[DataProvider('detailsAtTheLimits')]
    public function test_accepts_details_at_the_length_limits(string $name, string $description): void
    {
        $circle = Circle::factory()->create();

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), [
            'name' => $name,
            'description' => $description,
        ]);

        $response->assertValid();
        $this->assertSame($name, $circle->fresh()->name);
    }

    public function test_lets_the_circle_keep_its_own_name(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange']);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), [
            'name' => 'Exchange',
            'description' => 'Borrow tools from your neighbours.',
        ]);

        $response->assertValid();
        $this->assertSame('Borrow tools from your neighbours.', $circle->fresh()->description);
    }

    public function test_rejects_a_name_another_circle_already_has(): void
    {
        Circle::factory()->create(['name' => 'Tool library']);
        $circle = Circle::factory()->create(['name' => 'Exchange']);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), [
            'name' => 'Tool library',
            'description' => 'Borrow tools from your neighbours.',
        ]);

        $response->assertInvalid(['name']);
        $this->assertSame('Exchange', $circle->fresh()->name);
    }

    public function test_refuses_members_who_cannot_manage_the_circle(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange']);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Member))->put(route('circles.update', $circle), [
            'name' => 'Tool library',
            'description' => 'Borrow tools from your neighbours.',
        ]);

        $response->assertForbidden();
        $this->assertSame('Exchange', $circle->fresh()->name);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidDetails(): array
    {
        return [
            'no name' => [['name' => ''], 'name'],
            'a name that is not text' => [['name' => 12345], 'name'],
            'a name under 3 characters' => [['name' => 'To'], 'name'],
            'a name over 255 characters' => [['name' => str_repeat('a', 256)], 'name'],
            'no description' => [['description' => ''], 'description'],
            'a description that is not text' => [['description' => 12345], 'description'],
            'a description under 3 characters' => [['description' => 'Bo'], 'description'],
            'a description over 500 characters' => [['description' => str_repeat('a', 501)], 'description'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function detailsAtTheLimits(): array
    {
        return [
            'the shortest' => ['Too', 'Bor'],
            'the longest' => [str_repeat('a', 255), str_repeat('a', 500)],
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
