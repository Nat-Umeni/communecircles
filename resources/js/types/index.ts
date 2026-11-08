import type { Component } from 'vue';

/**
 * A member's site-wide role (App\Enums\Role). Circle roles like chief belong to circle memberships, not here.
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
    role: Role;
}

/**
 * A circle in the home feed, as sent by app/Http/Controllers/HomeController.php.
 */
export interface Circle {
    name: string;
    description: string;
    channels: string[];
}

/**
 * One choice in a SegmentedControl. Options with an icon show only the icon, with the label for screen readers.
 */
export interface Option {
    value: string;
    label: string;
    icon?: Component;
}
