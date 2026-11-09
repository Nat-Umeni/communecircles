<?php

namespace App\Models;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
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
