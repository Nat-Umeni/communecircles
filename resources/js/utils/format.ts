import type { Place, User } from '@/types';

/**
 * Someone's name as the pages show it, e.g. "Grace Hopper".
 */
export function fullName(user: Pick<User, 'first_name' | 'last_name'>): string {
    return `${user.first_name} ${user.last_name}`;
}

/**
 * A GeoNames town or village with its region and country, e.g. "Totnes, England, United Kingdom", or "Vaduz,
 * Liechtenstein" where GeoNames has no region.
 */
export function placeLabel(place: Pick<Place, 'name' | 'region' | 'country_name'>): string {
    return [place.name, place.region, place.country_name].filter(Boolean).join(', ');
}
