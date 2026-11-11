<?php

namespace Tests\Feature\Models;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Enums\Approval\ApprovalType;
use App\Enums\Circle\CircleRole;
use App\Enums\User\Role;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;
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

    #[DataProvider('managerRoles')]
    public function test_is_managed_by_its_owners_and_admins(CircleRole $role): void
    {
        $circle = Circle::factory()->create();
        $manager = User::factory()->create();
        $circle->members()->attach($manager, ['role' => $role]);

        $this->assertTrue($circle->managedBy($manager));
    }

    public function test_is_not_managed_by_a_plain_member(): void
    {
        $circle = Circle::factory()->create();
        $member = User::factory()->create();
        $circle->members()->attach($member, ['role' => CircleRole::Member]);

        $this->assertFalse($circle->managedBy($member));
    }

    public function test_is_not_managed_by_an_owner_of_another_circle_or_by_staff(): void
    {
        $circle = Circle::factory()->create();
        $otherOwner = User::factory()->create();
        Circle::factory()->create()->members()->attach($otherOwner, ['role' => CircleRole::Owner]);

        $this->assertFalse($circle->managedBy($otherOwner));
        $this->assertFalse($circle->managedBy(User::factory()->create(['role' => Role::SuperAdmin])));
    }

    /**
     * @return array<string, array{CircleRole}>
     */
    public static function managerRoles(): array
    {
        return [
            'owner' => [CircleRole::Owner],
            'admin' => [CircleRole::Admin],
        ];
    }

    public function test_invite_for_someone_is_their_invite_while_it_waits_or_once_it_is_withdrawn(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $waiting = Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $owner);
        $withdrawn = Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $owner);
        $withdrawn->cancel($owner);

        $this->assertTrue($circle->inviteFor($waiting->user)?->is($waiting));
        $this->assertTrue($circle->inviteFor($withdrawn->user)?->is($withdrawn));
    }

    public function test_invite_for_someone_who_declined_or_has_none_is_nothing(): void
    {
        $circle = Circle::factory()->create();
        $decliner = User::factory()->create();
        Approval::open(ApprovalType::Invite, $circle, $decliner, $this->memberOf($circle, CircleRole::Owner))->reject($decliner);

        $this->assertNull($circle->inviteFor($decliner));
        $this->assertNull($circle->inviteFor(User::factory()->create()));
        $this->assertNull($circle->inviteFor(null));
    }

    public function test_invite_for_someone_already_in_the_circle_is_nothing_even_if_an_old_one_was_withdrawn(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $member = User::factory()->create();
        Approval::open(ApprovalType::Invite, $circle, $member, $owner)->cancel($owner);
        $circle->members()->attach($member, ['role' => CircleRole::Member]);

        $this->assertNull($circle->inviteFor($member));
    }

    public function test_members_by_role_are_owners_then_admins_then_members_longest_in_first_with_their_membership(): void
    {
        $this->freezeSecond();
        $circle = Circle::factory()->create();
        // The newest member's row is written first, so the order is by when they joined, not by row or account.
        $newest = User::factory()->create();
        $circle->members()->attach($newest, ['role' => CircleRole::Member, 'created_at' => now()->addHours(3)]);
        $longestIn = $this->memberOf($circle, CircleRole::Member);
        $this->travel(1)->hour();
        $admin = $this->memberOf($circle, CircleRole::Admin);
        $this->travel(1)->hour();
        $owner = $this->memberOf($circle, CircleRole::Owner);

        $members = $circle->membersByRole()->get();

        $this->assertSame([$owner->id, $admin->id, $longestIn->id, $newest->id], $members->modelKeys());
        $this->assertSame(CircleRole::Owner, $members->first()->pivot->role);
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

    public function test_sums_itself_up_for_whoever_decides_its_approvals(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);

        $this->assertSame([
            'name' => 'Exchange',
            'description' => 'Share skills and lend tools.',
            'url' => route('circles.show', $circle),
        ], $circle->approvalSummary());
    }

    public function test_search_finds_circles_with_the_text_in_their_name_or_description_whatever_the_case(): void
    {
        $byName = Circle::factory()->create(['name' => 'Seed Exchange', 'description' => 'Swapping what grows well here.']);
        $byDescription = Circle::factory()->create(['name' => 'Allotment', 'description' => 'Sharing seeds and tools.']);
        Circle::factory()->create(['name' => 'Repair Café', 'description' => 'Fixing things together.']);

        $this->assertEqualsCanonicalizing([$byName->id, $byDescription->id], Circle::query()->search('SEED')->pluck('id')->all());
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

    public function test_logs_a_new_circle_with_its_details_and_who_created_it(): void
    {
        $creator = User::factory()->create();
        $this->actingAs($creator);

        $circle = Circle::factory()->create(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);

        $activity = $this->lastActivityAbout($circle);
        $this->assertSame(ActivityEvent::Created->value, $activity->event);
        $this->assertTrue($activity->causer->is($creator));
        $this->assertSame('Exchange', $activity->attribute_changes['attributes']['name']);
        $this->assertSame('Share skills and lend tools.', $activity->attribute_changes['attributes']['description']);
    }

    public function test_logs_changes_to_a_circles_details_and_location_with_who_made_them(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange']);
        $owner = User::factory()->create();
        $this->actingAs($owner);

        $circle->name = 'Tool library';
        $circle->locate(Location::named('Fernhill Farm', 'GB'));
        $circle->save();

        $activity = $this->lastActivityAbout($circle);
        $this->assertSame(ActivityEvent::Updated->value, $activity->event);
        $this->assertTrue($activity->causer->is($owner));
        $this->assertSame([
            'name' => 'Tool library',
            'location_name' => 'Fernhill Farm',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
        ], $activity->attribute_changes['attributes']);
        $this->assertSame('Exchange', $activity->attribute_changes['old']['name']);
        $this->assertNull($activity->attribute_changes['old']['location_name']);
    }

    public function test_logs_a_move_to_a_geonames_place_with_where_it_is(): void
    {
        $circle = Circle::factory()->create();

        $circle->locate(Location::at(new Place(2635610, 'Totnes', 'England', 'GB', 'United Kingdom', 50.43107, -3.6843)));
        $circle->save();

        $this->assertSame([
            'geonames_id' => 2635610,
            'place_name' => 'Totnes',
            'region' => 'England',
            'country_code' => 'GB',
            'country_name' => 'United Kingdom',
            'latitude' => 50.43107,
            'longitude' => -3.6843,
        ], $this->lastActivityAbout($circle)->attribute_changes['attributes']);
    }

    public function test_does_not_log_a_save_that_only_touches_the_timestamps(): void
    {
        $circle = Circle::factory()->create();
        $logged = Activity::query()->count();
        $this->travel(1)->minute();

        $circle->touch();

        $this->assertSame($logged, Activity::query()->count());
    }

    public function test_logs_a_circle_being_deleted_with_its_details(): void
    {
        $circle = Circle::factory()->create(['name' => 'Exchange']);

        $circle->delete();

        $activity = $this->lastActivityAbout($circle);
        $this->assertSame(ActivityEvent::Deleted->value, $activity->event);
        $this->assertSame('Exchange', $activity->attribute_changes['old']['name']);
    }

    /**
     * The newest audit log entry about the circle.
     */
    private function lastActivityAbout(Circle $circle): Activity
    {
        return Activity::query()->whereMorphedTo('subject', $circle)->latest('id')->firstOrFail();
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
}
