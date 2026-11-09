<?php

namespace App\DTO\GeoNames;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A town, village or hamlet from GeoNames.
 *
 * @implements Arrayable<string, int|float|string|null>
 */
final readonly class Place implements Arrayable
{
    public function __construct(
        public int $geonamesId,
        public string $name,
        /** The first-level region, e.g. "England"; GeoNames has none for some small countries. */
        public ?string $region,
        public string $countryCode,
        public string $countryName,
        public float $latitude,
        public float $longitude,
    ) {}

    /**
     * Read a place from one GeoNames result, as searchJSON and getJSON send it.
     *
     * @param  array{geonameId: int, name: string, adminName1?: string, countryCode: string, countryName: string, lat: string, lng: string}  $result
     */
    public static function fromGeoNames(array $result): self
    {
        return new self(
            geonamesId: (int) $result['geonameId'],
            name: $result['name'],
            region: ($result['adminName1'] ?? '') !== '' ? $result['adminName1'] : null,
            countryCode: $result['countryCode'],
            countryName: $result['countryName'],
            latitude: (float) $result['lat'],
            longitude: (float) $result['lng'],
        );
    }

    /**
     * @return array{geonames_id: int, name: string, region: string|null, country_code: string, country_name: string, latitude: float, longitude: float}
     */
    public function toArray(): array
    {
        return [
            'geonames_id' => $this->geonamesId,
            'name' => $this->name,
            'region' => $this->region,
            'country_code' => $this->countryCode,
            'country_name' => $this->countryName,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
