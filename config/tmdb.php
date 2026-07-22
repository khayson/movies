<?php

return [
    'api_key' => env('TMDB_API_KEY'),
    'base_url' => 'https://api.themoviedb.org/3',
    'image_base_url' => 'https://image.tmdb.org/t/p',

    'cache_ttl' => [
        'trending' => 360,
        'popular' => 360,
        'details' => 1440,
        'search' => 60,
        'config' => 10080,
    ],
];
