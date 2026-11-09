<?php

namespace App\Enums;

/**
 * Where something waiting for approval stands, e.g. a new circle or a request to join one.
 */
enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Waiting for approval',
            self::Approved => 'Approved',
            self::Rejected => 'Not approved',
        };
    }
}
