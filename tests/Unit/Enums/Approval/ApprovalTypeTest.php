<?php

namespace Tests\Unit\Enums\Approval;

use App\Enums\Approval\ApprovalType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApprovalTypeTest extends TestCase
{
    #[DataProvider('labels')]
    public function test_labels_each_type_for_display(ApprovalType $type, string $label): void
    {
        $this->assertSame($label, $type->label());
    }

    #[DataProvider('addsMember')]
    public function test_only_join_requests_and_invites_add_someone_to_the_circle_once_approved(ApprovalType $type, bool $addsMember): void
    {
        $this->assertSame($addsMember, $type->addsMember());
    }

    #[DataProvider('decidedByStaff')]
    public function test_says_whether_staff_decide_it(ApprovalType $type, bool $decidedByStaff): void
    {
        $this->assertSame($decidedByStaff, $type->decidedByStaff());
    }

    public function test_lists_the_kinds_staff_decide_for_their_queue(): void
    {
        $this->assertSame([ApprovalType::NewCircle], ApprovalType::forStaff());
    }

    /**
     * @return array<string, array{ApprovalType, bool}>
     */
    public static function decidedByStaff(): array
    {
        return [
            'a new circle' => [ApprovalType::NewCircle, true],
            'a request to join' => [ApprovalType::JoinRequest, false],
            'an invite' => [ApprovalType::Invite, false],
        ];
    }

    #[DataProvider('messages')]
    public function test_says_what_happened_after_each_decision(ApprovalType $type, string $approved, string $rejected, string $cancelled): void
    {
        $this->assertSame($approved, $type->approvedMessage());
        $this->assertSame($rejected, $type->rejectedMessage());
        $this->assertSame($cancelled, $type->cancelledMessage());
    }

    /**
     * @return array<string, array{ApprovalType, string, string, string}>
     */
    public static function messages(): array
    {
        return [
            'a new circle' => [ApprovalType::NewCircle, 'Circle approved.', 'Circle rejected.', 'Circle withdrawn.'],
            'a request to join' => [ApprovalType::JoinRequest, 'Request accepted.', 'Request declined.', 'Request withdrawn.'],
            'an invite' => [ApprovalType::Invite, "You've joined the circle.", 'Invite declined.', 'Invite cancelled.'],
        ];
    }

    /**
     * @return array<string, array{ApprovalType, string}>
     */
    public static function labels(): array
    {
        return [
            'a new circle' => [ApprovalType::NewCircle, 'New circle'],
            'a request to join' => [ApprovalType::JoinRequest, 'Request to join'],
            'an invite' => [ApprovalType::Invite, 'Invite'],
        ];
    }

    /**
     * @return array<string, array{ApprovalType, bool}>
     */
    public static function addsMember(): array
    {
        return [
            'a new circle' => [ApprovalType::NewCircle, false],
            'a request to join' => [ApprovalType::JoinRequest, true],
            'an invite' => [ApprovalType::Invite, true],
        ];
    }
}
