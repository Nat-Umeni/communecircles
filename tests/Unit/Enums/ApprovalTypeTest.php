<?php

namespace Tests\Unit\Enums;

use App\Enums\ApprovalType;
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
