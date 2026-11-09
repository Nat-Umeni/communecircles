<?php

namespace Database\Factories;

use App\DTO\GeoNames\Place;
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
     * A circle located at the place, e.g. Circle::factory()->in($totnes)->create().
     */
    public function in(Place $place): static
    {
        return $this->afterMaking(fn (Circle $circle) => $circle->moveTo($place));
    }
}
