<?php

namespace Tests\Feature\Http\Requests\Approval;

use App\Enums\ApprovalStatus;
use App\Enums\Role;
use App\Models\Circle;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RejectApprovalRequestTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $details
     */
    #[DataProvider('acceptedReasons')]
    public function test_a_reason_is_optional_and_can_be_up_to_500_characters(array $details): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->actingAs($this->staff())->post(route('approvals.reject', $circle->approval), $details);

        $response->assertValid();
        $this->assertSame(ApprovalStatus::Rejected, $circle->approval->fresh()->status);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    #[DataProvider('invalidReasons')]
    public function test_rejects_a_reason_that_is_not_text_or_over_500_characters(array $details): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->actingAs($this->staff())->post(route('approvals.reject', $circle->approval), $details);

        $response->assertInvalid(['reason']);
        $this->assertSame(ApprovalStatus::Pending, $circle->approval->fresh()->status);
    }

    public function test_refuses_someone_who_cannot_decide(): void
    {
        $circle = Circle::factory()->pending()->create();

        $response = $this->actingAs(User::factory()->create())->post(route('approvals.reject', $circle->approval), ['reason' => 'No.']);

        $response->assertForbidden();
        $this->assertSame(ApprovalStatus::Pending, $circle->approval->fresh()->status);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function acceptedReasons(): array
    {
        return [
            'no reason' => [[]],
            'an empty reason' => [['reason' => '']],
            'a reason of 500 characters' => [['reason' => str_repeat('a', 500)]],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidReasons(): array
    {
        return [
            'a reason over 500 characters' => [['reason' => str_repeat('a', 501)]],
            'a reason that is not text' => [['reason' => 12345]],
        ];
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }
}
