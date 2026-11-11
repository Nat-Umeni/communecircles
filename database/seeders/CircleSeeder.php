<?php

namespace Database\Seeders;

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
    public const string BIG_CIRCLE = 'Totnes Seed Exchange';

    /** Everyone in the big circle, its owner, admin and member included. */
    public const int BIG_CIRCLE_MEMBERS = 45;

    public const int BIG_CIRCLE_REQUESTS = 25;

    public const int BIG_CIRCLE_INVITES = 15;

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
     * Approved circles of every size, so the most popular list has an order to it; every tenth says where it is.
     *
     * @param  Collection<int, User>  $people
     * @return Collection<int, Circle>
     */
    private function approvedCircles(Collection $people): Collection
    {
        return Collection::times(self::APPROVED, function (int $number) use ($people): Circle {
            $owner = $people->random();
            $circle = $this->start($owner, ApprovalStatus::Approved, location: $number % 10 === 0 ? Location::named(fake()->city(), 'GB') : null);
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
        $people->slice(self::WAITING, self::REJECTED)->each(fn (User $person) => $this->start($person, ApprovalStatus::Rejected, reason: fake()->sentence()));
    }

    /**
     * The circle owner's circle: their admin, the circle member and many more members, requests to join and invites
     * still waiting, and a few already answered.
     *
     * @param  Collection<int, User>  $people
     */
    private function bigCircle(User $owner, User $admin, User $member, Collection $people): void
    {
        $circle = $this->start($owner, ApprovalStatus::Approved, ['name' => self::BIG_CIRCLE], Location::named('Totnes', 'GB'));
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
        $this->start($owner, ApprovalStatus::Pending, ['name' => 'Dartington Tool Library']);
        $this->start($owner, ApprovalStatus::Rejected, ['name' => 'Totnes Repair Café'], reason: 'There is already a repair café in Totnes. Could you join theirs?');
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
     * waiting), with the time it was asked for spread over the last month.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function start(User $owner, ApprovalStatus $outcome, array $attributes = [], ?Location $location = null, ?string $reason = null): Circle
    {
        $factory = $location === null ? Circle::factory() : Circle::factory()->at($location);
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

    private function account(string $email): User
    {
        return User::query()->where('email', $email)->sole();
    }
}
