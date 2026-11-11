<?php

namespace App\Policies;

use App\Enums\Approval\ApprovalStatus;
use App\Enums\Approval\ApprovalType;
use App\Models\Approval;
use App\Models\User;

class ApprovalPolicy
{
    /**
     * Determine whether the user can see the queue of what's waiting for staff approval: staff only.
     */
    public function viewAny(User $user): bool
    {
        return $user->role->internal();
    }

    /**
     * Determine whether the user can approve or reject it, while it's still waiting: staff for the kinds they decide
     * (ApprovalType::decidedByStaff(), e.g. something new), whoever runs what's being joined (its managedBy(), so not
     * staff) for a request to join, and only the person invited for an invite.
     */
    public function decide(User $user, Approval $approval): bool
    {
        if ($approval->status !== ApprovalStatus::Pending) {
            return false;
        }

        if ($approval->type->decidedByStaff()) {
            return $user->role->internal();
        }

        return match ($approval->type) {
            ApprovalType::JoinRequest => $approval->approvable->managedBy($user),
            ApprovalType::Invite => $approval->user->is($user),
        };
    }

    /**
     * Determine whether the user can cancel it while it's still waiting: only an invite, by anyone who runs what they
     * were invited into (its managedBy()), not just whoever sent it.
     */
    public function cancel(User $user, Approval $approval): bool
    {
        if ($approval->status !== ApprovalStatus::Pending || $approval->type !== ApprovalType::Invite) {
            return false;
        }

        return $approval->approvable->managedBy($user);
    }
}
