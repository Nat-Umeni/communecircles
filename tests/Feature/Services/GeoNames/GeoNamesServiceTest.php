<?php

namespace Tests\Feature\Services\GeoNames;

use App\DTO\GeoNames\Place;
use App\Services\GeoNames\GeoNamesService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeoNamesServiceTest extends TestCase
{
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

    protected function setUp(): void
    {
        parent::setUp();

        config(['geonames.username' => 'commune-test', 'geonames.base_url' => 'https://geonames.test']);
    }

    public function test_searches_towns_and_villages_whose_name_starts_with_the_text(): void
    {
        Http::fake(['geonames.test/searchJSON*' => Http::response(['totalResultsCount' => 1, 'geonames' => [self::TOTNES]])]);

        $places = app(GeoNamesService::class)->searchPlaces('Totn');

        $this->assertEquals([Place::fromGeoNames(self::TOTNES)], $places->all());
        Http::assertSent(fn (Request $request): bool => $request['name_startsWith'] === 'Totn'
            && $request['featureClass'] === 'P'
            && $request['maxRows'] === 10
            && $request['orderby'] === 'relevance');
    }

    public function test_finds_no_places_when_geonames_has_none(): void
    {
        Http::fake(['geonames.test/searchJSON*' => Http::response(['totalResultsCount' => 0, 'geonames' => []])]);

        $places = app(GeoNamesService::class)->searchPlaces('Xyzzy');

        $this->assertTrue($places->isEmpty());
    }

    public function test_finds_a_town_or_village_by_its_geonames_id(): void
    {
        Http::fake(['geonames.test/getJSON*' => Http::response(self::TOTNES)]);

        $place = app(GeoNamesService::class)->findPlace(2635610);

        $this->assertEquals(Place::fromGeoNames(self::TOTNES), $place);
        Http::assertSent(fn (Request $request): bool => $request['geonameId'] === 2635610);
    }

    public function test_finds_nothing_for_a_geonames_feature_that_is_not_a_town_or_village(): void
    {
        Http::fake(['geonames.test/getJSON*' => Http::response([...self::TOTNES, 'name' => 'Dartmoor', 'fcl' => 'T'])]);

        $this->assertNull(app(GeoNamesService::class)->findPlace(2651347));
    }
}
