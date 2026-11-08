<?php

namespace App\Policies;

use App\Models\Circle;
use App\Models\User;

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
     * Determine whether the user can see the circle's public page (name, description, join button). Guests can too.
     */
    public function view(?User $user, Circle $circle): bool
    {
        return true;
    }

    /**
     * Determine whether the user can see inside the circle: its channels, posts and members.
     */
    public function viewChannels(User $user, Circle $circle): bool
    {
        return $user->role->internal() || $circle->membershipOf($user) !== null;
    }

    /**
     * Determine whether the user can ask to join the circle.
     */
    public function requestToJoin(User $user, Circle $circle): bool
    {
        return $user->hasVerifiedEmail() && $circle->membershipOf($user) === null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role->internal()
            || ($user->hasVerifiedEmail() && $user->created_at->lte(now()->subMinutes(self::MINUTES_BEFORE_NEW_ACCOUNTS_CAN_CREATE)));
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
