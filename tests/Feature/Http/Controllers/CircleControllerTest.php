<?php

namespace Tests\Feature\Http\Controllers;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Enums\CircleRole;
use App\Enums\Role;
use App\Models\Circle;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CircleControllerTest extends TestCase
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

    public function test_index_renders_the_circles_page(): void
    {
        $response = $this->get(route('circles.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->component('circles/Index'));
    }

    public function test_lists_circles_in_name_order_with_their_member_counts(): void
    {
        $exchange = Circle::factory()->create(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);
        $exchange->members()->attach(User::factory()->count(2)->create(), ['role' => CircleRole::Member]);
        Circle::factory()->create(['name' => 'Allotment']);

        $response = $this->get(route('circles.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('circles.data.0.name', 'Allotment')
            ->where('circles.data.1', [
                'public_id' => $exchange->public_id,
                'name' => 'Exchange',
                'description' => 'Share skills and lend tools.',
                'members_count' => 2,
                'location' => null,
            ]));
    }

    public function test_lists_24_circles_a_page(): void
    {
        Circle::factory()->count(25)->create();

        $response = $this->get(route('circles.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('circles.data', 24)
            ->where('circles.total', 25));
    }

    public function test_finds_circles_whose_name_contains_the_search_and_keeps_it_for_the_search_box(): void
    {
        Circle::factory()->create(['name' => 'Exchange']);
        Circle::factory()->create(['name' => 'Allotment']);

        $response = $this->get(route('circles.index', ['search' => ' xch ']));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('circles.data', 1)
            ->where('circles.data.0.name', 'Exchange')
            ->where('filters.search', 'xch'));
    }

    public function test_lists_where_each_circle_is(): void
    {
        Circle::factory()->in($this->totnes())->create();

        $response = $this->get(route('circles.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('circles.data.0.location', Location::at($this->totnes())->toArray()));
    }

    public function test_the_home_page_shows_the_circle_list_too(): void
    {
        Circle::factory()->create(['name' => 'Exchange']);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('circles.data.0.name', 'Exchange')
            ->where('filters.search', ''));
    }

    public function test_signed_in_members_can_browse_circles_too(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('circles.index'));

        $response->assertOk();
    }

    public function test_show_renders_the_circle_page(): void
    {
        $response = $this->get(route('circles.show', Circle::factory()->create()));

        $response->assertInertia(fn (AssertableInertia $page) => $page->component('circles/Show'));
    }

    public function test_shows_guests_a_circles_public_details_and_nothing_they_can_do(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);
        $this->memberOf($circle, CircleRole::Owner);

        $response = $this->get(route('circles.show', $circle));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('circle', [
                'public_id' => $circle->public_id,
                'name' => 'Exchange',
                'description' => 'Share skills and lend tools.',
                'members_count' => 1,
                'location' => null,
            ])
            ->where('can', ['view_channels' => false, 'request_to_join' => false, 'update' => false, 'delete' => false]));
    }

    public function test_shows_where_the_circle_is(): void
    {
        $circle = Circle::factory()->in($this->totnes())->create();

        $response = $this->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('circle.location', Location::at($this->totnes())->toArray()));
    }

    public function test_tells_the_owner_they_can_see_inside_and_manage_their_circle(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);

        $response = $this->actingAs($owner)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can', ['view_channels' => true, 'request_to_join' => false, 'update' => true, 'delete' => false]));
    }

    public function test_tells_someone_outside_the_circle_they_can_ask_to_join(): void
    {
        $circle = Circle::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can', ['view_channels' => false, 'request_to_join' => true, 'update' => false, 'delete' => false]));
    }

    public function test_tells_staff_they_can_also_delete_the_circle(): void
    {
        $circle = Circle::factory()->create();
        $staff = User::factory()->create(['role' => Role::SuperAdmin]);

        $response = $this->actingAs($staff)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('can.delete', true));
    }

    public function test_an_unknown_circle_is_not_found(): void
    {
        $response = $this->get(route('circles.show', 'abcdefghij'));

        $response->assertNotFound();
    }

    public function test_create_renders_the_new_circle_page(): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->get(route('circles.create'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->component('circles/Create'));
    }

    public function test_opens_the_new_circle_page_with_no_reason_to_wait_for_a_member_who_can_create(): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->get(route('circles.create'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page->where('cannot_create_reason', null));
    }

    public function test_tells_a_brand_new_account_on_the_new_circle_page_why_it_cannot_create_one_yet(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('circles.create'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('cannot_create_reason', 'New accounts can create a circle 5 minutes after joining.'));
    }

    public function test_stores_the_circle_with_its_creator_as_owner_and_opens_it(): void
    {
        $creator = $this->memberWhoCanCreate();

        $response = $this->actingAs($creator)->post(route('circles.store'), [
            'name' => 'Exchange',
            'description' => 'Share skills and lend tools.',
        ]);

        $circle = Circle::sole();
        $response->assertRedirect(route('circles.show', $circle));
        $this->assertSame('Exchange', $circle->name);
        $this->assertSame('Share skills and lend tools.', $circle->description);
        $this->assertSame([$creator->id], $circle->members->modelKeys());
        $this->assertSame(CircleRole::Owner, $circle->membershipOf($creator)->role);
    }

    public function test_edit_renders_the_edit_page(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);

        $response = $this->actingAs($owner)->get(route('circles.edit', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->component('circles/Edit'));
    }

    public function test_gives_the_edit_page_the_circles_details(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);
        $owner = $this->memberOf($circle, CircleRole::Owner);

        $response = $this->actingAs($owner)->get(route('circles.edit', $circle));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('circle', [
                'public_id' => $circle->public_id,
                'name' => 'Exchange',
                'description' => 'Share skills and lend tools.',
                'location' => null,
            ]));
    }

    public function test_gives_the_edit_page_the_circles_place_so_it_can_be_kept_or_changed(): void
    {
        $circle = Circle::factory()->in($this->totnes())->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);

        $response = $this->actingAs($owner)->get(route('circles.edit', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('circle.location', Location::at($this->totnes())->toArray()));
    }

    public function test_plain_members_cannot_open_the_edit_page(): void
    {
        $circle = Circle::factory()->create();
        $member = $this->memberOf($circle, CircleRole::Member);

        $response = $this->actingAs($member)->get(route('circles.edit', $circle));

        $response->assertForbidden();
    }

    public function test_updates_the_circle_and_opens_it(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);

        $response = $this->actingAs($owner)->put(route('circles.update', $circle), [
            'name' => 'Tool library',
            'description' => 'Borrow tools from your neighbours.',
        ]);

        $response->assertRedirect(route('circles.show', $circle));
        $circle->refresh();
        $this->assertSame('Tool library', $circle->name);
        $this->assertSame('Borrow tools from your neighbours.', $circle->description);
    }

    public function test_stores_the_circle_at_the_chosen_place(): void
    {
        Http::fake(['geonames.test/getJSON*' => Http::response(self::TOTNES)]);

        $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), [
            'name' => 'Exchange',
            'description' => 'Share skills and lend tools.',
            'geonames_id' => 2635610,
        ]);

        $this->assertEquals(Location::at($this->totnes()), Circle::sole()->location());
    }

    public function test_moves_the_circle_to_a_newly_chosen_place(): void
    {
        Http::fake(['geonames.test/getJSON*' => Http::response(self::TOTNES)]);
        $circle = Circle::factory()->in(new Place(2651513, 'Dartington', 'England', 'GB', 'United Kingdom', 50.45, -3.7))->create();

        $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), [
            'name' => $circle->name,
            'description' => $circle->description,
            'geonames_id' => 2635610,
        ]);

        $this->assertEquals(Location::at($this->totnes()), $circle->fresh()->location());
    }

    public function test_keeps_the_circles_place_when_the_same_one_is_sent_back(): void
    {
        $circle = Circle::factory()->in($this->totnes())->create();

        $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), [
            'name' => 'Tool library',
            'description' => $circle->description,
            'geonames_id' => 2635610,
        ]);

        $circle->refresh();
        $this->assertSame('Tool library', $circle->name);
        $this->assertEquals(Location::at($this->totnes()), $circle->location());
    }

    public function test_clears_the_circles_place_when_none_is_chosen(): void
    {
        $circle = Circle::factory()->in($this->totnes())->create();

        $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), [
            'name' => $circle->name,
            'description' => $circle->description,
            'geonames_id' => null,
        ]);

        $this->assertNull($circle->fresh()->location());
    }

    public function test_stores_a_circle_with_a_name_of_its_own_in_a_country_when_geonames_has_no_place_for_it(): void
    {
        $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), [
            'name' => 'Exchange',
            'description' => 'Share skills and lend tools.',
            'location_name' => 'Fernhill Farm',
            'country_code' => 'GB',
        ]);

        $this->assertEquals(Location::named('Fernhill Farm', 'GB'), Circle::sole()->location());
    }

    public function test_stores_a_circle_with_a_name_of_its_own_near_the_chosen_place(): void
    {
        Http::fake(['geonames.test/getJSON*' => Http::response(self::TOTNES)]);

        $this->actingAs($this->memberWhoCanCreate())->post(route('circles.store'), [
            'name' => 'Exchange',
            'description' => 'Share skills and lend tools.',
            'location_name' => 'Fernhill Farm',
            'geonames_id' => 2635610,
        ]);

        $this->assertEquals(Location::at($this->totnes(), 'Fernhill Farm'), Circle::sole()->location());
    }

    public function test_renames_the_circles_place_and_keeps_the_town_it_is_near(): void
    {
        $circle = Circle::factory()->in($this->totnes())->create();

        $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), [
            'name' => $circle->name,
            'description' => $circle->description,
            'location_name' => 'Fernhill Farm',
            'geonames_id' => 2635610,
        ]);

        $this->assertEquals(Location::at($this->totnes(), 'Fernhill Farm'), $circle->fresh()->location());
    }

    public function test_the_new_circle_page_lists_the_countries_for_a_place_geonames_does_not_know(): void
    {
        $response = $this->actingAs($this->memberWhoCanCreate())->get(route('circles.create'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('countries', 250)
            ->where('countries', fn ($countries): bool => $countries->contains(['value' => 'GB', 'label' => 'United Kingdom'])));
    }

    public function test_the_edit_page_lists_the_countries_too(): void
    {
        $circle = Circle::factory()->create();

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->get(route('circles.edit', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('countries', 250)
            ->where('countries', fn ($countries): bool => $countries->contains(['value' => 'LI', 'label' => 'Liechtenstein'])));
    }

    public function test_staff_delete_the_circle_and_go_back_to_the_list(): void
    {
        $circle = Circle::factory()->create();
        $staff = User::factory()->create(['role' => Role::SuperAdmin]);

        $response = $this->actingAs($staff)->delete(route('circles.destroy', $circle));

        $response->assertRedirect(route('circles.index'));
        $this->assertModelMissing($circle);
    }

    /**
     * @param  Closure(Circle): string  $url
     */
    #[DataProvider('membersOnlyActions')]
    public function test_guests_are_sent_to_log_in_before_making_or_changing_circles(string $method, Closure $url): void
    {
        $circle = Circle::factory()->create();

        $response = $this->{$method}($url($circle));

        $response->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{string, Closure(Circle): string}>
     */
    public static function membersOnlyActions(): array
    {
        return [
            'the new circle page' => ['get', fn (): string => route('circles.create')],
            'creating' => ['post', fn (): string => route('circles.store')],
            'the edit page' => ['get', fn (Circle $circle): string => route('circles.edit', $circle)],
            'updating' => ['put', fn (Circle $circle): string => route('circles.update', $circle)],
            'deleting' => ['delete', fn (Circle $circle): string => route('circles.destroy', $circle)],
        ];
    }

    /**
     * Totnes, as a GeoNames place.
     */
    private function totnes(): Place
    {
        return Place::fromGeoNames(self::TOTNES);
    }

    /**
     * A new user who belongs to the circle with the given role.
     */
    private function memberOf(Circle $circle, CircleRole $role): User
    {
        $user = User::factory()->create();
        $circle->members()->attach($user, ['role' => $role]);

        return $user;
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
