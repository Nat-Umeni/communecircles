<?php

namespace App\Policies;

use App\Enums\Approval\ApprovalStatus;
use App\Enums\Approval\ApprovalType;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CirclePolicy
{
    /**
     * How long a new account waits before it can create a circle, to slow down sign-up bots.
     */
    private const int MINUTES_BEFORE_NEW_ACCOUNTS_CAN_CREATE = 5;

    /**
     * Determine whether the user can browse and search circles. Guests can too, since every circle is public for now.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can see the circle's page. An approved circle's is public, guests included; until
     * it's approved, only its members and staff can see it, and anyone else gets "not found", as if it didn't exist.
     */
    public function view(?User $user, Circle $circle): Response
    {
        if ($circle->isApproved() || ($user !== null && ($user->role->internal() || $circle->membershipOf($user) !== null))) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can see inside the circle: its channels, posts and members.
     */
    public function viewChannels(User $user, Circle $circle): bool
    {
        return $user->role->internal() || $circle->membershipOf($user) !== null;
    }

    /**
     * Determine whether the user can see who is in the circle: its owners and admins, and staff.
     */
    public function viewMembers(User $user, Circle $circle): bool
    {
        return $user->role->internal() || $circle->managedBy($user);
    }

    /**
     * Determine whether the user can see the circle's requests to join and invites still waiting: staff, who can see
     * everything in a circle, and whoever runs it (its owners and admins). Deciding on them is ApprovalPolicy's.
     */
    public function viewWaitingApprovals(User $user, Circle $circle): bool
    {
        return $user->role->internal() || $circle->managedBy($user);
    }

    /**
     * Determine whether the user can ask to join the circle: an approved circle they're not in, with a confirmed
     * email, and no request or invite of theirs still waiting. After a refusal or a declined invite they can ask again.
     */
    public function requestToJoin(User $user, Circle $circle): bool
    {
        return $circle->isApproved()
            && $user->hasVerifiedEmail()
            && $circle->membershipOf($user) === null
            && ! $circle->approvals()
                ->pending()
                ->whereIn('type', [ApprovalType::JoinRequest, ApprovalType::Invite])
                ->where('user_id', $user->id)
                ->exists();
    }

    /**
     * Determine whether the user can invite people into the circle: its owners and admins, once it's approved.
     * Staff can't, since who's in a circle is the circle's own business.
     */
    public function invite(User $user, Circle $circle): bool
    {
        return $circle->isApproved() && $circle->managedBy($user);
    }

    /**
     * Determine whether the user can create a circle. Staff can straight away; everyone else needs a confirmed
     * email and an account a few minutes old, and is told which one is missing (email first, since they can act on it).
     */
    public function create(User $user): Response
    {
        if ($user->role->internal()) {
            return Response::allow();
        }

        if (! $user->hasVerifiedEmail()) {
            return Response::deny('Confirm your email address to create a circle.');
        }

        if ($user->created_at->gt(now()->subMinutes(self::MINUTES_BEFORE_NEW_ACCOUNTS_CAN_CREATE))) {
            return Response::deny(sprintf(
                'New accounts can create a circle %d minutes after joining.',
                self::MINUTES_BEFORE_NEW_ACCOUNTS_CAN_CREATE,
            ));
        }

        // One at a time, so no one account can fill the approvals queue.
        if ($user->hasCircleWaitingForApproval()) {
            return Response::deny('You already have a circle waiting for approval. You can start another once the team has decided on it.');
        }

        return Response::allow();
    }

    /**
     * Staff, and the circle's owners and admins. Saving a rejected circle sends it back to the team, so like starting
     * one it waits while its starter has another circle waiting for approval.
     */
    public function update(User $user, Circle $circle): bool
    {
        if ($user->role->internal()) {
            return true;
        }

        if (! $circle->managedBy($user)) {
            return false;
        }

        return ! ($circle->approval->status === ApprovalStatus::Rejected && $circle->approval->user->hasCircleWaitingForApproval());
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Circle $circle): bool
    {
        return $user->role->internal();
    }
}
