<?php

namespace Tests\Feature\Models;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CircleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_mass_assigns_the_name_and_description(): void
    {
        $circle = new Circle(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);

        $this->assertSame('Exchange', $circle->name);
        $this->assertSame('Share skills and lend tools.', $circle->description);
    }

    public function test_ignores_attributes_that_are_not_mass_assignable(): void
    {
        $circle = new Circle(['name' => 'Exchange', 'public_id' => 'chosenbyme']);

        $this->assertSame('Exchange', $circle->name);
        $this->assertNull($circle->public_id);
    }

    public function test_members_are_everyone_in_the_circle_with_their_role(): void
    {
        $circle = Circle::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);
        $circle->members()->attach($member, ['role' => CircleRole::Member]);
        User::factory()->create();

        $members = $circle->members()->orderBy('users.id')->get();

        $this->assertSame([$owner->id, $member->id], $members->modelKeys());
        $this->assertSame([CircleRole::Owner, CircleRole::Member], $members->pluck('pivot.role')->all());
    }

    public function test_owners_are_only_the_members_who_own_the_circle(): void
    {
        $circle = Circle::factory()->create();
        $owner = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);
        $circle->members()->attach(User::factory()->create(), ['role' => CircleRole::Admin]);
        $circle->members()->attach(User::factory()->create(), ['role' => CircleRole::Member]);

        $this->assertSame([$owner->id], $circle->owners->modelKeys());
    }

    public function test_managers_are_the_owners_and_admins(): void
    {
        $circle = Circle::factory()->create();
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);
        $circle->members()->attach($admin, ['role' => CircleRole::Admin]);
        $circle->members()->attach(User::factory()->create(), ['role' => CircleRole::Member]);

        $this->assertEqualsCanonicalizing([$owner->id, $admin->id], $circle->managers->modelKeys());
    }

    public function test_membership_of_a_member_is_their_own_row_with_their_role(): void
    {
        $circle = Circle::factory()->create();
        $circle->members()->attach(User::factory()->create(), ['role' => CircleRole::Owner]);
        $admin = User::factory()->create();
        $circle->members()->attach($admin, ['role' => CircleRole::Admin]);

        $membership = $circle->membershipOf($admin);

        $this->assertSame($admin->id, $membership->user_id);
        $this->assertSame(CircleRole::Admin, $membership->role);
    }

    public function test_membership_of_someone_outside_the_circle_is_null(): void
    {
        $circle = Circle::factory()->create();
        $circle->members()->attach(User::factory()->create(), ['role' => CircleRole::Owner]);

        $this->assertNull($circle->membershipOf(User::factory()->create()));
    }

    public function test_is_approved_only_once_staff_have_approved_it(): void
    {
        $this->assertTrue(Circle::factory()->create()->isApproved());
        $this->assertFalse(Circle::factory()->pending()->create()->isApproved());
        $this->assertFalse(Circle::factory()->rejected()->create()->isApproved());
        $this->assertFalse(Circle::factory()->withoutApproval()->create()->isApproved());
    }

    public function test_its_approval_is_the_new_circle_one_not_a_join_request_or_invite(): void
    {
        $circle = Circle::factory()->withoutApproval()->create();
        $asker = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);
        Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $asker);
        Approval::open(ApprovalType::NewCircle, $circle, $asker, $asker);

        $this->assertSame(ApprovalType::NewCircle, $circle->approval->type);
        $this->assertSame(3, $circle->approvals()->count());
    }

    /**
     * @param  'JoinRequest'|'Invite'  $type
     */
    #[DataProvider('approvalsThatAddSomeone')]
    public function test_approving_a_join_request_or_invite_adds_the_person_as_a_member(string $type): void
    {
        $circle = Circle::factory()->create();
        $person = User::factory()->create();
        $approval = Approval::open(constant(ApprovalType::class.'::'.$type), $circle, $person, User::factory()->create());

        $circle->whenApproved($approval);

        $this->assertSame(CircleRole::Member, $circle->membershipOf($person)?->role);
    }

    public function test_approving_a_new_circle_adds_no_one(): void
    {
        $circle = Circle::factory()->pending()->create();

        $circle->whenApproved($circle->approval);

        $this->assertSame(0, $circle->members()->count());
    }

    public function test_approving_someone_already_in_the_circle_keeps_their_role(): void
    {
        $circle = Circle::factory()->create();
        $admin = User::factory()->create();
        $circle->members()->attach($admin, ['role' => CircleRole::Admin]);
        $approval = Approval::open(ApprovalType::JoinRequest, $circle, $admin, $admin);

        $circle->whenApproved($approval);

        $this->assertSame(CircleRole::Admin, $circle->membershipOf($admin)->role);
        $this->assertSame(1, $circle->members()->count());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function approvalsThatAddSomeone(): array
    {
        return [
            'a request to join' => ['JoinRequest'],
            'an invite' => ['Invite'],
        ];
    }

    public function test_approved_finds_only_approved_circles(): void
    {
        $approved = Circle::factory()->create();
        Circle::factory()->pending()->create();
        Circle::factory()->rejected()->create();

        $this->assertSame([$approved->id], Circle::query()->approved()->pluck('id')->all());
    }

    public function test_has_no_location_until_it_is_given_one(): void
    {
        $circle = Circle::factory()->create();

        $this->assertNull($circle->location());
    }

    public function test_keeps_a_location_at_a_geonames_place(): void
    {
        $circle = Circle::factory()->create();

        $circle->locate(Location::at($this->totnes()));
        $circle->save();

        $this->assertEquals(Location::at($this->totnes()), $circle->fresh()->location());
    }

    public function test_keeps_its_own_name_for_a_place_near_a_geonames_one(): void
    {
        $circle = Circle::factory()->create();

        $circle->locate(Location::at($this->totnes(), 'Fernhill Farm'));
        $circle->save();

        $this->assertEquals(Location::at($this->totnes(), 'Fernhill Farm'), $circle->fresh()->location());
    }

    public function test_keeps_a_location_that_is_just_a_name_in_a_country(): void
    {
        $circle = Circle::factory()->create();

        $circle->locate(Location::named('Fernhill Farm', 'GB'));
        $circle->save();

        $circle->refresh();
        $this->assertEquals(Location::named('Fernhill Farm', 'GB'), $circle->location());
        $this->assertNull($circle->geonames_id);
        $this->assertNull($circle->latitude);
    }

    public function test_keeps_a_place_with_no_region(): void
    {
        $vaduz = new Place(3042030, 'Vaduz', null, 'LI', 'Liechtenstein', 47.14151, 9.52154);
        $circle = Circle::factory()->create();

        $circle->locate(Location::at($vaduz));
        $circle->save();

        $this->assertEquals(Location::at($vaduz), $circle->fresh()->location());
    }

    public function test_clearing_the_location_empties_every_part_of_it(): void
    {
        $circle = Circle::factory()->in($this->totnes(), 'Fernhill Farm')->create();

        $circle->locate(null);
        $circle->save();

        $circle->refresh();
        $this->assertNull($circle->location());
        $this->assertNull($circle->location_name);
        $this->assertNull($circle->geonames_id);
        $this->assertNull($circle->country_code);
        $this->assertNull($circle->latitude);
    }

    public function test_does_not_let_the_location_be_mass_assigned(): void
    {
        $circle = new Circle(['name' => 'Exchange', 'location_name' => 'Fernhill Farm', 'geonames_id' => 2635610, 'place_name' => 'Totnes', 'latitude' => 50.43107]);

        $this->assertNull($circle->location_name);
        $this->assertNull($circle->geonames_id);
        $this->assertNull($circle->place_name);
        $this->assertNull($circle->latitude);
    }

    private function totnes(): Place
    {
        return new Place(2635610, 'Totnes', 'England', 'GB', 'United Kingdom', 50.43107, -3.6843);
    }
}
