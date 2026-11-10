<?php

namespace Tests\Feature\Models;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\AuditEvent;
use App\Enums\CircleRole;
use App\Enums\Role;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * NOTE: circles are what get approved for now (new circles, join requests, invites), so these use one.
 */
class ApprovalTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_opens_an_approval_for_a_new_circle_and_logs_who_asked(): void
    {
        $circle = Circle::factory()->withoutApproval()->create();
        $creator = User::factory()->create();

        $approval = Approval::open(ApprovalType::NewCircle, $circle, $creator, $creator);

        $this->assertSame(ApprovalType::NewCircle, $approval->type);
        $this->assertSame(ApprovalStatus::Pending, $approval->status);
        $this->assertTrue($approval->approvable->is($circle));
        $this->assertTrue($approval->user->is($creator));
        $this->assertTrue($approval->requester->is($creator));
        $this->assertNull($approval->decider);
        $this->assertNull($approval->decided_at);

        $activity = $this->lastActivity();
        $this->assertSame(AuditEvent::ApprovalRequested->value, $activity->event);
        $this->assertTrue($activity->subject->is($circle));
        $this->assertTrue($activity->causer->is($creator));
        $this->assertSame('new_circle', $activity->getProperty('type'));
    }

    public function test_an_invite_is_about_the_person_invited_and_requested_by_whoever_sent_it(): void
    {
        $circle = Circle::factory()->create();
        $invitee = User::factory()->create();
        $owner = User::factory()->create();

        $approval = Approval::open(ApprovalType::Invite, $circle, $invitee, $owner);

        $this->assertTrue($approval->user->is($invitee));
        $this->assertTrue($approval->requester->is($owner));
        $this->assertTrue($this->lastActivity()->causer->is($owner));
    }

    public function test_approving_records_who_decided_and_when_and_logs_it(): void
    {
        $this->freezeSecond();
        $circle = Circle::factory()->pending()->create();
        $staff = User::factory()->create(['role' => Role::Admin]);

        $circle->approval->approve($staff);

        $approval = $circle->approval->fresh();
        $this->assertSame(ApprovalStatus::Approved, $approval->status);
        $this->assertTrue($approval->decider->is($staff));
        $this->assertTrue($approval->decided_at->equalTo(now()));

        $activity = $this->lastActivity();
        $this->assertSame(AuditEvent::Approved->value, $activity->event);
        $this->assertTrue($activity->subject->is($circle));
        $this->assertTrue($activity->causer->is($staff));
        $this->assertSame('new_circle', $activity->getProperty('type'));
    }

    public function test_approving_runs_what_the_approved_thing_does_on_approval(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        $approval = Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);

        $approval->approve(User::factory()->create());

        $this->assertSame(CircleRole::Member, $circle->membershipOf($asker)?->role);
    }

    public function test_rejecting_records_who_decided_and_why_and_logs_it(): void
    {
        $circle = Circle::factory()->pending()->create();
        $staff = User::factory()->create(['role' => Role::Admin]);

        $circle->approval->reject($staff, 'There is already a circle for tool sharing.');

        $approval = $circle->approval->fresh();
        $this->assertSame(ApprovalStatus::Rejected, $approval->status);
        $this->assertSame('There is already a circle for tool sharing.', $approval->reason);
        $this->assertTrue($approval->decider->is($staff));

        $activity = $this->lastActivity();
        $this->assertSame(AuditEvent::Rejected->value, $activity->event);
        $this->assertTrue($activity->causer->is($staff));
        $this->assertSame('There is already a circle for tool sharing.', $activity->getProperty('reason'));
        $this->assertSame('new_circle', $activity->getProperty('type'));
    }

    public function test_rejecting_adds_no_one_to_the_circle(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();

        Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker)->reject(User::factory()->create());

        $this->assertNull($circle->membershipOf($asker));
    }

    public function test_can_be_rejected_without_a_reason(): void
    {
        $circle = Circle::factory()->pending()->create();

        $circle->approval->reject(User::factory()->create(['role' => Role::Admin]));

        $this->assertNull($circle->approval->fresh()->reason);
    }

    public function test_cancelling_records_who_took_it_back_and_when_and_logs_it(): void
    {
        $this->freezeSecond();
        $circle = Circle::factory()->create();
        $owner = User::factory()->create();
        $circle->members()->attach($owner, ['role' => CircleRole::Owner]);
        $invite = Approval::open(ApprovalType::Invite, $circle, User::factory()->create(), $owner);

        $invite->cancel($owner);

        $invite = $invite->fresh();
        $this->assertSame(ApprovalStatus::Cancelled, $invite->status);
        $this->assertTrue($invite->decider->is($owner));
        $this->assertTrue($invite->decided_at->equalTo(now()));
        $this->assertNull($invite->reason);

        $activity = $this->lastActivity();
        $this->assertSame(AuditEvent::Cancelled->value, $activity->event);
        $this->assertTrue($activity->causer->is($owner));
        $this->assertTrue($activity->subject->is($circle));
        $this->assertSame('invite', $activity->getProperty('type'));
    }

    public function test_asking_again_after_a_rejection_reopens_the_same_approval(): void
    {
        $circle = Circle::factory()->create();
        $asker = User::factory()->create();
        $first = Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);
        $first->reject(User::factory()->create(), 'Please say a bit about yourself first.');

        $again = Approval::open(ApprovalType::JoinRequest, $circle, $asker, $asker);

        $this->assertSame($first->id, $again->id);
        $this->assertSame(ApprovalStatus::Pending, $again->status);
        $this->assertNull($again->decider);
        $this->assertNull($again->decided_at);
        $this->assertNull($again->reason);
        $this->assertSame(1, $circle->approvals()->where('type', ApprovalType::JoinRequest)->count());
    }

    public function test_each_person_and_kind_of_approval_gets_its_own_row(): void
    {
        $circle = Circle::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();

        $firstRequest = Approval::open(ApprovalType::JoinRequest, $circle, $first, $first);
        $secondRequest = Approval::open(ApprovalType::JoinRequest, $circle, $second, $second);
        $invite = Approval::open(ApprovalType::Invite, $circle, $first, User::factory()->create());

        $this->assertCount(3, collect([$firstRequest->id, $secondRequest->id, $invite->id])->unique());
    }

    public function test_pending_lists_only_what_is_still_waiting(): void
    {
        $circle = Circle::factory()->create();
        $decider = User::factory()->create();
        [$waiting, $approved, $rejected] = User::factory()->count(3)->create();
        $stillWaiting = Approval::open(ApprovalType::JoinRequest, $circle, $waiting, $waiting);
        Approval::open(ApprovalType::JoinRequest, $circle, $approved, $approved)->approve($decider);
        Approval::open(ApprovalType::JoinRequest, $circle, $rejected, $rejected)->reject($decider);

        $this->assertSame([$stillWaiting->id], Approval::query()->pending()->pluck('id')->all());
    }

    /**
     * The newest audit log entry.
     */
    private function lastActivity(): Activity
    {
        return Activity::query()->latest('id')->firstOrFail();
    }
}
