import type { Component } from 'vue';

/**
 * One choice in a Select or SegmentedControl, e.g. a country from App\Services\Countries::options(). Options with an
 * icon show only the icon in a SegmentedControl, with the label for screen readers.
 */
export interface Option {
    value: string;
    label: string;
    icon?: Component;
}
