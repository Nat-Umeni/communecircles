<?php

namespace App\Http\Requests\Circle;

use App\DTO\Coordinates;
use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Exceptions\GeoNamesException;
use App\Models\Circle;
use App\Services\Countries;
use App\Services\GeoNames\GeoNamesService;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * What creating and editing a circle share: its optional location. That's a name of its own (e.g. "Fernhill Farm"),
 * the nearest town or village from GeoNames, or both, and optionally its own exact spot. For a place, the form sends
 * only its GeoNames id; its name, region, country and coordinates are read from GeoNames, so they can't be made up. A
 * name with no place needs its country instead, and an exact spot needs a name or a place with it.
 */
abstract class CircleRequest extends FormRequest
{
    private ?Place $chosenPlace = null;

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function locationRules(): array
    {
        return [
            'location_name' => ['nullable', 'string', 'max:255'],
            'geonames_id' => ['nullable', 'integer', 'min:1'],
            'country_code' => [
                Rule::requiredIf(fn (): bool => $this->filled('location_name') && ! $this->filled('geonames_id')),
                'nullable',
                'string',
                Rule::in(array_keys(Countries::all())),
            ],
            'latitude' => [
                'nullable',
                'required_with:longitude',
                Rule::prohibitedIf(fn (): bool => ! $this->filled('location_name') && ! $this->filled('geonames_id')),
                'numeric',
                'between:-90,90',
            ],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'country_code.required' => 'Choose the country the place is in.',
            'latitude.required_with' => 'Give both the latitude and the longitude, or neither.',
            'longitude.required_with' => 'Give both the latitude and the longitude, or neither.',
            'latitude.prohibited' => 'Give the place a name or choose its nearest town to go with its exact spot.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'location_name' => 'place name',
            'country_code' => 'country',
        ];
    }

    /**
     * Once the other rules pass, look a newly chosen place up in GeoNames. The circle's current place isn't
     * looked up again, so a circle can still be edited while GeoNames is unavailable.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(GeoNamesService $geoNames): array
    {
        return [
            function (Validator $validator) use ($geoNames): void {
                if ($validator->errors()->isNotEmpty() || $this->chosenPlaceId() === null || ! $this->placeChanged()) {
                    return;
                }

                try {
                    $this->chosenPlace = $geoNames->findPlace($this->chosenPlaceId());
                } catch (GeoNamesException $exception) {
                    report($exception);
                    $validator->errors()->add('geonames_id', "The place can't be checked right now. Try again in a little while, or leave the location empty.");

                    return;
                }

                if ($this->chosenPlace === null) {
                    $validator->errors()->add('geonames_id', 'Choose a town or village from the list, or leave the location empty.');
                }
            },
        ];
    }

    /**
     * Where the circle is, from the form: at (or near) the chosen place, or just a name in a country, with its exact
     * spot if the form gives one. Null when the form leaves the location empty.
     */
    public function location(): ?Location
    {
        $name = $this->filled('location_name') ? $this->string('location_name')->value() : null;
        $place = $this->placeChanged() ? $this->chosenPlace : $this->circleBeingEdited()?->location()?->place;
        $coordinates = $this->filled('latitude') ? new Coordinates($this->float('latitude'), $this->float('longitude')) : null;

        if ($place !== null) {
            return Location::at($place, $name, $coordinates);
        }

        return $name === null ? null : Location::named($name, $this->string('country_code')->value(), $coordinates);
    }

    /**
     * Whether the form chose a different place from the circle's current one. For a new circle, whether one was chosen.
     */
    private function placeChanged(): bool
    {
        return $this->chosenPlaceId() !== $this->circleBeingEdited()?->geonames_id;
    }

    private function chosenPlaceId(): ?int
    {
        return $this->filled('geonames_id') ? $this->integer('geonames_id') : null;
    }

    /**
     * The circle the form edits (from the {circle} route parameter), or null when the form creates one.
     */
    private function circleBeingEdited(): ?Circle
    {
        $circle = $this->route('circle');

        return $circle instanceof Circle ? $circle : null;
    }
}
