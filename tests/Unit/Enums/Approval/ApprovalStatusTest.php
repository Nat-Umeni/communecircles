<?php

namespace Tests\Unit\Enums\Approval;

use App\Enums\Approval\ApprovalStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApprovalStatusTest extends TestCase
{
    #[DataProvider('labels')]
    public function test_labels_each_status_for_display(ApprovalStatus $status, string $label): void
    {
        $this->assertSame($label, $status->label());
    }

    /**
     * @return array<string, array{ApprovalStatus, string}>
     */
    public static function labels(): array
    {
        return [
            'pending' => [ApprovalStatus::Pending, 'Waiting for approval'],
            'approved' => [ApprovalStatus::Approved, 'Approved'],
            'rejected' => [ApprovalStatus::Rejected, 'Not approved'],
            'cancelled' => [ApprovalStatus::Cancelled, 'Cancelled'],
        ];
    }
}
