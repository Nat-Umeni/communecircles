/**
 * A point on a map, by latitude and longitude. A GeoNames Place is one, so it can be passed as it is.
 */
export interface MapPoint {
    latitude: number;
    longitude: number;
}

/**
 * A point to mark on a map, with what it is: shown on hover and read out, e.g. "Fernhill, near Totnes, England".
 */
export interface MapMarker extends MapPoint {
    label: string;
}

/**
 * One set of map images and the credit their provider asks for.
 */
export interface MapTileLayer {
    /** e.g. "https://tile.openstreetmap.org/{z}/{x}/{y}.png"; {r} becomes "@2x" on a high-density screen. */
    url: string;
    /** HTML, e.g. a link to the provider's copyright page. */
    attribution: string;
    max_zoom: number;
}

/**
 * The map images for a light page and a dark one, as config/map.php has them; sent by the pages with a map, e.g.
 * CircleController::show's map_tiles.
 */
export interface MapTiles {
    light: MapTileLayer;
    dark: MapTileLayer;
}
