<?php

namespace Tests\Feature\Http\Requests\Circle;

use App\DTO\GeoNames\Place;
use App\Enums\CircleRole;
use App\Exceptions\GeoNamesException;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * NOTE: CircleRequest is abstract, so these run through creating a circle (StoreCircleRequest)
 * and, for what only happens when editing, updating one (UpdateCircleRequest).
 */
class CircleRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

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

    public function test_looks_the_chosen_place_up_in_geonames(): void
    {
        Http::fake(['geonames.test/getJSON*' => Http::response(self::TOTNES)]);

        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['geonames_id' => 2635610]));

        $response->assertValid()->assertRedirect();
        Http::assertSent(fn (Request $request): bool => $request['geonameId'] === 2635610);
    }

    /**
     * @param  array<string, mixed>  $noPlace
     */
    #[DataProvider('noPlaceChosen')]
    public function test_needs_no_lookup_when_no_place_is_chosen(array $noPlace): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details($noPlace));

        $response->assertValid()->assertRedirect();
        Http::assertNothingSent();
    }

    public function test_rejects_a_geonames_id_that_is_not_a_town_or_village(): void
    {
        Http::fake(['geonames.test/getJSON*' => Http::response([...self::TOTNES, 'name' => 'Dartmoor', 'fcl' => 'T'])]);

        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['geonames_id' => 2651347]));

        $response->assertInvalid(['geonames_id' => 'Choose a town or village from the list, or leave the location empty.']);
        $this->assertDatabaseEmpty('circles');
    }

    public function test_says_the_place_cannot_be_checked_and_reports_why_when_geonames_cannot_answer(): void
    {
        Exceptions::fake();
        Http::fake(['geonames.test/*' => Http::response(['status' => ['message' => 'the hourly limit has been exceeded', 'value' => 19]])]);

        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['geonames_id' => 2635610]));

        $response->assertInvalid(['geonames_id' => "The place can't be checked right now. Try again in a little while, or leave the location empty."]);
        Exceptions::assertReported(GeoNamesException::class);
        $this->assertDatabaseEmpty('circles');
    }

    #[DataProvider('invalidGeoNamesIds')]
    public function test_rejects_a_geonames_id_that_is_not_a_positive_whole_number(mixed $geonamesId): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['geonames_id' => $geonamesId]));

        $response->assertInvalid(['geonames_id']);
        Http::assertNothingSent();
    }

    public function test_does_not_look_the_place_up_while_the_other_details_are_invalid(): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['name' => '', 'geonames_id' => 2635610]));

        $response->assertInvalid(['name']);
        $response->assertValid(['geonames_id']);
        Http::assertNothingSent();
    }

    public function test_does_not_look_the_circles_current_place_up_again_when_editing(): void
    {
        $circle = Circle::factory()->in(new Place(2635610, 'Totnes', 'England', 'GB', 'United Kingdom', 50.43107, -3.6843))->create();

        $response = $this->actingAs($this->ownerOf($circle))->put(route('circles.update', $circle), $this->details(['geonames_id' => 2635610]));

        $response->assertValid()->assertRedirect();
        Http::assertNothingSent();
    }

    public function test_needs_no_lookup_when_the_place_is_cleared_while_editing(): void
    {
        $circle = Circle::factory()->in(new Place(2635610, 'Totnes', 'England', 'GB', 'United Kingdom', 50.43107, -3.6843))->create();

        $response = $this->actingAs($this->ownerOf($circle))->put(route('circles.update', $circle), $this->details(['geonames_id' => null]));

        $response->assertValid()->assertRedirect();
        Http::assertNothingSent();
    }

    public function test_looks_a_newly_chosen_place_up_when_editing(): void
    {
        Http::fake(['geonames.test/getJSON*' => Http::response(self::TOTNES)]);
        $circle = Circle::factory()->in(new Place(2651513, 'Dartington', 'England', 'GB', 'United Kingdom', 50.45, -3.7))->create();

        $response = $this->actingAs($this->ownerOf($circle))->put(route('circles.update', $circle), $this->details(['geonames_id' => 2635610]));

        $response->assertValid()->assertRedirect();
        Http::assertSent(fn (Request $request): bool => $request['geonameId'] === 2635610);
    }

    public function test_a_name_of_its_own_needs_a_country_when_no_place_is_chosen(): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['location_name' => 'Fernhill Farm']));

        $response->assertInvalid(['country_code' => 'Choose the country the place is in.']);
        $this->assertDatabaseEmpty('circles');
    }

    public function test_rejects_a_country_code_that_is_not_a_country(): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['location_name' => 'Fernhill Farm', 'country_code' => 'ZZ']));

        $response->assertInvalid(['country_code' => 'The selected country is invalid.']);
    }

    public function test_accepts_a_name_of_its_own_in_a_country_without_asking_geonames(): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['location_name' => 'Fernhill Farm', 'country_code' => 'GB']));

        $response->assertValid()->assertRedirect();
        Http::assertNothingSent();
    }

    public function test_needs_no_country_for_a_name_of_its_own_near_a_chosen_place(): void
    {
        Http::fake(['geonames.test/getJSON*' => Http::response(self::TOTNES)]);

        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['location_name' => 'Fernhill Farm', 'geonames_id' => 2635610]));

        $response->assertValid()->assertRedirect();
    }

    public function test_rejects_a_name_of_its_own_over_255_characters(): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), $this->details(['location_name' => str_repeat('a', 256), 'country_code' => 'GB']));

        $response->assertInvalid(['location_name' => 'The place name field must not be greater than 255 characters.']);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function noPlaceChosen(): array
    {
        return [
            'left out' => [[]],
            'left empty' => [['geonames_id' => '']],
        ];
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidGeoNamesIds(): array
    {
        return [
            'text' => ['Totnes'],
            'a fraction' => [2635610.5],
            'zero' => [0],
            'negative' => [-2635610],
        ];
    }

    /**
     * Valid circle details, with any of them replaced.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function details(array $overrides = []): array
    {
        return ['name' => 'Exchange', 'description' => 'Share skills and lend tools.', ...$overrides];
    }

    /**
     * A new user who owns the circle.
     */
    private function ownerOf(Circle $circle): User
    {
        $owner = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);

        return $owner;
    }

    /**
     * A member whose account is old enough to create circles.
     */
    private function memberWhoCanCreate(): User
    {
        $this->freezeSecond();
        $user = User::factory()->create();
        $this->travel(5)->minutes();

        return $user;
    }
}
