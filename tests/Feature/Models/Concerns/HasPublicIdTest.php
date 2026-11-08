<?php

namespace Tests\Feature\Models\Concerns;

use App\Models\Circle;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * NOTE: a trait needs a model to live on, so these run through Circle, which uses it.
 */
class HasPublicIdTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_gives_a_new_model_a_ten_character_public_id_and_keeps_its_incrementing_id(): void
    {
        $circle = Circle::factory()->create();

        $this->assertMatchesRegularExpression('/^[0-9a-z]{10}$/', $circle->public_id);
        $this->assertIsInt($circle->id);
    }

    public function test_gives_each_model_its_own_public_id(): void
    {
        [$first, $second] = Circle::factory()->count(2)->create();

        $this->assertNotSame($first->public_id, $second->public_id);
    }

    public function test_puts_the_public_id_in_urls_and_finds_the_model_by_it(): void
    {
        $circle = Circle::factory()->create();

        $this->assertSame($circle->public_id, $circle->getRouteKey());
        $this->assertTrue($circle->is((new Circle)->resolveRouteBinding($circle->public_id)));
    }

    /**
     * @param  Closure(Circle): string  $malformed
     */
    #[DataProvider('malformedPublicIds')]
    public function test_treats_a_malformed_public_id_as_not_found(Closure $malformed): void
    {
        $circle = Circle::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        (new Circle)->resolveRouteBinding($malformed($circle));
    }

    /**
     * @return array<string, array{Closure(Circle): string}>
     */
    public static function malformedPublicIds(): array
    {
        return [
            'its numeric id' => [fn (Circle $circle): string => (string) $circle->id],
            'its public id in capitals' => [fn (Circle $circle): string => strtoupper($circle->public_id)],
            'its public id a character short' => [fn (Circle $circle): string => substr($circle->public_id, 0, 9)],
        ];
    }
}
