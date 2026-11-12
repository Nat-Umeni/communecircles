<?php

namespace App\DTO;

use App\DTO\GeoNames\Place;
use App\Services\Countries;
use Illuminate\Contracts\Support\Arrayable;
use InvalidArgumentException;

/**
 * Where a circle is: the circle's own name for its place, the nearest town or village from GeoNames, or both, and
 * optionally its own exact spot. A name alone is for places GeoNames doesn't know; then the only map position is the
 * exact spot, if it has one.
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class Location implements Arrayable
{
    public function __construct(
        /** What the circle calls its place, e.g. "Fernhill Farm". */
        public ?string $name,
        /** The nearest town or village from GeoNames, which gives the region and map position. */
        public ?Place $place,
        public string $countryCode,
        public string $countryName,
        /** The circle's own exact spot (its land), if it gave one; only its members and staff see it. */
        public ?Coordinates $coordinates = null,
        /** Whether this copy leaves out an exact spot the location has (withoutCoordinates()). */
        public bool $coordinatesHidden = false,
    ) {
        if ($name === null && $place === null) {
            throw new InvalidArgumentException('A location needs a name or a place.');
        }
    }

    /**
     * At (or, with a name of its own, near) a GeoNames town or village.
     */
    public static function at(Place $place, ?string $name = null, ?Coordinates $coordinates = null): self
    {
        return new self($name, $place, $place->countryCode, $place->countryName, $coordinates);
    }

    /**
     * Just a name in a country, for a place GeoNames doesn't know.
     */
    public static function named(string $name, string $countryCode, ?Coordinates $coordinates = null): self
    {
        $countryName = Countries::name($countryCode) ?? throw new InvalidArgumentException("[{$countryCode}] isn't a country code.");

        return new self($name, null, $countryCode, $countryName, $coordinates);
    }

    /**
     * The same location without its exact spot, for someone who may not see it; it still says whether there is one.
     */
    public function withoutCoordinates(): self
    {
        return new self($this->name, $this->place, $this->countryCode, $this->countryName, null, $this->coordinates !== null);
    }

    /**
     * @return array{name: string|null, place: array<string, mixed>|null, country_code: string, country_name: string, coordinates: array{latitude: float, longitude: float}|null, coordinates_hidden: bool}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'place' => $this->place?->toArray(),
            'country_code' => $this->countryCode,
            'country_name' => $this->countryName,
            'coordinates' => $this->coordinates?->toArray(),
            'coordinates_hidden' => $this->coordinatesHidden,
        ];
    }
}
