<?php

namespace Tests\Feature\Http\Resources;

use App\DTO\Coordinates;
use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Enums\Circle\CircleRole;
use App\Enums\User\Role;
use App\Http\Resources\CircleResource;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CircleResourceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_sends_the_circles_details_and_where_it_is(): void
    {
        $circle = Circle::factory()->create(['name' => 'Seed Exchange', 'description' => 'Swapping seeds that grow well here.']);
        $circle->locate(new Location('Fernhill Farm', null, 'GB', 'United Kingdom'));
        $circle->save();

        $this->assertSame([
            'public_id' => $circle->public_id,
            'name' => 'Seed Exchange',
            'description' => 'Swapping seeds that grow well here.',
            'location' => ['name' => 'Fernhill Farm', 'place' => null, 'country_code' => 'GB', 'country_name' => 'United Kingdom', 'coordinates' => null, 'coordinates_hidden' => false],
        ], $this->sent($circle->fresh()));
    }

    public function test_sends_its_exact_spot_to_its_members(): void
    {
        $circle = $this->withExactSpot();
        $member = User::factory()->create();
        $circle->members()->attach($member, ['role' => CircleRole::Member]);
        $this->actingAs($member);

        $this->assertSame(['latitude' => 50.4319, 'longitude' => -3.6863], $this->sent($circle)['location']['coordinates']);
    }

    public function test_sends_its_exact_spot_to_staff(): void
    {
        $this->actingAs(User::factory()->create(['role' => Role::Admin]));

        $this->assertSame(['latitude' => 50.4319, 'longitude' => -3.6863], $this->sent($this->withExactSpot())['location']['coordinates']);
    }

    public function test_keeps_its_exact_spot_from_everyone_else_saying_there_is_one(): void
    {
        $circle = $this->withExactSpot();
        $this->actingAs(User::factory()->create());
        $location = $this->sent($circle)['location'];

        $this->assertNull($location['coordinates']);
        $this->assertTrue($location['coordinates_hidden']);
        $this->assertSame('Totnes', $location['place']['name']);
    }

    public function test_keeps_its_exact_spot_from_guests(): void
    {
        $this->assertTrue($this->sent($this->withExactSpot())['location']['coordinates_hidden']);
    }

    public function test_does_not_look_up_the_viewers_membership_for_a_circle_without_an_exact_spot(): void
    {
        $circle = Circle::factory()->in(new Place(2635610, 'Totnes', 'England', 'GB', 'United Kingdom', 50.43107, -3.6843))->create()->fresh();
        $this->actingAs(User::factory()->create());
        $queries = 0;
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries++;
        });

        $this->sent($circle);

        $this->assertSame(0, $queries);
    }

    public function test_sends_no_location_for_a_circle_that_has_not_said_where_it_is(): void
    {
        $this->assertNull($this->sent(Circle::factory()->create()->fresh())['location']);
    }

    public function test_sends_how_many_members_it_has_only_once_they_are_counted(): void
    {
        $circle = Circle::factory()->create();
        $circle->members()->attach(User::factory()->count(2)->create(), ['role' => CircleRole::Member]);

        $this->assertArrayNotHasKey('members_count', $this->sent($circle->fresh()));
        $this->assertSame(2, $this->sent($circle->fresh()->loadCount('members'))['members_count']);
    }

    public function test_sends_where_its_own_approval_stands_only_once_that_is_loaded(): void
    {
        $circle = Circle::factory()->pending()->create();

        $this->assertArrayNotHasKey('status', $this->sent($circle->fresh()));
        $this->assertSame(['value' => 'pending', 'label' => 'Waiting for approval'], $this->sent($circle->fresh()->load('approval'))['status']);
    }

    /**
     * A circle near Totnes with its own exact spot there.
     */
    private function withExactSpot(): Circle
    {
        $totnes = new Place(2635610, 'Totnes', 'England', 'GB', 'United Kingdom', 50.43107, -3.6843);

        return Circle::factory()->at(Location::at($totnes, 'Fernhill Farm', new Coordinates(50.4319, -3.6863)))->create()->fresh();
    }

    /**
     * The circle as a page receives it.
     *
     * @return array<string, mixed>
     */
    private function sent(Circle $circle): array
    {
        return $this->asSent(CircleResource::make($circle));
    }
}
