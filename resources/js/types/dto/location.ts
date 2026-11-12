import type { Coordinates } from './coordinates';
import type { Place } from './geonames/place';

/**
 * Where something is, as app/DTO/Location.php's toArray() sends it: its own name for the place, the nearest GeoNames
 * place, or both, and its own exact spot for those who may see it.
 */
export interface Location {
    name: string | null;
    place: Place | null;
    country_code: string;
    country_name: string;
    /** Its own exact spot; null without one, or for someone who may not see it. */
    coordinates: Coordinates | null;
    /** Whether there is an exact spot this viewer may not see (only members and staff can). */
    coordinates_hidden: boolean;
}
