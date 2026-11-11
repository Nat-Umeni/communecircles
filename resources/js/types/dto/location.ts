import type { Place } from './geonames/place';

/**
 * Where something is, as app/DTO/Location.php's toArray() sends it: its own name for the place, the nearest GeoNames
 * place, or both.
 */
export interface Location {
    name: string | null;
    place: Place | null;
    country_code: string;
    country_name: string;
}
