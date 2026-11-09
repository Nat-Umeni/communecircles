<?php

namespace Tests\Unit\DTO;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LocationTest extends TestCase
{
    public function test_is_at_a_geonames_place_with_its_country(): void
    {
        $location = Location::at($this->totnes());

        $this->assertNull($location->name);
        $this->assertEquals($this->totnes(), $location->place);
        $this->assertSame('GB', $location->countryCode);
        $this->assertSame('United Kingdom', $location->countryName);
    }

    public function test_can_have_its_own_name_near_a_geonames_place(): void
    {
        $location = Location::at($this->totnes(), 'Fernhill Farm');

        $this->assertSame('Fernhill Farm', $location->name);
        $this->assertEquals($this->totnes(), $location->place);
    }

    public function test_can_be_just_a_name_in_a_country_when_geonames_has_no_place_for_it(): void
    {
        $location = Location::named('Fernhill Farm', 'GB');

        $this->assertSame('Fernhill Farm', $location->name);
        $this->assertNull($location->place);
        $this->assertSame('GB', $location->countryCode);
        $this->assertSame('United Kingdom', $location->countryName);
    }

    public function test_refuses_a_country_code_that_is_not_a_country(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Location::named('Fernhill Farm', 'ZZ');
    }

    public function test_needs_a_name_or_a_place(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Location(null, null, 'GB', 'United Kingdom');
    }

    public function test_turns_into_what_the_circle_pages_show(): void
    {
        $this->assertSame([
            'name' => 'Fernhill Farm',
            'place' => $this->totnes()->toArray(),
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
        ], Location::at($this->totnes(), 'Fernhill Farm')->toArray());

        $this->assertSame([
            'name' => 'Fernhill Farm',
            'place' => null,
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
        ], Location::named('Fernhill Farm', 'GB')->toArray());
    }

    private function totnes(): Place
    {
        return new Place(2635610, 'Totnes', 'England', 'GB', 'United Kingdom', 50.43107, -3.6843);
    }
}
