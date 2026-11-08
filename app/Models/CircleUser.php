<?php

namespace App\Models;

use App\Enums\CircleRole;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CircleUser extends Pivot
{
    protected $table = 'circle_user';

    protected function casts(): array
    {
        return ['role' => CircleRole::class];
    }
}
