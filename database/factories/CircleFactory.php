<?php

namespace Database\Factories;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Enums\Approval\ApprovalStatus;
use App\Models\Approval;
use App\Models\Circle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Circle>
 */
class CircleFactory extends Factory
{
    /**
     * What off-grid communities call their land; a circle is named for its land, e.g. "Willowmere Eco Village". One
     * word each, so the land is the first word of the name. NOTE: no Fernhill, Hazelbrook or Hollyford, which the
     * CircleSeeder's own circles use, so a made-up circle can't share one of their names.
     */
    private const array LANDS = [
        'Ashgrove', 'Birchwater', 'Bramblewood', 'Brightwater', 'Clearspring', 'Cloudberry', 'Copperbeech', 'Elderwood',
        'Foxglove', 'Greenwater', 'Heronsgate', 'Honeycombe', 'Juniper', 'Kingfisher', 'Lanternhill', 'Larkspur',
        'Meadowlight', 'Mossbank', 'Oakhaven', 'Riverbend', 'Starling', 'Sunhollow', 'Sunreach', 'Thistledown',
        'Wildacre', 'Willowmere',
    ];

    /**
     * Kinds of solarpunk, off-grid community, each with how it lives ({land} is filled in with its land's name).
     */
    private const array KINDS = [
        'Eco Village' => 'An off-grid village of straw-bale homes at {land}, running on sun, wind and rainwater.',
        'Commons' => 'Shared land at {land} where families grow food, raise their own homes and decide things together.',
        'Permaculture Farm' => 'Food forests, swales and polycultures at {land}, with courses for anyone who wants to learn.',
        'Housing Co-op' => 'Low-impact homes at {land}, owned together and built from local timber and cob.',
        'Land Trust' => "A community land trust keeping {land} affordable, wild and out of speculators' hands.",
        'Food Forest' => 'A seven-layer food forest at {land}, open to everyone who helps tend it.',
        'Solar Co-op' => 'Neighbours at {land} sharing solar panels and batteries, off the grid for good.',
        'Earthship Collective' => 'Earthships at {land} built from tyres, bottles and earth that heat and water themselves.',
        'Microgrid Co-op' => 'Running {land} on its own sun, wind and water, with the power shared between homes.',
        'Rewilding Collective' => 'Letting {land} go wild again: beaver dams, hedgerows and wildflower meadows.',
        'Water Commons' => 'Catching, cleaning and sharing rainwater and springs around {land}.',
        'Seed Commons' => 'Saving and swapping open-pollinated seeds suited to the soil at {land}.',
        'Cohousing' => 'Homes of our own at {land} around a shared common house: one kitchen, one workshop, one garden.',
        "Smallholders' Collective" => 'Smallholders around {land} sharing tools, livestock and the harvest.',
        'Tiny House Village' => 'Tiny off-grid homes at {land}, with a shared bathhouse, kitchen and gardens.',
        'Repair Commons' => "A workshop at {land} where nothing is thrown away until we've tried to fix it.",
        'Forest Garden' => 'Nut trees, fruit and perennial greens growing together at {land}, tended by everyone.',
        'Off-Grid Collective' => 'Living at {land} without mains power or water, and helping others make the change.',
    ];

    /**
     * Define the model's default state: a solarpunk community named for its land, e.g. "Willowmere Eco Village", with
     * how it lives.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$land, $kind] = fake()->unique()->randomElement(self::communities());

        return [
            'name' => "{$land} {$kind}",
            'description' => str_replace('{land}', $land, self::KINDS[$kind]),
        ];
    }

    /**
     * Every land with every kind of community.
     *
     * @return list<array{string, string}>
     */
    private static function communities(): array
    {
        return array_merge(...array_map(
            fn (string $land): array => array_map(fn (string $kind): array => [$land, $kind], array_keys(self::KINDS)),
            self::LANDS,
        ));
    }

    /**
     * Circles are approved unless a test says otherwise, with pending() or rejected().
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Circle $circle) => Approval::factory()->approved()->for($circle, 'approvable')->create());
    }

    /**
     * A circle with no approval at all yet, e.g. to open one in a test.
     */
    public function withoutApproval(): static
    {
        return $this->afterCreating(fn (Circle $circle) => $circle->approval()->delete());
    }

    /**
     * A circle still waiting for staff to approve it.
     */
    public function pending(): static
    {
        return $this->afterCreating(fn (Circle $circle) => $circle->approval->update(['status' => ApprovalStatus::Pending, 'decided_at' => null]));
    }

    /**
     * A circle staff turned down, optionally saying why.
     */
    public function rejected(?string $reason = null): static
    {
        return $this->afterCreating(fn (Circle $circle) => $circle->approval->update(['status' => ApprovalStatus::Rejected, 'reason' => $reason]));
    }

    /**
     * A circle at a GeoNames place, or near it with a name of its own, e.g. Circle::factory()->in($totnes)->create().
     */
    public function in(Place $place, ?string $name = null): static
    {
        return $this->at(Location::at($place, $name));
    }

    /**
     * Somewhere near one of the places, mostly, under the community's own name for its land (the first word of its
     * name): usually near one of them, with its coordinates (e.g. for a map); sometimes just the land's name in that
     * place's country; sometimes nowhere at all.
     *
     * @param  non-empty-list<Place>  $places
     */
    public function somewhereNear(array $places): static
    {
        return $this->afterMaking(function (Circle $circle) use ($places): void {
            $place = fake()->randomElement($places);
            $land = Str::before($circle->name, ' ');

            $circle->locate(match (true) {
                fake()->boolean(80) => Location::at($place, $land),
                fake()->boolean(50) => Location::named($land, $place->countryCode),
                default => null,
            });
        });
    }

    /**
     * A circle at the location, e.g. Circle::factory()->at(Location::named('Fernhill Farm', 'GB'))->create().
     */
    public function at(Location $location): static
    {
        return $this->afterMaking(fn (Circle $circle) => $circle->locate($location));
    }
}
