<?php

return [
    // The GeoNames account to use. Free web services have to be enabled on it.
    'username' => env('GEONAMES_USERNAME'),

    // NOTE: api.geonames.org fails over HTTPS, so this is the secure host.
    'base_url' => env('GEONAMES_BASE_URL', 'https://secure.geonames.org'),
];
