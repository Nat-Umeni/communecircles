<?php

namespace App\Http\Requests\Circle\Concerns;

use App\DTO\GeoNames\Place;
use App\Exceptions\GeoNamesException;
use App\Services\GeoNames\GeoNamesService;
use Closure;
use Illuminate\Contracts\Validation\Validator;

/**
 * A circle form's optional location: the form sends only the chosen place's GeoNames id, and the place's name,
 * region, country and coordinates are read from GeoNames, so they can't be made up.
 */
trait ChoosesPlace
{
    private ?Place $chosenPlace = null;

    /**
     * @return array<string, array<int, string>>
     */
    protected function placeRules(): array
    {
        return [
            'geonames_id' => ['nullable', 'integer', 'min:1'],
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
     * The chosen town or village, as GeoNames describes it, or null when no place was chosen.
     */
    public function place(): ?Place
    {
        return $this->chosenPlace;
    }

    /**
     * Whether the form chose a different place from the circle's current one. For a new circle, whether one was chosen.
     */
    public function placeChanged(): bool
    {
        return $this->chosenPlaceId() !== $this->route('circle')?->geonames_id;
    }

    private function chosenPlaceId(): ?int
    {
        return $this->filled('geonames_id') ? $this->integer('geonames_id') : null;
    }
}
