import type { Component } from 'vue';

/**
 * The signed-in member, shared with every page by app/Http/Middleware/HandleInertiaRequests.php.
 */
export interface AuthUser {
    first_name: string;
    last_name: string;
    email: string;
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
