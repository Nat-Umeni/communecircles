<?php

namespace App\Models\Concerns;

use App\Enums\Approval\ApprovalType;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * The approvals of something waiting for approval (an App\Models\Contracts\Approvable), of every type, and the
 * lookups the pages need. Nothing here assumes what the thing is.
 */
trait HasApprovals
{
    /**
     * @return MorphMany<Approval, $this>
     */
    public function approvals(): MorphMany
    {
        // NOTE: chaperone() gives each approval loaded through here this model as its approvable, without a query, so
        // checking what can be done with a page of them (ApprovalPolicy) doesn't look the same thing up for each.
        return $this->morphMany(Approval::class, 'approvable')->chaperone('approvable');
    }

    /**
     * Its approvals of the type still waiting for a decision, the longest-waiting first, with who each is about. A
     * query, so it can be paged.
     *
     * @return MorphMany<Approval, $this>
     */
    public function waitingApprovals(ApprovalType $type): MorphMany
    {
        return $this->approvals()->pending()->where('type', $type)->with('user')->oldest('updated_at');
    }

    /**
     * The person's approval of the type (there's at most one per type, thing and person), whatever its status; null
     * when they have none, or for a guest.
     */
    public function approvalFor(ApprovalType $type, ?User $user): ?Approval
    {
        return $user === null ? null : $this->approvals()->where('type', $type)->where('user_id', $user->id)->first();
    }
}
