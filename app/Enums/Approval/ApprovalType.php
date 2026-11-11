<?php

namespace App\Enums\Approval;

/**
 * What an approval is for. Who decides each one is in ApprovalPolicy.
 */
enum ApprovalType: string
{
    /** A member created a circle; staff approve it before it's listed. */
    case NewCircle = 'new_circle';

    /** Someone asked to join something, e.g. a circle; its owners and admins decide. */
    case JoinRequest = 'join_request';

    /** An owner or admin of something, e.g. a circle, invited someone in; that person accepts or declines. */
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
     * What to tell whoever just approved it.
     */
    public function approvedMessage(): string
    {
        return match ($this) {
            self::NewCircle => 'Circle approved.',
            self::JoinRequest => 'Request accepted.',
            self::Invite => "You've joined the circle.",
        };
    }

    /**
     * What to tell whoever just rejected it.
     */
    public function rejectedMessage(): string
    {
        return match ($this) {
            self::NewCircle => 'Circle rejected.',
            self::JoinRequest => 'Request declined.',
            self::Invite => 'Invite declined.',
        };
    }

    /**
     * What to tell whoever just cancelled it while it was still waiting.
     */
    public function cancelledMessage(): string
    {
        return match ($this) {
            self::NewCircle => 'Circle withdrawn.',
            self::JoinRequest => 'Request withdrawn.',
            self::Invite => 'Invite cancelled.',
        };
    }

    /**
     * Whether staff decide it (anything new waiting to be let in), so it waits in their queue. The others are decided
     * by whoever runs the thing, or by the person it's about.
     */
    public function decidedByStaff(): bool
    {
        return match ($this) {
            self::NewCircle => true,
            self::JoinRequest, self::Invite => false,
        };
    }

    /**
     * The kinds staff decide, for their approvals queue.
     *
     * @return list<self>
     */
    public static function forStaff(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->decidedByStaff()));
    }

    /**
     * Whether approving it lets the person it's about into the thing waiting for approval, e.g. as a member.
     */
    public function addsMember(): bool
    {
        return $this !== self::NewCircle;
    }
}
