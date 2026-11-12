<?php

return [
    // The map images every map draws (the circle page's, the explore page's), for a light page and a dark one, and the
    // credit each provider asks for. {r} in a URL becomes "@2x" on a high-density screen.
    'tiles' => [
        // NOTE: OpenStreetMap's own tiles suit a small site; its usage policy rules out heavy use, so production can
        // point these at a tile provider instead.
        'light' => [
            'url' => env('MAP_TILES_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
            'attribution' => env('MAP_TILES_ATTRIBUTION', '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
            'max_zoom' => (int) env('MAP_TILES_MAX_ZOOM', 19),
        ],

        // NOTE: Stadia Maps serves localhost without a key; the live site's domain has to be added to a Stadia Maps
        // account first (or an api_key added to the URL).
        'dark' => [
            'url' => env('MAP_DARK_TILES_URL', 'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png'),
            'attribution' => env('MAP_DARK_TILES_ATTRIBUTION', '&copy; <a href="https://www.stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://openmaptiles.org/" target="_blank">OpenMapTiles</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'),
            'max_zoom' => (int) env('MAP_DARK_TILES_MAX_ZOOM', 20),
        ],
    ],
];
