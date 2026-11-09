<?php

namespace Database\Factories;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Models\Circle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Circle>
 */
class CircleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'description' => fake()->sentence(),
        ];
    }

    /**
     * A circle at a GeoNames place, or near it with a name of its own, e.g. Circle::factory()->in($totnes)->create().
     */
    public function in(Place $place, ?string $name = null): static
    {
        return $this->at(Location::at($place, $name));
    }

    /**
     * A circle at the location, e.g. Circle::factory()->at(Location::named('Fernhill Farm', 'GB'))->create().
     */
    public function at(Location $location): static
    {
        return $this->afterMaking(fn (Circle $circle) => $circle->locate($location));
    }
}
