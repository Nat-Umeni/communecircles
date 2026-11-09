<?php

namespace App\Models;

use App\DTO\GeoNames\Place;
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

    /**
     * Where the circle is: the town or village chosen from GeoNames, or null when none was chosen.
     */
    public function place(): ?Place
    {
        if ($this->geonames_id === null) {
            return null;
        }

        return new Place(
            geonamesId: $this->geonames_id,
            name: $this->place_name,
            region: $this->region,
            countryCode: $this->country_code,
            countryName: $this->country_name,
            latitude: $this->latitude,
            longitude: $this->longitude,
        );
    }

    /**
     * Set the circle's location to the place, or clear it with null. Save the circle afterwards.
     */
    public function moveTo(?Place $place): void
    {
        $this->forceFill([
            'geonames_id' => $place?->geonamesId,
            'place_name' => $place?->name,
            'region' => $place?->region,
            'country_code' => $place?->countryCode,
            'country_name' => $place?->countryName,
            'latitude' => $place?->latitude,
            'longitude' => $place?->longitude,
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
