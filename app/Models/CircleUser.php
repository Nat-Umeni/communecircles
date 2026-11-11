<?php

namespace App\Models;

use App\Enums\Circle\CircleRole;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Someone's membership of a circle, with their role in it.
 */
class CircleUser extends Pivot
{
    use LogsActivity;

    protected $table = 'circle_user';

    /**
     * NOTE: circle_user has its own id; without this the id isn't read back after joining, and the audit log
     * couldn't say which membership an entry is about.
     */
    public $incrementing = true;

    /**
     * Record people joining, changing role and leaving in the audit log, with who did it; only what changed.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['circle_id', 'user_id', 'role'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['role' => CircleRole::class];
    }
}
