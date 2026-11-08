<?php

namespace App\Models;

use App\Enums\CircleRole;
use App\Models\Concerns\HasPublicId;
use Database\Factories\CircleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'description'])]
class Circle extends Model
{
    /** @use HasFactory<CircleFactory> */
    use HasFactory, HasPublicId;

    public function owners(): BelongsToMany
    {
        return $this->members()->wherePivot('role', CircleRole::Owner->value);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CircleUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * The user's membership of this circle (their circle_user row), or null when they aren't in it.
     */
    public function membershipOf(User $user): ?CircleUser
    {
        return $this->members()->whereKey($user->id)->first()?->pivot;
    }
}
