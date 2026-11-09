<?php

namespace App\Services\GeoNames;

use App\DTO\GeoNames\Place;
use App\Exceptions\GeoNamesException;
use Illuminate\Support\Collection;

/**
 * The GeoNames lookups the app needs, each one question to the GeoNamesClient. Add new lookups here.
 */
class GeoNamesService
{
    /**
     * GeoNames' feature class for populated places: cities, towns, villages, hamlets, farm villages and localities.
     */
    private const string POPULATED_PLACE = 'P';

    private const int SEARCH_RESULTS = 10;

    public function __construct(private readonly GeoNamesClient $client) {}

    /**
     * Towns and villages whose name starts with the text, best matches first, e.g. for "Totn".
     *
     * @return Collection<int, Place>
     *
     * @throws GeoNamesException
     */
    public function searchPlaces(string $text): Collection
    {
        $answer = $this->client->get('searchJSON', [
            'name_startsWith' => $text,
            'featureClass' => self::POPULATED_PLACE,
            'maxRows' => self::SEARCH_RESULTS,
            // NOTE: GeoNames only orders by relevance for name_startsWith searches.
            'orderby' => 'relevance',
        ]);

        return collect($answer['geonames'] ?? [])
            ->map(fn (array $result): Place => Place::fromGeoNames($result))
            ->values();
    }

    /**
     * The town or village with this GeoNames id, or null when the id belongs to another kind of feature, e.g. a hill.
     *
     * @throws GeoNamesException
     */
    public function findPlace(int $geonamesId): ?Place
    {
        $answer = $this->client->get('getJSON', ['geonameId' => $geonamesId]);

        return ($answer['fcl'] ?? null) === self::POPULATED_PLACE ? Place::fromGeoNames($answer) : null;
    }
}
