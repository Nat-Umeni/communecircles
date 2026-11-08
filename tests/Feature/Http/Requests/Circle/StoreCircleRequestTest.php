<?php

namespace Tests\Feature\Http\Requests\Circle;

use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreCircleRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $details
     */
    #[DataProvider('invalidDetails')]
    public function test_rejects_invalid_details(array $details, string $field): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), [
            'name' => 'Exchange',
            'description' => 'Share skills and lend tools.',
            ...$details,
        ]);

        $response->assertInvalid([$field]);
        $this->assertDatabaseEmpty('circles');
    }

    #[DataProvider('detailsAtTheLimits')]
    public function test_accepts_details_at_the_length_limits(string $name, string $description): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), [
            'name' => $name,
            'description' => $description,
        ]);

        $response->assertValid();
        $this->assertDatabaseCount('circles', 1);
    }

    public function test_rejects_a_name_another_circle_already_has(): void
    {
        Circle::factory()->create(['name' => 'Exchange']);

        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), [
            'name' => 'Exchange',
            'description' => 'Share skills and lend tools.',
        ]);

        $response->assertInvalid(['name']);
        $this->assertDatabaseCount('circles', 1);
    }

    public function test_refuses_accounts_that_cannot_create_circles_yet(): void
    {
        $response = $this->actingAs(User::factory()->create())->post(route('circles.store'), [
            'name' => 'Exchange',
            'description' => 'Share skills and lend tools.',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseEmpty('circles');
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidDetails(): array
    {
        return [
            'no name' => [['name' => ''], 'name'],
            'a name that is not text' => [['name' => 12345], 'name'],
            'a name under 3 characters' => [['name' => 'Ex'], 'name'],
            'a name over 255 characters' => [['name' => str_repeat('a', 256)], 'name'],
            'no description' => [['description' => ''], 'description'],
            'a description that is not text' => [['description' => 12345], 'description'],
            'a description under 3 characters' => [['description' => 'Sh'], 'description'],
            'a description over 500 characters' => [['description' => str_repeat('a', 501)], 'description'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function detailsAtTheLimits(): array
    {
        return [
            'the shortest' => ['Exc', 'Sha'],
            'the longest' => [str_repeat('a', 255), str_repeat('a', 500)],
        ];
    }

    /**
     * A member whose account is old enough to create circles.
     */
    private function memberWhoCanCreate(): User
    {
        $this->freezeSecond();
        $user = User::factory()->create();
        $this->travel(5)->minutes();

        return $user;
    }
}
