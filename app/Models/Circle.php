<?php

namespace App\Models;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Enums\ApprovalStatus;
use App\Enums\ApprovalType;
use App\Enums\CircleRole;
use App\Models\Concerns\HasPublicId;
use App\Models\Contracts\Approvable;
use Database\Factories\CircleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['name', 'description'])]
class Circle extends Model implements Approvable
{
    /** @use HasFactory<CircleFactory> */
    use HasFactory, HasPublicId, LogsActivity;

    /**
     * Record changes to the circle's details and location in the audit log, with who made them; only what changed.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'description',
                'location_name', 'geonames_id', 'place_name', 'region', 'country_code', 'country_name', 'latitude', 'longitude',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * @return MorphMany<Approval, $this>
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * Its approval as a new circle: staff approve new circles before they're listed.
     *
     * @return MorphOne<Approval, $this>
     */
    public function approval(): MorphOne
    {
        return $this->morphOne(Approval::class, 'approvable')->where('type', ApprovalType::NewCircle);
    }

    /**
     * An approved request to join or invite adds the person as a member, unless they're already in the circle.
     */
    public function whenApproved(Approval $approval): void
    {
        if ($approval->type->addsMember() && $this->membershipOf($approval->user) === null) {
            $this->members()->attach($approval->user, ['role' => CircleRole::Member]);
        }
    }

    /**
     * @return array{name: string, description: string, url: string}
     */
    public function approvalSummary(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'url' => route('circles.show', $this),
        ];
    }

    /**
     * Whether staff have approved it; until then only its members and staff can see it.
     */
    public function isApproved(): bool
    {
        return $this->approval?->status === ApprovalStatus::Approved;
    }

    /**
     * Only circles staff have approved.
     */
    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->whereRelation('approval', 'status', ApprovalStatus::Approved);
    }

    public function owners(): BelongsToMany
    {
        return $this->members()->wherePivot('role', CircleRole::Owner->value);
    }

    /**
     * Its owners and admins, who run the circle and decide on requests to join.
     */
    public function managers(): BelongsToMany
    {
        return $this->members()->wherePivotIn(
            'role',
            array_map(fn (CircleRole $role): string => $role->value, array_filter(CircleRole::cases(), fn (CircleRole $role): bool => $role->hasManagerRole())),
        );
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(CircleUser::class)
            ->withPivot('id', 'role')
            ->withTimestamps();
    }

    /**
     * The user's membership of this circle (their circle_user row), or null when they aren't in it.
     */
    public function membershipOf(User $user): ?CircleUser
    {
        return $this->members()->whereKey($user->id)->first()?->pivot;
    }

    /**
     * Where the circle is: its own name for its place, the nearest GeoNames town or village, or both. Null when
     * it hasn't said.
     */
    public function location(): ?Location
    {
        if ($this->location_name === null && $this->geonames_id === null) {
            return null;
        }

        $place = $this->geonames_id === null ? null : new Place(
            geonamesId: $this->geonames_id,
            name: $this->place_name,
            region: $this->region,
            countryCode: $this->country_code,
            countryName: $this->country_name,
            latitude: $this->latitude,
            longitude: $this->longitude,
        );

        return new Location($this->location_name, $place, $this->country_code, $this->country_name);
    }

    /**
     * Set where the circle is, or clear it with null. Save the circle afterwards.
     */
    public function locate(?Location $location): void
    {
        $this->forceFill([
            'location_name' => $location?->name,
            'geonames_id' => $location?->place?->geonamesId,
            'place_name' => $location?->place?->name,
            'region' => $location?->place?->region,
            'country_code' => $location?->countryCode,
            'country_name' => $location?->countryName,
            'latitude' => $location?->place?->latitude,
            'longitude' => $location?->place?->longitude,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'geonames_id' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}
