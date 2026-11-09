<?php

namespace App\Models;

use App\Enums\Role;
use App\Notifications\ResetPassword;
use App\Notifications\VerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Enums\ActivityEvent;
use Spatie\Activitylog\Models\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['first_name', 'last_name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /**
     * Stands in for the password's old and new values in the audit log.
     */
    private const string HIDDEN_IN_AUDIT_LOG = '[hidden]';

    /**
     * Record changes to the member's details in the audit log, with who made them. Only what actually
     * changed is logged; the remember token never is, since it rotates on every "remember me" log in.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'email', 'password', 'role', 'email_verified_at'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Never store the password in the audit log, not even hashed: a change to it is logged with its values
     * hidden, and it's left out when a member joins or is deleted.
     */
    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $changes = $activity->attribute_changes?->all() ?? [];

        if ($eventName === ActivityEvent::Updated->value && array_key_exists('password', $changes['attributes'] ?? [])) {
            $changes['attributes']['password'] = self::HIDDEN_IN_AUDIT_LOG;
            $changes['old']['password'] = self::HIDDEN_IN_AUDIT_LOG;
        } else {
            unset($changes['attributes']['password'], $changes['old']['password']);
        }

        $activity->attribute_changes = collect($changes);
    }

    /**
     * Email the member a link to confirm their email address, through the queue.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Email the member a password reset link, through the queue.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /**
     * Staff accounts: admins and super admins (Role::internal()).
     */
    #[Scope]
    protected function staff(Builder $query): void
    {
        $query->whereIn('role', array_filter(Role::cases(), fn (Role $role): bool => $role->internal()));
    }

    public function circles(): BelongsToMany
    {
        return $this->belongsToMany(Circle::class)
            ->using(CircleUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }
}
