<?php

namespace App\Policies;

use App\Enums\ApprovalType;
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

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Circle $circle): bool
    {
        return $user->role->internal()
            || ($circle->membershipOf($user)?->role->hasManagerRole() ?? false);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Circle $circle): bool
    {
        return $user->role->internal();
    }
}
