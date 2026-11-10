<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\AuditEvent;
use App\Models\Contracts\Approvable;
use Database\Factories\ApprovalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * An approval of some type (App\Enums\ApprovalType) for the thing waiting for approval (an Approvable), about one
 * person. Every request and decision is also written to the audit log, against the thing waiting for approval.
 */
#[Fillable(['type', 'user_id', 'status', 'requested_by', 'decided_by', 'decided_at', 'reason'])]
class Approval extends Model
{
    /** @use HasFactory<ApprovalFactory> */
    use HasFactory;

    /**
     * Ask for an approval about someone, e.g. open(ApprovalType::Invite, $thing, for: $invitee, by: $inviter).
     * There's one per type, thing and person, so asking again, e.g. after a rejection, reopens it.
     */
    public static function open(ApprovalType $type, Approvable&Model $about, User $for, User $by): self
    {
        $approval = $about->approvals()->updateOrCreate(['type' => $type, 'user_id' => $for->id], [
            'status' => ApprovalStatus::Pending,
            'requested_by' => $by->id,
            'decided_by' => null,
            'decided_at' => null,
            'reason' => null,
        ]);

        $approval->log(AuditEvent::ApprovalRequested, $by);

        return $approval;
    }

    /**
     * Approve it, then let the thing waiting for approval act on it (its whenApproved()), e.g. letting in the
     * person who asked to join.
     */
    public function approve(User $by): void
    {
        $this->decide(ApprovalStatus::Approved, $by);
        $this->approvable->whenApproved($this);
        $this->log(AuditEvent::Approved, $by);
    }

    /**
     * Turn it down, optionally saying why; the reason is shown to whoever asked.
     */
    public function reject(User $by, ?string $reason = null): void
    {
        $this->decide(ApprovalStatus::Rejected, $by, $reason);
        $this->log(AuditEvent::Rejected, $by, ['reason' => $reason]);
    }

    /**
     * The thing waiting for approval. Ask it through the Approvable contract; never assume what it is.
     *
     * @return MorphTo<Approvable&Model, $this>
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Who it's about: whoever created the new thing, the person asking to join, or the person invited.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * Approvals still waiting for a decision.
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', ApprovalStatus::Pending);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ApprovalType::class,
            'status' => ApprovalStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Write the event to the audit log against the thing waiting for approval, with the approval's type.
     *
     * @param  array<string, mixed>  $properties
     */
    private function log(AuditEvent $event, User $by, array $properties = []): void
    {
        activity()->performedOn($this->approvable)->causedBy($by)->event($event->value)
            ->withProperties(['type' => $this->type->value, ...$properties])
            ->log($event->value);
    }

    private function decide(ApprovalStatus $status, User $by, ?string $reason = null): void
    {
        $this->update([
            'status' => $status,
            'decided_by' => $by->id,
            'decided_at' => now(),
            'reason' => $reason,
        ]);
    }
}
