<?php

namespace Tests\Feature\Http\Resources;

use App\DTO\Location;
use App\Enums\Circle\CircleRole;
use App\Http\Resources\CircleResource;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
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
            'location' => ['name' => 'Fernhill Farm', 'place' => null, 'country_code' => 'GB', 'country_name' => 'United Kingdom'],
        ], $this->sent($circle->fresh()));
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
     * The circle as a page receives it.
     *
     * @return array<string, mixed>
     */
    private function sent(Circle $circle): array
    {
        return $this->asSent(CircleResource::make($circle));
    }
}
