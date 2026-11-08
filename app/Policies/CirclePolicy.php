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
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->role->internal();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Circle $circle): bool
    {
        return $user->role->internal() || $circle->membershipOf($user) !== null;
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
