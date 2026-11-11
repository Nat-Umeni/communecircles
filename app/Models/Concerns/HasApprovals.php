<?php

namespace App\Models\Concerns;

use App\Enums\Approval\ApprovalType;
use App\Models\Approval;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Its approvals of the type still waiting for a decision, the longest-waiting first, with who each is about.
     *
     * @return Collection<int, Approval>
     */
    public function waitingApprovals(ApprovalType $type): Collection
    {
        return $this->approvals()->pending()->where('type', $type)->with('user')->oldest('updated_at')->get();
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
