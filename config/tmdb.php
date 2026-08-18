<?php

return [
    'api_key' => env('TMDB_API_KEY'),
    'base_url' => env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
    'base_urls' => array_values(array_unique(array_filter([
        env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
        env('TMDB_FALLBACK_URL', 'https://api.tmdb.org/3'),
    ]))),
    'image_base_url' => env('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p'),

    'cache_ttl' => [
        'trending' => 360,
        'popular' => 360,
        'details' => 1440,
        'search' => 60,
        'config' => 10080,
    ],
];
