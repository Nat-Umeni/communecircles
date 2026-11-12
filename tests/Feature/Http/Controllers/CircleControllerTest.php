<?php

namespace Tests\Feature\Http\Controllers;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Enums\Approval\ApprovalStatus;
use App\Enums\Approval\ApprovalType;
use App\Enums\Circle\CircleRole;
use App\Enums\User\Role;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use App\Notifications\Circle\NewCircleAwaitingApproval;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
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

    public function test_lists_the_most_popular_circles_first_then_by_name_with_their_member_counts(): void
    {
        $exchange = Circle::factory()->create(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);
        $exchange->members()->attach(User::factory()->count(2)->create(), ['role' => CircleRole::Member]);
        Circle::factory()->create(['name' => 'Bakery']);
        Circle::factory()->create(['name' => 'Allotment']);

        $response = $this->get(route('circles.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('circles.data.1.name', 'Allotment')
            ->where('circles.data.2.name', 'Bakery')
            ->where('circles.data.0', [
                'public_id' => $exchange->public_id,
                'name' => 'Exchange',
                'description' => 'Share skills and lend tools.',
                'members_count' => 2,
                'location' => null,
            ]));
    }

    public function test_gives_a_member_their_own_circles_in_name_order_including_ones_waiting_for_approval(): void
    {
        $member = User::factory()->create();
        $exchange = Circle::factory()->pending()->create(['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);
        $exchange->members()->attach($member, ['role' => CircleRole::Owner]);
        $allotment = Circle::factory()->create(['name' => 'Allotment']);
        $allotment->members()->attach($member, ['role' => CircleRole::Member]);
        Circle::factory()->create(['name' => 'Bakery']);

        $response = $this->actingAs($member)->get(route('circles.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('your_circles', 2)
            ->where('your_circles.0.name', 'Allotment')
            ->where('your_circles.0.status', ['value' => 'approved', 'label' => 'Approved'])
            ->where('your_circles.1', [
                'public_id' => $exchange->public_id,
                'name' => 'Exchange',
                'description' => 'Share skills and lend tools.',
                'location' => null,
                'members_count' => 1,
                'status' => ['value' => 'pending', 'label' => 'Waiting for approval'],
            ]));
    }

    public function test_guests_have_no_circles_of_their_own(): void
    {
        Circle::factory()->create();

        $response = $this->get(route('circles.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('your_circles', null));
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

    public function test_finds_circles_whose_description_contains_the_search(): void
    {
        Circle::factory()->create(['name' => 'Allotment', 'description' => 'Sharing seeds and tools.']);
        Circle::factory()->create(['name' => 'Repair Café', 'description' => 'Fixing things together.']);

        $response = $this->get(route('circles.index', ['search' => 'seeds']));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('circles.data', 1)
            ->where('circles.data.0.name', 'Allotment'));
    }

    public function test_lists_and_searches_only_approved_circles(): void
    {
        Circle::factory()->create(['name' => 'Exchange']);
        Circle::factory()->pending()->create(['name' => 'Exchange, waiting']);
        Circle::factory()->rejected()->create(['name' => 'Exchange, rejected']);
        // Found by its description, so searching either column mustn't let a circle staff haven't approved through.
        Circle::factory()->pending()->create(['name' => 'Allotment', 'description' => 'Exchange plants and seeds.']);

        $list = $this->get(route('circles.index'));
        $search = $this->get(route('circles.index', ['search' => 'Exch']));

        $list->assertInertia(fn (AssertableInertia $page) => $page->has('circles.data', 1)->where('circles.data.0.name', 'Exchange'));
        $search->assertInertia(fn (AssertableInertia $page) => $page->has('circles.data', 1)->where('circles.data.0.name', 'Exchange'));
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
                'status' => ['value' => 'approved', 'label' => 'Approved'],
            ])
            ->where('can', ['view_channels' => false, 'request_to_join' => false, 'invite' => false, 'update' => false, 'delete' => false]));
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
            ->where('can', ['view_channels' => true, 'request_to_join' => false, 'invite' => true, 'update' => true, 'delete' => false]));
    }

    public function test_tells_someone_outside_the_circle_they_can_ask_to_join(): void
    {
        $circle = Circle::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('can', ['view_channels' => false, 'request_to_join' => true, 'invite' => false, 'update' => false, 'delete' => false]));
    }

    public function test_tells_staff_they_can_also_delete_the_circle(): void
    {
        $circle = Circle::factory()->create();
        $staff = User::factory()->create(['role' => Role::SuperAdmin]);

        $response = $this->actingAs($staff)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('can.delete', true));
    }

    public function test_a_circle_that_is_not_approved_is_not_found_for_anyone_else(): void
    {
        $circle = Circle::factory()->pending()->create();
        $this->memberOf($circle, CircleRole::Owner);

        $this->get(route('circles.show', $circle))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('circles.show', $circle))->assertNotFound();
    }

    public function test_its_creator_can_open_their_circle_while_it_waits_for_approval(): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->get(route('circles.show', $circle));

        $response->assertOk();
    }

    public function test_shows_the_creator_that_their_circle_is_waiting_for_approval(): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('approval.id', $circle->approval->id)
            ->where('approval.status', ['value' => 'pending', 'label' => 'Waiting for approval'])
            ->where('approval.can.decide', false));
    }

    public function test_shows_the_creator_why_staff_turned_their_circle_down(): void
    {
        $circle = Circle::factory()->rejected('There is already a circle for tool sharing.')->create();

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('approval.status', ['value' => 'rejected', 'label' => 'Not approved'])
            ->where('approval.reason', 'There is already a circle for tool sharing.'));
    }

    public function test_lets_staff_decide_on_a_waiting_circle_from_its_page(): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('approval.can.decide', true));
    }

    public function test_an_approved_circle_has_no_approval_to_show(): void
    {
        $response = $this->get(route('circles.show', Circle::factory()->create()));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('approval', null));
    }

    public function test_shows_someone_their_own_request_to_join_while_it_waits(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        $request = Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);

        $response = $this->actingAs($asker)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('join_request.id', $request->id)
            ->where('join_request.status', ['value' => 'pending', 'label' => 'Waiting for approval'])
            ->where('join_request.reason', null));
    }

    public function test_shows_someone_why_their_request_to_join_was_turned_down(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker)->reject($this->memberOf($circle, CircleRole::Owner), 'We are full for now.');

        $response = $this->actingAs($asker)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('join_request.status', ['value' => 'rejected', 'label' => 'Not approved'])
            ->where('join_request.reason', 'We are full for now.'));
    }

    public function test_shows_the_circles_owners_and_admins_the_requests_waiting_for_them_oldest_first(): void
    {
        $this->freezeSecond();
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $first = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $firstRequest = Approval::open(ApprovalType::JoinRequest, $circle, $first, $first);
        $this->travel(1)->hour();
        $second = User::factory()->create();
        $secondRequest = Approval::open(ApprovalType::JoinRequest, $circle, $second, $second);
        $declined = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, $circle, $declined, $declined)->reject($owner);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Admin))->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pending_join_requests.data', 2)
            ->where('pending_join_requests.data.0.id', $firstRequest->id)
            ->where('pending_join_requests.data.0.user', ['first_name' => 'Ada', 'last_name' => 'Lovelace'])
            ->where('pending_join_requests.data.0.requested_at', $firstRequest->updated_at->toIso8601String())
            ->where('pending_join_requests.data.0.can.decide', true)
            ->where('pending_join_requests.data.1.id', $secondRequest->id));
    }

    public function test_someone_who_has_not_asked_sees_no_request_of_theirs_even_when_others_have_asked(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);

        $this->get(route('circles.show', $circle))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('join_request', null));
        $this->actingAs(User::factory()->create())->get(route('circles.show', $circle))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('join_request', null));
    }

    /**
     * @param  Closure(Circle): ?User  $viewer
     */
    #[DataProvider('cannotSeeWaitingApprovals')]
    public function test_only_staff_and_the_circles_owners_and_admins_see_the_requests_to_join(Closure $viewer): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);
        $user = $viewer($circle);

        $response = ($user === null ? $this : $this->actingAs($user))->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('pending_join_requests', null));
    }

    public function test_staff_see_the_requests_and_invites_waiting_but_cannot_decide_or_cancel_them(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);
        Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $this->memberOf($circle, CircleRole::Owner));

        $response = $this->actingAs(User::factory()->create(['role' => Role::SuperAdmin]))->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pending_join_requests.data', 1)
            ->where('pending_join_requests.data.0.can.decide', false)
            ->has('pending_invites.data', 1)
            ->where('pending_invites.data.0.can.cancel', false));
    }

    /**
     * @return array<string, array{Closure(Circle): ?User}>
     */
    public static function cannotSeeWaitingApprovals(): array
    {
        return [
            'a guest' => [fn (): ?User => null],
            'someone outside the circle' => [fn (): User => User::factory()->create()],
            'a plain member' => [function (Circle $circle): User {
                $member = User::factory()->create();
                $circle->members()->attach($member, ['role' => CircleRole::Member]);

                return $member;
            }],
        ];
    }

    public function test_shows_someone_the_invite_waiting_for_them_and_who_sent_it(): void
    {
        $circle = Circle::factory()->create();
        $owner = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);
        $invitee = User::factory()->create();
        $invite = Approval::open(ApprovalType::Invite, $circle, $invitee, $owner);

        $response = $this->actingAs($invitee)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('invite.id', $invite->id)
            ->where('invite.status', ['value' => 'pending', 'label' => 'Waiting for approval'])
            ->where('invite.requester', ['first_name' => 'Ada', 'last_name' => 'Lovelace'])
            ->where('invite.can.decide', true));
    }

    public function test_shows_someone_whose_invite_was_cancelled_that_it_expired_and_lets_them_ask_to_join(): void
    {
        $circle = Circle::factory()->create();
        $owner = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);
        $invitee = User::factory()->create();
        $invite = Approval::open(ApprovalType::Invite, $circle, $invitee, $owner);
        $invite->cancel($owner);

        $response = $this->actingAs($invitee)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('invite.id', $invite->id)
            ->where('invite.status', ['value' => 'expired', 'label' => 'Expired'])
            ->where('invite.requester', ['first_name' => 'Ada', 'last_name' => 'Lovelace'])
            ->where('can.request_to_join', true));
    }

    public function test_a_member_whose_old_invite_was_cancelled_sees_no_invite(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $member = User::factory()->create();
        Approval::open(ApprovalType::Invite, $circle, $member, $owner)->cancel($owner);
        $circle->members()->attach($member, ['role' => CircleRole::Member]);

        $response = $this->actingAs($member)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('invite', null));
    }

    public function test_shows_no_sender_for_an_invite_from_an_account_since_deleted(): void
    {
        $circle = Circle::factory()->create();
        $invitee = User::factory()->create();
        $invite = Approval::open(ApprovalType::Invite, $circle, $invitee, $this->memberOf($circle, CircleRole::Owner));
        $invite->update(['requested_by' => null]);

        $response = $this->actingAs($invitee)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('invite.requester', null));
    }

    public function test_shows_the_circles_owners_and_admins_the_invites_still_waiting_oldest_first(): void
    {
        $this->freezeSecond();
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $first = User::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
        $firstInvite = Approval::open(ApprovalType::Invite, $circle, $first, $owner);
        $this->travel(1)->hour();
        $secondInvite = Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $owner);
        $decliner = User::factory()->create();
        Approval::open(ApprovalType::Invite, $circle, $decliner, $owner)->reject($decliner);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Admin))->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pending_invites.data', 2)
            ->where('pending_invites.data.0.id', $firstInvite->id)
            ->where('pending_invites.data.0.user', ['first_name' => 'Grace', 'last_name' => 'Hopper'])
            ->where('pending_invites.data.0.requested_at', $firstInvite->updated_at->toIso8601String())
            ->where('pending_invites.data.0.can.cancel', true)
            ->where('pending_invites.data.1.id', $secondInvite->id));
    }

    public function test_someone_without_an_invite_waiting_sees_none_even_when_others_are_invited(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $owner);
        $decliner = User::factory()->create();
        Approval::open(ApprovalType::Invite, $circle, $decliner, $owner)->reject($decliner);

        $this->get(route('circles.show', $circle))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('invite', null));
        $this->actingAs(User::factory()->create())->get(route('circles.show', $circle))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('invite', null));
        $this->actingAs($decliner)->get(route('circles.show', $circle))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('invite', null));
    }

    /**
     * @param  Closure(Circle): ?User  $viewer
     */
    #[DataProvider('cannotSeeWaitingApprovals')]
    public function test_only_staff_and_the_circles_owners_and_admins_see_the_invites_waiting(Closure $viewer): void
    {
        $circle = Circle::factory()->create();
        Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $this->memberOf($circle, CircleRole::Owner));
        $user = $viewer($circle);

        $response = ($user === null ? $this : $this->actingAs($user))->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('pending_invites', null));
    }

    public function test_shows_those_who_can_see_the_members_everyone_in_the_circle_owners_then_admins_then_members(): void
    {
        $this->freezeSecond();
        $circle = Circle::factory()->create();
        // The newest member's row is written first, so the order is by when they joined, not by row or account.
        $newest = User::factory()->create();
        $circle->members()->attach($newest, ['role' => CircleRole::Member, 'created_at' => now()->addHours(3)]);
        $longestIn = User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']);
        $circle->members()->attach($longestIn, ['role' => CircleRole::Member]);
        $joinedAt = now();
        $this->travel(1)->hour();
        $admin = $this->memberOf($circle, CircleRole::Admin);
        $this->travel(1)->hour();
        $owner = $this->memberOf($circle, CircleRole::Owner);

        $response = $this->actingAs($admin)->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('members.data', 4)
            ->where('members.data.0.membership.id', $circle->membershipOf($owner)->id)
            ->where('members.data.1.membership.id', $circle->membershipOf($admin)->id)
            ->where('members.data.2', [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'membership' => [
                    'id' => $circle->membershipOf($longestIn)->id,
                    'role' => ['value' => 'member', 'label' => 'Member'],
                    'joined_at' => $joinedAt->toIso8601String(),
                ],
            ])
            ->where('members.data.3.membership.id', $circle->membershipOf($newest)->id));
    }

    public function test_sends_the_light_and_dark_map_tiles_from_config_for_the_circles_map(): void
    {
        $tiles = [
            'light' => ['url' => 'https://tiles.example.com/light/{z}/{x}/{y}.png', 'attribution' => '&copy; Example Maps', 'max_zoom' => 17],
            'dark' => ['url' => 'https://tiles.example.com/dark/{z}/{x}/{y}{r}.png', 'attribution' => '&copy; Example Dark Maps', 'max_zoom' => 20],
        ];
        config(['map.tiles' => $tiles]);

        $response = $this->get(route('circles.show', Circle::factory()->create()));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('map_tiles', $tiles));
    }

    public function test_shows_a_plain_member_who_else_is_in_the_circle_only_as_a_count(): void
    {
        $circle = Circle::factory()->create();
        $this->memberOf($circle, CircleRole::Owner);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Member))->get(route('circles.show', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('circle.members_count', 2)
            ->where('members', null));
    }

    public function test_pages_the_requests_to_join_ten_at_a_time_keeping_the_other_lists_pages(): void
    {
        $circle = Circle::factory()->create();
        $this->askToJoin($circle, 11);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->get(route('circles.show', [$circle, 'members_page' => 2]));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('pending_join_requests.data', 10)
            ->where('pending_join_requests.total', 11)
            ->where('pending_join_requests.next_page_url', fn (string $url): bool => str_contains($url, 'requests_page=2') && str_contains($url, 'members_page=2')));
    }

    public function test_opens_a_later_page_of_the_requests_to_join(): void
    {
        $circle = Circle::factory()->create();
        $this->askToJoin($circle, 11);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->get(route('circles.show', [$circle, 'requests_page' => 2]));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pending_join_requests.current_page', 2)
            ->has('pending_join_requests.data', 1));
    }

    public function test_shows_one_page_either_side_of_the_current_one_so_the_pages_fit_in_half_a_row(): void
    {
        $circle = Circle::factory()->create();
        $this->askToJoin($circle, 110);

        $response = $this->actingAs($this->memberOf($circle, CircleRole::Owner))->get(route('circles.show', [$circle, 'requests_page' => 6]));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where(
            'pending_join_requests.links',
            fn ($links): bool => collect($links)->slice(1, -1)->pluck('label')->all() === ['1', '2', '...', '5', '6', '7', '...', '10', '11'],
        ));
    }

    public function test_shows_one_page_either_side_for_invites_and_members_too(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        User::factory()->count(110)->create()->each(fn (User $invitee) => Approval::open(ApprovalType::Invite, $circle, $invitee, $owner));
        $circle->members()->attach(User::factory()->count(219)->create(), ['role' => CircleRole::Member]);
        $window = fn ($links): bool => collect($links)->slice(1, -1)->pluck('label')->all() === ['1', '2', '...', '5', '6', '7', '...', '10', '11'];

        $response = $this->actingAs($owner)->get(route('circles.show', [$circle, 'invites_page' => 6, 'members_page' => 6]));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pending_invites.links', $window)
            ->where('members.links', $window));
    }

    public function test_pages_the_invites_ten_at_a_time(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        User::factory()->count(11)->create()->each(fn (User $invitee) => Approval::open(ApprovalType::Invite, $circle, $invitee, $owner));

        $response = $this->actingAs($owner)->get(route('circles.show', [$circle, 'invites_page' => 2]));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('pending_invites.current_page', 2)
            ->where('pending_invites.total', 11)
            ->has('pending_invites.data', 1)
            ->where('pending_invites.prev_page_url', fn (string $url): bool => str_contains($url, 'invites_page=1')));
    }

    public function test_pages_the_members_twenty_at_a_time(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $circle->members()->attach(User::factory()->count(20)->create(), ['role' => CircleRole::Member]);

        $response = $this->actingAs($owner)->get(route('circles.show', [$circle, 'members_page' => 2]));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('members.current_page', 2)
            ->where('members.total', 21)
            ->has('members.data', 1)
            ->where('members.prev_page_url', fn (string $url): bool => str_contains($url, 'members_page=1')));
    }

    public function test_searches_the_members_by_name_finding_only_people_in_the_circle(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $grace = User::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']);
        $circle->members()->attach($grace, ['role' => CircleRole::Member]);
        $circle->members()->attach(User::factory()->create(['first_name' => 'Ada', 'last_name' => 'Lovelace']), ['role' => CircleRole::Member]);
        Circle::factory()->create()->members()->attach(User::factory()->create(['first_name' => 'Grace', 'last_name' => 'Hopper']), ['role' => CircleRole::Member]);

        $response = $this->actingAs($owner)->get(route('circles.show', [$circle, 'members_search' => '  grace hop ']));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('members.total', 1)
            ->where('members.data.0.membership.id', $circle->membershipOf($grace)->id)
            ->where('filters', ['members_search' => 'grace hop']));
    }

    public function test_keeps_the_member_search_on_its_pages(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $circle->members()->attach(User::factory()->count(21)->create(['first_name' => 'Grace']), ['role' => CircleRole::Member]);

        $response = $this->actingAs($owner)->get(route('circles.show', [$circle, 'members_search' => 'grace']));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('members.total', 21)
            ->where('members.next_page_url', fn (string $url): bool => str_contains($url, 'members_page=2') && str_contains($url, 'members_search=grace')));
    }

    public function test_shows_every_member_without_a_search(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $circle->members()->attach(User::factory()->count(2)->create(), ['role' => CircleRole::Member]);

        $response = $this->actingAs($owner)->get(route('circles.show', [$circle, 'members_search' => '   ']));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('members.total', 3)
            ->where('filters', ['members_search' => '']));
    }

    public function test_reloading_one_list_only_looks_that_list_up(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $this->askToJoin($circle, 1);
        Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $owner);
        $queries = [];

        $response = $this->actingAs($owner)->get(route('circles.show', $circle));

        $response->assertInertia(function (AssertableInertia $page) use (&$queries): void {
            DB::listen(function (QueryExecuted $query) use (&$queries): void {
                $queries[] = $query;
            });

            $page->reloadOnly('pending_join_requests', fn (AssertableInertia $reload) => $reload->has('pending_join_requests.data', 1));
        });
        $this->assertEmpty(array_filter($queries, fn (QueryExecuted $query): bool => str_contains($query->sql, 'case circle_user.role')), 'the members were looked up');
        $this->assertEmpty(array_filter($queries, fn (QueryExecuted $query): bool => in_array(ApprovalType::Invite->value, $query->bindings, true)), 'the invites were looked up');
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
                'status' => ['value' => 'approved', 'label' => 'Approved'],
            ]));
    }

    public function test_gives_staff_the_same_circle_details_on_the_edit_page(): void
    {
        $circle = Circle::factory()->create();

        $response = $this->actingAs(User::factory()->create(['role' => Role::Admin]))->get(route('circles.edit', $circle));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('circle.status', ['value' => 'approved', 'label' => 'Approved']));
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

    public function test_a_circle_a_member_creates_waits_for_staff_approval(): void
    {
        $creator = $this->memberWhoCanCreate();

        $this->actingAs($creator)->post(route('circles.store'), ['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);

        $approval = Circle::sole()->approval;
        $this->assertSame(ApprovalStatus::Pending, $approval->status);
        $this->assertTrue($approval->requester->is($creator));
    }

    public function test_emails_every_staff_member_that_a_new_circle_is_waiting(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => Role::Admin]);
        $superAdmin = User::factory()->create(['role' => Role::SuperAdmin]);
        $creator = $this->memberWhoCanCreate();

        $this->actingAs($creator)->post(route('circles.store'), ['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);

        Notification::assertSentTo([$admin, $superAdmin], NewCircleAwaitingApproval::class, fn (NewCircleAwaitingApproval $notification): bool => $notification->circle->is(Circle::sole())
            && $notification->requester->is($creator));
        Notification::assertNotSentTo($creator, NewCircleAwaitingApproval::class);
    }

    public function test_a_circle_staff_create_is_approved_by_them_straight_away_without_emailing_anyone(): void
    {
        Notification::fake();
        User::factory()->create(['role' => Role::SuperAdmin]);
        $staff = User::factory()->create(['role' => Role::Admin]);

        $this->actingAs($staff)->post(route('circles.store'), ['name' => 'Exchange', 'description' => 'Share skills and lend tools.']);

        $approval = Circle::sole()->approval;
        $this->assertSame(ApprovalStatus::Approved, $approval->status);
        $this->assertTrue($approval->decider->is($staff));
        Notification::assertNothingSent();
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

    public function test_editing_a_rejected_circle_sends_it_back_for_approval_and_emails_staff(): void
    {
        Notification::fake();
        $staff = User::factory()->create(['role' => Role::Admin]);
        $circle = Circle::factory()->rejected('Please say what the circle is for.')->create();
        $creator = $circle->approval->user;
        $owner = $this->memberOf($circle, CircleRole::Owner);

        $this->actingAs($owner)->put(route('circles.update', $circle), ['name' => $circle->name, 'description' => 'Swap tools and skills with your neighbours.']);

        $approval = $circle->approval->fresh();
        $this->assertSame(ApprovalStatus::Pending, $approval->status);
        $this->assertNull($approval->reason);
        $this->assertTrue($approval->user->is($creator));
        $this->assertTrue($approval->requester->is($owner));
        Notification::assertSentTo($staff, NewCircleAwaitingApproval::class, fn (NewCircleAwaitingApproval $notification): bool => $notification->circle->is($circle)
            && $notification->requester->is($owner)
            && $notification->resubmitted);
    }

    public function test_editing_an_approved_circle_keeps_it_approved_and_emails_no_one(): void
    {
        Notification::fake();
        User::factory()->create(['role' => Role::Admin]);
        $circle = Circle::factory()->create();

        $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), ['name' => $circle->name, 'description' => 'Swap tools and skills with your neighbours.']);

        $this->assertSame(ApprovalStatus::Approved, $circle->approval->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_editing_a_circle_still_waiting_for_approval_does_not_email_staff_again(): void
    {
        Notification::fake();
        User::factory()->create(['role' => Role::Admin]);
        $circle = Circle::factory()->pending()->create();

        $this->actingAs($this->memberOf($circle, CircleRole::Owner))->put(route('circles.update', $circle), ['name' => $circle->name, 'description' => 'Swap tools and skills with your neighbours.']);

        $this->assertSame(ApprovalStatus::Pending, $circle->approval->fresh()->status);
        Notification::assertNothingSent();
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
     * New users asking to join the circle.
     */
    private function askToJoin(Circle $circle, int $howMany): void
    {
        User::factory()->count($howMany)->create()->each(fn (User $asker) => Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker));
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
