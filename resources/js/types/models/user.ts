import type { CircleRole, Role } from '../enums';
import type { EnumOption, Timestamp } from '../utilities';

/**
 * A member, as app/Http/Resources/UserResource.php sends them. Everyone gets their name; only the member themselves
 * gets their email, whether it's confirmed and their role; and coming from a circle's members they carry their
 * membership.
 */
export interface User {
    first_name: string;
    last_name: string;
    /** Only to the member themselves. */
    email?: string;
    /** Only to the member themselves: whether they've clicked the link in their confirmation email. */
    email_verified?: boolean;
    /** Only to the member themselves; internal is true for staff. */
    role?: EnumOption<Role> & { internal: boolean };
    /** When they come from a circle's members. */
    membership?: Membership;
}

/**
 * Someone's membership of a circle (their circle_user row), from UserResource's membership.
 */
export interface Membership {
    /** The membership's id, not the member's. */
    id: number;
    role: EnumOption<CircleRole>;
    joined_at: Timestamp;
}
