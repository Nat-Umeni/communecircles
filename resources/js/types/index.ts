import type { Component } from 'vue';

/**
 * A member's site-wide role: the value of App\Enums\Role. Circle roles like chief belong to circle memberships, not here.
 */
export type Role = 'user' | 'admin' | 'super_admin';

/**
 * The signed-in member, shared with every page by app/Http/Middleware/HandleInertiaRequests.php.
 */
export interface AuthUser {
    first_name: string;
    last_name: string;
    email: string;
    /** Whether they've clicked the link in their confirmation email. */
    email_verified: boolean;
    /** The role's value and its display label, from App\Enums\Role::label(); internal is true for staff. */
    role: {
        value: Role;
        label: string;
        internal: boolean;
    };
}

/**
 * A town or village from GeoNames, as App\DTO\GeoNames\Place::toArray() sends it.
 */
export interface Place {
    geonames_id: number;
    name: string;
    region: string | null;
    country_code: string;
    country_name: string;
    latitude: number;
    longitude: number;
}

/**
 * Where a circle is, as App\DTO\Location::toArray() sends it: its own name for the place, the nearest GeoNames place,
 * or both.
 */
export interface Location {
    name: string | null;
    place: Place | null;
    country_code: string;
    country_name: string;
}

/**
 * A circle's own details, as CircleController::details() sends them.
 */
export interface CircleDetails {
    public_id: string;
    name: string;
    description: string;
    location: Location | null;
}

/**
 * A circle in a list, as CircleController sends it.
 */
export interface CircleSummary extends CircleDetails {
    members_count: number;
}

/**
 * Where an approval stands: the value of App\Enums\ApprovalStatus, with its label.
 */
export interface ApprovalStatus {
    value: 'pending' | 'approved' | 'rejected';
    label: string;
}

/**
 * One of the member's own circles (your_circles), approved or not.
 */
export interface CircleOfMine extends CircleSummary {
    status: ApprovalStatus;
}

/**
 * What the viewer may do with a circle, from CirclePolicy (CircleController::show).
 */
export interface CirclePermissions {
    view_channels: boolean;
    request_to_join: boolean;
    invite: boolean;
    update: boolean;
    delete: boolean;
}

/**
 * Where a circle's own approval stands, sent while it isn't approved; can_decide is for staff.
 */
export interface CircleApproval {
    id: number;
    status: ApprovalStatus['value'];
    label: string;
    reason: string | null;
    can_decide: boolean;
}

/**
 * The viewer's own request to join a circle.
 */
export interface JoinRequest {
    status: ApprovalStatus['value'];
    label: string;
    reason: string | null;
}

/**
 * An invite to a circle waiting for the viewer's answer.
 */
export interface Invite {
    id: number;
    invited_by: string | null;
}

/**
 * Someone's request to join, waiting for the circle's owners and admins.
 */
export interface WaitingJoinRequest {
    id: number;
    name: string;
    requested_at: string;
}

/**
 * Someone invited to a circle, who hasn't answered yet.
 */
export interface WaitingInvite {
    id: number;
    name: string;
    invited_at: string;
}

/**
 * A new circle in the staff approvals queue (ApprovalController::index). requester is null if their account is gone.
 */
export interface NewCircleApproval {
    id: number;
    requested_at: string;
    circle: Pick<CircleDetails, 'public_id' | 'name' | 'description'>;
    requester: { first_name: string; last_name: string } | null;
}

/**
 * A page of a Laravel paginator, as Inertia receives it.
 */
export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

/**
 * One choice in a SegmentedControl. Options with an icon show only the icon, with the label for screen readers.
 */
export interface Option {
    value: string;
    label: string;
    icon?: Component;
}
