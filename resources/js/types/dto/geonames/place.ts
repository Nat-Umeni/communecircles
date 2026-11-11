/**
 * A town or village from GeoNames, as app/DTO/GeoNames/Place.php's toArray() sends it.
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
