<?php

namespace App\Policies;

use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Models\Approval;
use App\Models\Circle;
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
     * Determine whether the user can approve or reject it, while it's still waiting: staff for something new, the
     * owners and admins of what's being joined (not staff) for a request to join, and only the person invited for an
     * invite.
     */
    public function decide(User $user, Approval $approval): bool
    {
        if ($approval->status !== ApprovalStatus::Pending) {
            return false;
        }

        /** @var Circle $circle Circles are the only thing approvals are about so far. */
        $circle = $approval->approvable;

        return match ($approval->type) {
            ApprovalType::NewCircle => $user->role->internal(),
            ApprovalType::JoinRequest => $circle->membershipOf($user)?->role->hasManagerRole() ?? false,
            ApprovalType::Invite => $approval->user->is($user),
        };
    }
}
