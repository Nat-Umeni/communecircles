<?php

namespace Tests\Unit\DTO\GeoNames;

use App\DTO\GeoNames\Place;
use PHPUnit\Framework\TestCase;

class PlaceTest extends TestCase
{
    public function test_reads_a_place_from_a_geonames_result(): void
    {
        $place = Place::fromGeoNames([
            'geonameId' => 2635610,
            'name' => 'Totnes',
            'adminName1' => 'England',
            'countryCode' => 'GB',
            'countryName' => 'United Kingdom',
            'lat' => '50.43107',
            'lng' => '-3.6843',
            'fcl' => 'P',
        ]);

        $this->assertSame(2635610, $place->geonamesId);
        $this->assertSame('Totnes', $place->name);
        $this->assertSame('England', $place->region);
        $this->assertSame('GB', $place->countryCode);
        $this->assertSame('United Kingdom', $place->countryName);
        $this->assertSame(50.43107, $place->latitude);
        $this->assertSame(-3.6843, $place->longitude);
    }

    public function test_has_no_region_when_geonames_gives_an_empty_one(): void
    {
        $place = Place::fromGeoNames([
            'geonameId' => 3042030,
            'name' => 'Vaduz',
            'adminName1' => '',
            'countryCode' => 'LI',
            'countryName' => 'Liechtenstein',
            'lat' => '47.14151',
            'lng' => '9.52154',
        ]);

        $this->assertNull($place->region);
    }

    public function test_has_no_region_when_geonames_leaves_it_out(): void
    {
        $place = Place::fromGeoNames([
            'geonameId' => 3042030,
            'name' => 'Vaduz',
            'countryCode' => 'LI',
            'countryName' => 'Liechtenstein',
            'lat' => '47.14151',
            'lng' => '9.52154',
        ]);

        $this->assertNull($place->region);
    }

    public function test_turns_into_the_array_the_place_search_sends(): void
    {
        $place = new Place(2635610, 'Totnes', 'England', 'GB', 'United Kingdom', 50.43107, -3.6843);

        $this->assertSame([
            'geonames_id' => 2635610,
            'name' => 'Totnes',
            'region' => 'England',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'latitude' => 50.43107,
            'longitude' => -3.6843,
        ], $place->toArray());
    }
}
