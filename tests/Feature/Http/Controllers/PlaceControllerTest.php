<?php

namespace Tests\Feature\Http\Controllers;

use App\Exceptions\GeoNamesException;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PlaceControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * Totnes as GeoNames describes it.
     */
    private const array TOTNES = [
        'geonameId' => 2635610,
        'name' => 'Totnes',
        'adminName1' => 'England',
        'countryCode' => 'GB',
        'countryName' => 'United Kingdom',
        'lat' => '50.43107',
        'lng' => '-3.6843',
        'fcl' => 'P',
    ];

    public function test_finds_towns_and_villages_whose_name_starts_with_the_search(): void
    {
        Http::fake(['geonames.test/searchJSON*' => Http::response(['geonames' => [self::TOTNES]])]);

        $response = $this->actingAs(User::factory()->create())->getJson(route('places.search', ['search' => 'Totn']));

        $response->assertOk();
        $response->assertExactJson(['places' => [[
            'geonames_id' => 2635610,
            'name' => 'Totnes',
            'region' => 'England',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'latitude' => 50.43107,
            'longitude' => -3.6843,
        ]]]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    #[DataProvider('invalidSearches')]
    public function test_rejects_a_search_that_is_missing_or_too_short_or_too_long(array $query): void
    {
        $response = $this->actingAs(User::factory()->create())->getJson(route('places.search', $query));

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['search']);
        Http::assertNothingSent();
    }

    public function test_says_place_search_is_unavailable_and_reports_why_when_geonames_cannot_answer(): void
    {
        Exceptions::fake();
        Http::fake(['geonames.test/*' => Http::response(['status' => ['message' => 'the hourly limit has been exceeded', 'value' => 19]])]);

        $response = $this->actingAs(User::factory()->create())->getJson(route('places.search', ['search' => 'Totn']));

        $response->assertServiceUnavailable();
        $response->assertExactJson(['message' => "Place search isn't available right now. Try again in a little while."]);
        Exceptions::assertReported(GeoNamesException::class);
    }

    public function test_guests_cannot_search_places(): void
    {
        $response = $this->getJson(route('places.search', ['search' => 'Totn']));

        $response->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_limits_a_member_to_30_searches_a_minute(): void
    {
        Http::fake(['geonames.test/*' => Http::response(['geonames' => []])]);
        $member = User::factory()->create();

        foreach (range(1, 30) as $search) {
            $this->actingAs($member)->getJson(route('places.search', ['search' => "Place {$search}"]))->assertOk();
        }
        $response = $this->actingAs($member)->getJson(route('places.search', ['search' => 'One more']));

        $response->assertTooManyRequests();
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidSearches(): array
    {
        return [
            'no search' => [[]],
            'one character' => [['search' => 'T']],
            'over 100 characters' => [['search' => str_repeat('a', 101)]],
        ];
    }
}
