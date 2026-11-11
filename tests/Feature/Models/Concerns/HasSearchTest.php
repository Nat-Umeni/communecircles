<?php

namespace Tests\Feature\Models\Concerns;

use App\Models\Circle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * NOTE: a trait needs a model to live on, so these run through Circle, which searches its name and description.
 */
class HasSearchTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_search_finds_what_has_every_word_in_one_of_its_columns_in_any_order_whatever_the_case(): void
    {
        $exchange = Circle::factory()->create(['name' => 'Seed Exchange', 'description' => 'Swapping what grows well here.']);
        Circle::factory()->create(['name' => 'Seed Library', 'description' => 'Borrowing seeds for the season.']);

        $this->assertSame([$exchange->id], Circle::query()->search('SWAP exch')->pluck('id')->all());
    }

    public function test_search_finds_nothing_when_one_of_the_words_is_nowhere(): void
    {
        Circle::factory()->create(['name' => 'Seed Exchange', 'description' => 'Swapping what grows well here.']);

        $this->assertSame([], Circle::query()->search('seed tools')->pluck('id')->all());
    }

    public function test_search_ignores_extra_spaces_between_and_around_the_words(): void
    {
        $exchange = Circle::factory()->create(['name' => 'Seed Exchange', 'description' => 'Swapping what grows well here.']);

        $this->assertSame([$exchange->id], Circle::query()->search('  seed    exchange ')->pluck('id')->all());
    }

    public function test_search_keeps_to_the_rest_of_the_query(): void
    {
        $approved = Circle::factory()->create(['name' => 'Seed Exchange']);
        Circle::factory()->pending()->create(['name' => 'Seed Exchange North']);

        $this->assertSame([$approved->id], Circle::query()->approved()->search('seed exchange')->pluck('id')->all());
    }
}
