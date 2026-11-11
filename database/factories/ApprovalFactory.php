<?php

namespace Database\Factories;

use App\Enums\Approval\ApprovalStatus;
use App\Enums\Approval\ApprovalType;
use App\Models\Approval;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Approval>
 */
class ApprovalFactory extends Factory
{
    /**
     * Define the model's default state: a new circle waiting for approval, asked for by its creator.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ApprovalType::NewCircle,
            'approvable_type' => (new Circle)->getMorphClass(),
            'approvable_id' => Circle::factory()->withoutApproval(),
            'user_id' => User::factory(),
            'requested_by' => fn (array $attributes): int => $attributes['user_id'],
            'status' => ApprovalStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => ApprovalStatus::Approved, 'decided_at' => now()]);
    }

    public function rejected(?string $reason = null): static
    {
        return $this->state(fn (): array => ['status' => ApprovalStatus::Rejected, 'decided_at' => now(), 'reason' => $reason]);
    }
}
