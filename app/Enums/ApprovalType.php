<?php

namespace App\Enums;

/**
 * What an approval is for. Who decides each one is in ApprovalPolicy.
 */
enum ApprovalType: string
{
    /** A member created a circle; staff approve it before it's listed. */
    case NewCircle = 'new_circle';

    /** Someone asked to join a circle; its owners and admins decide. */
    case JoinRequest = 'join_request';

    /** A circle's owner or admin invited someone; that person accepts or declines. */
    case Invite = 'invite';

    public function label(): string
    {
        return match ($this) {
            self::NewCircle => 'New circle',
            self::JoinRequest => 'Request to join',
            self::Invite => 'Invite',
        };
    }

    /**
     * Whether approving it adds the person it's about to the circle.
     */
    public function addsMember(): bool
    {
        return $this !== self::NewCircle;
    }
}
