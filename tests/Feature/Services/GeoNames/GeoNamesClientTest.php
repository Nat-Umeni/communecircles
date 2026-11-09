<?php

namespace Tests\Feature\Services\GeoNames;

use App\Exceptions\GeoNamesException;
use App\Services\GeoNames\GeoNamesClient;
use Closure;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\StrayRequestException;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GeoNamesClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['geonames.username' => 'commune-test', 'geonames.base_url' => 'https://geonames.test']);
    }

    public function test_asks_the_configured_host_with_the_username_and_returns_the_answer(): void
    {
        Http::fake(['geonames.test/*' => Http::response(['geonames' => [['name' => 'Totnes']]])]);

        $answer = app(GeoNamesClient::class)->get('searchJSON', ['name_startsWith' => 'Totn']);

        $this->assertSame(['geonames' => [['name' => 'Totnes']]], $answer);
        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://geonames.test/searchJSON?')
            && $request['name_startsWith'] === 'Totn'
            && $request['username'] === 'commune-test');
    }

    public function test_never_reaches_the_real_geonames_from_a_test(): void
    {
        $this->expectException(StrayRequestException::class);

        app(GeoNamesClient::class)->get('searchJSON', ['name_startsWith' => 'Totn']);
    }

    public function test_remembers_an_answer_so_the_same_question_is_only_sent_once(): void
    {
        Http::fake(['geonames.test/*' => Http::response(['geonames' => [['name' => 'Totnes']]])]);
        $client = app(GeoNamesClient::class);

        $client->get('searchJSON', ['name_startsWith' => 'Totn', 'maxRows' => 10]);
        $answer = $client->get('searchJSON', ['maxRows' => 10, 'name_startsWith' => 'Totn']);

        $this->assertSame(['geonames' => [['name' => 'Totnes']]], $answer);
        Http::assertSentCount(1);
    }

    public function test_sends_a_different_question_again(): void
    {
        Http::fake(['geonames.test/*' => Http::response(['geonames' => []])]);
        $client = app(GeoNamesClient::class);

        $client->get('searchJSON', ['name_startsWith' => 'Totn']);
        $client->get('searchJSON', ['name_startsWith' => 'Dart']);
        $client->get('getJSON', ['name_startsWith' => 'Totn']);

        Http::assertSentCount(3);
    }

    public function test_remembers_an_answer_for_a_day(): void
    {
        $this->freezeSecond();
        Http::fake(['geonames.test/*' => Http::response(['geonames' => []])]);
        $client = app(GeoNamesClient::class);
        $client->get('searchJSON', ['name_startsWith' => 'Totn']);

        $this->travel(24 * 60 * 60 - 1)->seconds();
        $client->get('searchJSON', ['name_startsWith' => 'Totn']);
        Http::assertSentCount(1);

        $this->travel(1)->seconds();
        $client->get('searchJSON', ['name_startsWith' => 'Totn']);
        Http::assertSentCount(2);
    }

    /**
     * @param  Closure(): mixed  $failure
     */
    #[DataProvider('failures')]
    public function test_throws_when_geonames_cannot_answer(Closure $failure, string $message): void
    {
        Http::fake(['geonames.test/*' => $failure()]);

        $this->expectException(GeoNamesException::class);
        $this->expectExceptionMessage($message);

        app(GeoNamesClient::class)->get('searchJSON', ['name_startsWith' => 'Totn']);
    }

    public function test_does_not_remember_a_failure(): void
    {
        Http::fakeSequence('geonames.test/*')
            ->push(['status' => ['message' => 'the server is busy', 'value' => 22]])
            ->push(['geonames' => [['name' => 'Totnes']]]);
        $client = app(GeoNamesClient::class);

        try {
            $client->get('searchJSON', ['name_startsWith' => 'Totn']);
        } catch (GeoNamesException) {
            // The first answer is an error; the point is what the second call does.
        }
        $answer = $client->get('searchJSON', ['name_startsWith' => 'Totn']);

        $this->assertSame(['geonames' => [['name' => 'Totnes']]], $answer);
        Http::assertSentCount(2);
    }

    /**
     * @return array<string, array{Closure(): mixed, string}>
     */
    public static function failures(): array
    {
        return [
            'an error in the answer' => [
                fn () => Http::response(['status' => ['message' => 'the hourly limit has been exceeded', 'value' => 19]]),
                'the hourly limit has been exceeded',
            ],
            'a refused request, with GeoNames saying why' => [
                fn () => Http::response(['status' => ['message' => 'user does not exist.', 'value' => 10]], 401),
                'user does not exist.',
            ],
            'an answer that is not JSON' => [fn () => Http::response('<html>Down for maintenance</html>'), 'GeoNames sent an answer that is not JSON.'],
            'a server error' => [fn () => Http::response('Bad gateway', 502), 'GeoNames could not be reached.'],
            'no connection' => [fn () => Http::failedConnection(), 'GeoNames could not be reached.'],
        ];
    }
}
