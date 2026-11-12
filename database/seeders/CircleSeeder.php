<?php

namespace Database\Seeders;

use App\DTO\Coordinates;
use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Enums\Approval\ApprovalStatus;
use App\Enums\Approval\ApprovalType;
use App\Enums\Circle\CircleRole;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Circles in every state, with members, requests to join and invites, built through the real approval steps so the
 * statuses and the audit log are true, and with enough of each list to page through. Needs UserSeeder first.
 */
class CircleSeeder extends Seeder
{
    /** Approved circles besides the big one, owned by people from UserSeeder. */
    public const int APPROVED = 60;

    /** Circles waiting for staff approval besides the circle owner's, each from a different person. */
    public const int WAITING = 30;

    /** Circles staff turned down besides the circle owner's. */
    public const int REJECTED = 5;

    /** The circle owner's circle, with lists long enough to page through. */
    public const string BIG_CIRCLE = 'Fernhill Eco Village';

    /** Everyone in the big circle, its owner, admin and member included. */
    public const int BIG_CIRCLE_MEMBERS = 45;

    public const int BIG_CIRCLE_REQUESTS = 25;

    public const int BIG_CIRCLE_INVITES = 15;

    /**
     * Towns the communities live near, mostly in the US and the UK, as GeoNames has them (looked up once on 2026-11-11,
     * so seeding never calls GeoNames): id, name, region, country code, country name, latitude, longitude.
     */
    private const array PLACES = [
        [2635610, 'Totnes', 'England', 'GB', 'United Kingdom', 50.43107, -3.68430],
        [2643251, 'Machynlleth', 'Wales', 'GB', 'United Kingdom', 52.58965, -3.85308],
        [2636616, 'Stroud', 'England', 'GB', 'United Kingdom', 51.75000, -2.20000],
        [2649192, 'Forres', 'Scotland', 'GB', 'United Kingdom', 57.60997, -3.62115],
        [2647207, 'Hebden Bridge', 'England', 'GB', 'United Kingdom', 53.74093, -2.01337],
        [2648559, 'Glastonbury', 'England', 'GB', 'United Kingdom', 51.14745, -2.72075],
        [2649024, 'Frome', 'England', 'GB', 'United Kingdom', 51.22834, -2.32211],
        [2644559, 'Lewes', 'England', 'GB', 'United Kingdom', 50.87398, 0.00880],
        [2643425, 'Ludlow', 'England', 'GB', 'United Kingdom', 52.37431, -2.71311],
        [2644981, 'Lampeter', 'Wales', 'GB', 'United Kingdom', 52.11285, -4.08039],
        [2645826, 'Kendal', 'England', 'GB', 'United Kingdom', 54.32681, -2.74757],
        [2640377, 'Penzance', 'England', 'GB', 'United Kingdom', 50.11861, -5.53715],
        [2647267, 'Hay-on-wye', 'Wales', 'GB', 'United Kingdom', 52.07049, -3.12741],
        [2635199, 'Ullapool', 'Scotland', 'GB', 'United Kingdom', 57.89872, -5.16039],
        [5550409, 'Paonia', 'Colorado', 'US', 'United States', 38.86832, -107.59200],
        [5418538, 'Crestone', 'Colorado', 'US', 'United States', 37.99639, -105.69973],
        [5493811, 'Taos', 'New Mexico', 'US', 'United States', 36.40725, -105.57307],
        [5284905, 'Bisbee', 'Arizona', 'US', 'United States', 31.44815, -109.92841],
        [5558953, 'Arcata', 'California', 'US', 'United States', 40.86652, -124.08284],
        [5376502, 'Nevada City', 'California', 'US', 'United States', 39.26173, -121.01779],
        [5807239, 'Port Townsend', 'Washington', 'US', 'United States', 48.11742, -122.76071],
        [5814307, 'Twisp', 'Washington', 'US', 'United States', 48.36348, -120.12230],
        [5277276, 'Viroqua', 'Wisconsin', 'US', 'United States', 43.55692, -90.88874],
        [4759446, 'Floyd', 'Virginia', 'US', 'United States', 36.91124, -80.32005],
        [5234141, 'Brattleboro', 'Vermont', 'US', 'United States', 42.85092, -72.55787],
        [4110119, 'Eureka Springs', 'Arkansas', 'US', 'United States', 36.40118, -93.73797],
        [5543307, 'Moab', 'Utah', 'US', 'United States', 38.57332, -109.54984],
        [5525775, 'Marfa', 'Texas', 'US', 'United States', 30.30973, -104.02134],
        [2965413, 'Cloghjordan', 'Munster', 'IE', 'Ireland', 52.94028, -8.03556],
        [6086871, 'Nelson', 'British Columbia', 'CA', 'Canada', 49.49985, -117.28553],
    ];

    /** Why staff turn a new circle down. */
    private const array REJECTIONS = [
        'There is already an off-grid community on that land. Could you join it instead?',
        'Please say more about how the community will live: its power, water and food.',
        'This looks like selling plots of land rather than a community.',
        'The name needs to say where the land is.',
    ];

    private User $staff;

    public function run(): void
    {
        $this->staff = $this->account(UserSeeder::SUPER_ADMIN);
        $owner = $this->account(UserSeeder::CIRCLE_OWNER);
        $member = $this->account(UserSeeder::CIRCLE_MEMBER);

        $people = User::query()
            ->whereNotIn('email', [UserSeeder::SUPER_ADMIN, UserSeeder::STAFF_ADMIN, UserSeeder::CIRCLE_OWNER, UserSeeder::CIRCLE_ADMIN, UserSeeder::CIRCLE_MEMBER, UserSeeder::UNCONFIRMED])
            ->get()
            ->shuffle()
            ->values();

        $approved = $this->approvedCircles($people);
        $this->waitingAndRejectedCircles($people);
        $this->bigCircle($owner, $this->account(UserSeeder::CIRCLE_ADMIN), $member, $people);
        $this->circleOwnersOtherCircles($owner);
        $this->circleMembersRequestAndInvites($member, $approved);
    }

    /**
     * Approved circles of every size, so the most popular list has an order to it.
     *
     * @param  Collection<int, User>  $people
     * @return Collection<int, Circle>
     */
    private function approvedCircles(Collection $people): Collection
    {
        return Collection::times(self::APPROVED, function () use ($people): Circle {
            $owner = $people->random();
            $circle = $this->start($owner, ApprovalStatus::Approved);
            $circle->members()->attach($people->reject(fn (User $person): bool => $person->is($owner))->random(fake()->numberBetween(0, 29)), ['role' => CircleRole::Member]);

            return $circle;
        });
    }

    /**
     * Circles waiting for staff, each from a different person (one waiting circle each), and some turned down.
     *
     * @param  Collection<int, User>  $people
     */
    private function waitingAndRejectedCircles(Collection $people): void
    {
        $people->take(self::WAITING)->each(fn (User $person) => $this->start($person, ApprovalStatus::Pending));
        $people->slice(self::WAITING, self::REJECTED)->each(fn (User $person) => $this->start($person, ApprovalStatus::Rejected, reason: fake()->randomElement(self::REJECTIONS)));
    }

    /**
     * The circle owner's circle: their admin, the circle member and many more members, requests to join and invites
     * still waiting, and a few already answered.
     *
     * @param  Collection<int, User>  $people
     */
    private function bigCircle(User $owner, User $admin, User $member, Collection $people): void
    {
        $circle = $this->start(
            $owner,
            ApprovalStatus::Approved,
            ['name' => self::BIG_CIRCLE, 'description' => 'An off-grid village of straw-bale homes at Fernhill, running on sun, wind and rainwater.'],
            // Its land, a couple of miles west of the town; only its members and staff see this spot.
            Location::at($this->totnes(), 'Fernhill', new Coordinates(50.4271, -3.7124)),
        );
        $circle->members()->attach($admin, ['role' => CircleRole::Admin]);
        $circle->members()->attach($member, ['role' => CircleRole::Member]);

        // Each person has only one part to play here, so no one is both a member and asking to join.
        $others = $people->values();
        $members = $others->splice(0, self::BIG_CIRCLE_MEMBERS - 3);
        $asking = $others->splice(0, self::BIG_CIRCLE_REQUESTS);
        $invited = $others->splice(0, self::BIG_CIRCLE_INVITES);
        [$declined, $alsoDeclined, $withdrawn] = $others->splice(0, 3)->all();

        $circle->members()->attach($members, ['role' => CircleRole::Member]);
        $asking->each(fn (User $person) => $this->backdate(Approval::open(ApprovalType::JoinRequest, $circle, $person, $person)));
        $invited->each(fn (User $person) => $this->backdate(Approval::open(ApprovalType::Invite, $circle, $person, fake()->randomElement([$owner, $admin]))));

        Approval::open(ApprovalType::JoinRequest, $circle, $declined, $declined)->reject($owner, "We're full for now. Try again in the spring.");
        Approval::open(ApprovalType::JoinRequest, $circle, $alsoDeclined, $alsoDeclined)->reject($admin);
        Approval::open(ApprovalType::Invite, $circle, $withdrawn, $admin)->cancel($owner);
    }

    /**
     * The circle owner also has a circle waiting for staff (their one at a time) and one staff turned down, which they
     * can edit to send again.
     */
    private function circleOwnersOtherCircles(User $owner): void
    {
        $this->start(
            $owner,
            ApprovalStatus::Pending,
            ['name' => 'Hazelbrook Solar Co-op', 'description' => 'Neighbours at Hazelbrook sharing solar panels and batteries, off the grid for good.'],
            Location::at($this->totnes(), 'Hazelbrook'),
        );
        $this->start(
            $owner,
            ApprovalStatus::Rejected,
            ['name' => 'Hollyford Tiny House Village', 'description' => 'Tiny off-grid homes at Hollyford, with a shared bathhouse, kitchen and gardens.'],
            Location::at($this->totnes(), 'Hollyford'),
            'There is already a tiny house village near Totnes. Could you join theirs?',
        );
    }

    /**
     * The circle member has asked to join one circle, is invited to another, and had an invite to a third withdrawn
     * (which they see as expired).
     *
     * @param  Collection<int, Circle>  $approved
     */
    private function circleMembersRequestAndInvites(User $member, Collection $approved): void
    {
        [$asked, $invited, $expired] = $approved->random(3)->all();

        Approval::open(ApprovalType::JoinRequest, $asked, $member, $member);
        Approval::open(ApprovalType::Invite, $invited, $member, $invited->approval->user);
        Approval::open(ApprovalType::Invite, $expired, $member, $expired->approval->user)->cancel($expired->approval->user);
    }

    /**
     * A circle started by its owner, who is attached as such, and its new-circle approval decided by staff (or left
     * waiting), with the time it was asked for spread over the last month. Without a location, it's somewhere near one
     * of the towns (CircleFactory::somewhereNear()), mostly with coordinates for the explore map.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function start(User $owner, ApprovalStatus $outcome, array $attributes = [], ?Location $location = null, ?string $reason = null): Circle
    {
        $factory = $location === null ? Circle::factory()->somewhereNear($this->places()) : Circle::factory()->at($location);
        // Made, then saved: creating it through the factory would also make an approval and a person of its own.
        $circle = $factory->make($attributes);
        $circle->save();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);

        $approval = Approval::open(ApprovalType::NewCircle, $circle, $owner, $owner);

        if ($outcome === ApprovalStatus::Approved) {
            $approval->approve($this->staff);
        } elseif ($outcome === ApprovalStatus::Rejected) {
            $approval->reject($this->staff, $reason);
        }

        $this->backdate($approval);

        return $circle;
    }

    /**
     * Spread when it was asked for over the last month, so the queues and "asked 3 days ago" vary.
     */
    private function backdate(Approval $approval): void
    {
        $askedAt = now()->subMinutes(fake()->numberBetween(10, 60 * 24 * 30));

        $approval->forceFill(['created_at' => $askedAt, 'updated_at' => $askedAt])->saveQuietly();
    }

    /**
     * The towns the communities live near.
     *
     * @return non-empty-list<Place>
     */
    private function places(): array
    {
        return array_map(
            fn (array $place): Place => new Place(
                geonamesId: $place[0],
                name: $place[1],
                region: $place[2],
                countryCode: $place[3],
                countryName: $place[4],
                latitude: $place[5],
                longitude: $place[6],
            ),
            self::PLACES,
        );
    }

    /**
     * Totnes, which the circle owner's own circles are near.
     */
    private function totnes(): Place
    {
        return $this->places()[0];
    }

    private function account(string $email): User
    {
        return User::query()->where('email', $email)->sole();
    }
}
