<?php

namespace App\DTO;

use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * An exact spot on the globe, e.g. a circle's own land, as opposed to the GeoNames town it's near.
 *
 * @implements Arrayable<string, float>
 */
final readonly class Coordinates implements Arrayable
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
        if (abs($latitude) > 90 || abs($longitude) > 180) {
            throw new InvalidArgumentException("[{$latitude}, {$longitude}] isn't on the globe.");
        }
    }

    /**
     * @return array{latitude: float, longitude: float}
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
