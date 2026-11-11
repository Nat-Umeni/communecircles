<?php

namespace Database\Factories;

use App\DTO\GeoNames\Place;
use App\DTO\Location;
use App\Enums\Approval\ApprovalStatus;
use App\Models\Approval;
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
     * Circles are approved unless a test says otherwise, with pending() or rejected().
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (Circle $circle) => Approval::factory()->approved()->for($circle, 'approvable')->create());
    }

    /**
     * A circle with no approval at all yet, e.g. to open one in a test.
     */
    public function withoutApproval(): static
    {
        return $this->afterCreating(fn (Circle $circle) => $circle->approval()->delete());
    }

    /**
     * A circle still waiting for staff to approve it.
     */
    public function pending(): static
    {
        return $this->afterCreating(fn (Circle $circle) => $circle->approval->update(['status' => ApprovalStatus::Pending, 'decided_at' => null]));
    }

    /**
     * A circle staff turned down, optionally saying why.
     */
    public function rejected(?string $reason = null): static
    {
        return $this->afterCreating(fn (Circle $circle) => $circle->approval->update(['status' => ApprovalStatus::Rejected, 'reason' => $reason]));
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
