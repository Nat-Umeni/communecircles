<?php

namespace App\Enums;

/**
 * Where the thing waiting for approval stands.
 */
enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    /** Taken back while still waiting, e.g. an invite cancelled by whoever runs the thing it was into. */
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for approval',
            self::Approved => 'Approved',
            self::Rejected => 'Not approved',
            self::Cancelled => 'Cancelled',
        };
    }
}
