<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Enums\Role;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Closure;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApprovalControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_index_renders_the_approvals_page(): void
    {
        $response = $this->actingAs($this->staff())->get(route('approvals.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->component('approvals/Index'));
    }

    public function test_lists_new_circles_waiting_for_approval_oldest_first_with_who_asked(): void
    {
        $this->freezeSecond();
        $older = Circle::factory()->pending()->create(['name' => 'Allotment', 'description' => 'Grow food together.']);
        $this->travel(1)->hour();
        $newer = Circle::factory()->pending()->create(['name' => 'Exchange']);
        Circle::factory()->create();
        Circle::factory()->rejected()->create();
        $asker = User::factory()->create();
        Approval::open(ApprovalType::JoinRequest, Circle::factory()->create(), $asker, $asker);

        $response = $this->actingAs($this->staff())->get(route('approvals.index'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->has('approvals.data', 2)
            ->where('approvals.data.0', [
                'id' => $older->approval->id,
                'type' => ['value' => ApprovalType::NewCircle->value, 'label' => 'New circle'],
                'requested_at' => $older->approval->updated_at->toIso8601String(),
                'subject' => ['name' => 'Allotment', 'description' => 'Grow food together.', 'url' => route('circles.show', $older)],
                'requester' => $older->approval->requester->only('first_name', 'last_name'),
            ])
            ->where('approvals.data.1.id', $newer->approval->id));
    }

    public function test_the_queue_shows_no_one_for_a_requester_whose_account_was_deleted(): void
    {
        $circle = Circle::factory()->pending()->create();
        $circle->approval->update(['requested_by' => null]);

        $response = $this->actingAs($this->staff())->get(route('approvals.index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->where('approvals.data.0.requester', null));
    }

    public function test_members_cannot_see_the_queue(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('approvals.index'));

        $response->assertForbidden();
    }

    public function test_staff_approve_a_new_circle_and_go_back_to_where_they_were(): void
    {
        $circle = Circle::factory()->pending()->create();
        $staff = $this->staff();

        $response = $this->actingAs($staff)->from(route('approvals.index'))->post(route('approvals.approve', $circle->approval));

        $response->assertRedirect(route('approvals.index'));
        $response->assertInertiaFlash('status', 'Circle approved.');
        $this->assertSame(ApprovalStatus::Approved, $circle->approval->fresh()->status);
        $this->assertTrue($circle->approval->fresh()->decider->is($staff));
    }

    public function test_a_circles_owner_accepts_a_request_to_join_and_the_person_joins(): void
    {
        $circle = Circle::factory()->create();
        $owner = $this->memberOf($circle, CircleRole::Owner);
        $asker = User::factory()->create();
        $request = Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);

        $response = $this->actingAs($owner)->from(route('circles.show', $circle))->post(route('approvals.approve', $request));

        $response->assertRedirect(route('circles.show', $circle));
        $response->assertInertiaFlash('status', 'Request accepted.');
        $this->assertSame(CircleRole::Member, $circle->membershipOf($asker)?->role);
    }

    public function test_the_person_invited_accepts_and_joins_the_circle(): void
    {
        $circle = Circle::factory()->create();
        $invitee = User::factory()->create();
        $invite = Approval::open(ApprovalType::Invite, $circle, $invitee, $this->memberOf($circle, CircleRole::Owner));

        $response = $this->actingAs($invitee)->post(route('approvals.approve', $invite));

        $response->assertInertiaFlash('status', "You've joined the circle.");
        $this->assertSame(CircleRole::Member, $circle->membershipOf($invitee)?->role);
    }

    public function test_staff_reject_a_new_circle_with_a_reason(): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->actingAs($this->staff())->from(route('approvals.index'))
            ->post(route('approvals.reject', $circle->approval), ['reason' => 'There is already a circle for tool sharing.']);

        $response->assertRedirect(route('approvals.index'));
        $response->assertInertiaFlash('status', 'Circle rejected.');
        $approval = $circle->approval->fresh();
        $this->assertSame(ApprovalStatus::Rejected, $approval->status);
        $this->assertSame('There is already a circle for tool sharing.', $approval->reason);
    }

    public function test_someone_who_cannot_decide_cannot_approve(): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->actingAs(User::factory()->create())->post(route('approvals.approve', $circle->approval));

        $response->assertForbidden();
        $this->assertSame(ApprovalStatus::Pending, $circle->approval->fresh()->status);
    }

    /**
     * @param  Closure(Approval): string  $url
     */
    #[DataProvider('membersOnlyActions')]
    public function test_guests_are_sent_to_log_in(string $method, Closure $url): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->{$method}($url($circle->approval));

        $response->assertRedirect(route('login'));
    }

    /**
     * @return array<string, array{string, Closure(Approval): string}>
     */
    public static function membersOnlyActions(): array
    {
        return [
            'the queue' => ['get', fn (): string => route('approvals.index')],
            'approving' => ['post', fn (Approval $approval): string => route('approvals.approve', $approval)],
            'rejecting' => ['post', fn (Approval $approval): string => route('approvals.reject', $approval)],
        ];
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
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
