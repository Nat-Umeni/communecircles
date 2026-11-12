<?php

namespace Tests\Unit\DTO;

use App\DTO\Coordinates;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CoordinatesTest extends TestCase
{
    public function test_is_a_spot_on_the_globe_up_to_its_edges(): void
    {
        $spot = new Coordinates(50.4319, -3.6863);

        $this->assertSame([50.4319, -3.6863], [$spot->latitude, $spot->longitude]);
        $this->assertSame([90.0, -180.0], [(new Coordinates(90, -180))->latitude, (new Coordinates(90, -180))->longitude]);
        $this->assertSame([-90.0, 180.0], [(new Coordinates(-90, 180))->latitude, (new Coordinates(-90, 180))->longitude]);
    }

    #[DataProvider('offTheGlobe')]
    public function test_refuses_a_spot_off_the_globe(float $latitude, float $longitude): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Coordinates($latitude, $longitude);
    }

    public function test_turns_into_what_the_pages_show(): void
    {
        $this->assertSame(['latitude' => 50.4319, 'longitude' => -3.6863], (new Coordinates(50.4319, -3.6863))->toArray());
    }

    /**
     * @return array<string, array{float, float}>
     */
    public static function offTheGlobe(): array
    {
        return [
            'north of the pole' => [90.1, 0],
            'south of the pole' => [-90.1, 0],
            'east of the date line' => [0, 180.1],
            'west of the date line' => [0, -180.1],
        ];
    }
}
