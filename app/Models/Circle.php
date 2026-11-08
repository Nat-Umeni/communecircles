<?php

namespace App\Models;

use App\Enums\CircleRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Circle extends Model
{
    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CircleUser::class)
            ->withPivot('role')
            ->withTimestamps()
            ->wherePivot('role', CircleRole::Owner->value);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CircleUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }
}
