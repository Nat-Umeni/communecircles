<?php

namespace App\Services\GeoNames;

use App\Exceptions\GeoNamesException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Talks to the GeoNames web services (config/geonames.php). GeoNamesService decides what to ask.
 */
class GeoNamesClient
{
    /**
     * Places hardly ever change, and the free account allows 1,000 requests an hour, so answers are kept for a day.
     */
    private const int REMEMBER_SECONDS = 24 * 60 * 60;

    private const int TIMEOUT_SECONDS = 5;

    /**
     * Ask a GeoNames web service, e.g. get('searchJSON', ['name_startsWith' => 'Totn']); the username is added here.
     * An answer is remembered, so the same question isn't sent again for a day. Failures throw and aren't remembered.
     *
     * @param  array<string, scalar>  $query
     * @return array<string, mixed>
     *
     * @throws GeoNamesException
     */
    public function get(string $endpoint, array $query = []): array
    {
        ksort($query);

        return Cache::remember(
            'geonames:'.$endpoint.'?'.http_build_query($query),
            self::REMEMBER_SECONDS,
            fn (): array => $this->ask($endpoint, $query),
        );
    }

    /**
     * @param  array<string, scalar>  $query
     * @return array<string, mixed>
     *
     * @throws GeoNamesException
     */
    private function ask(string $endpoint, array $query): array
    {
        try {
            $answer = Http::baseUrl(config('geonames.base_url'))
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get($endpoint, [...$query, 'username' => config('geonames.username')])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            // GeoNames says why it refused a request, e.g. an unknown username, in the same "status" format.
            throw new GeoNamesException($exception->response->json('status.message') ?? 'GeoNames could not be reached.', previous: $exception);
        } catch (ConnectionException $exception) {
            throw new GeoNamesException('GeoNames could not be reached.', previous: $exception);
        }

        // NOTE: GeoNames reports errors such as a used-up hourly limit as a "status" in a normal 200 answer.
        if (! is_array($answer) || isset($answer['status'])) {
            throw new GeoNamesException($answer['status']['message'] ?? 'GeoNames sent an answer that is not JSON.');
        }

        return $answer;
    }
}
