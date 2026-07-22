<?php

return [
    'providers' => [
        [
            'name' => 'VidCore',
            'driver' => 'embed',
            'movie_url' => env('VIDCORE_MOVIE_URL', 'https://vidcore.org/embed/movie/{id}'),
            'tv_url' => env('VIDCORE_TV_URL', 'https://vidcore.org/embed/tv/{id}/{season}/{episode}'),
        ],
        [
            'name' => 'VidPhantom',
            'driver' => 'embed',
            'movie_url' => env('VIDPHANTOM_MOVIE_URL', 'https://vidphantom.com/movie/{id}'),
            'tv_url' => env('VIDPHANTOM_TV_URL', 'https://vidphantom.com/tv/{id}/{season}/{episode}'),
        ],
        [
            'name' => 'CineSrc',
            'driver' => 'embed',
            'movie_url' => env('CINESRC_MOVIE_URL', 'https://cinesrc.st/embed/movie/{id}'),
            'tv_url' => env('CINESRC_TV_URL', 'https://cinesrc.st/embed/tv/{id}?s={season}&e={episode}'),
        ],
        [
            'name' => 'VidSrc',
            'driver' => 'embed',
            'movie_url' => env('VIDSRC_MOVIE_URL', 'https://vidsrc.mov/embed/movie/{id}'),
            'tv_url' => env('VIDSRC_TV_URL', 'https://vidsrc.mov/embed/tv/{id}/{season}/{episode}'),
        ],
        [
            'name' => 'EzVidAPI',
            'driver' => 'embed',
            'movie_url' => env('EZVIDAPI_MOVIE_URL', 'https://ezvidapi.com/embed/movie/{id}'),
            'tv_url' => env('EZVIDAPI_TV_URL', 'https://ezvidapi.com/embed/tv/{id}/{season}/{episode}'),
        ],
        [
            'name' => 'VidLink',
            'driver' => 'embed',
            'movie_url' => env('VIDLINK_MOVIE_URL', 'https://vidlink.pro/movie/{id}'),
            'tv_url' => env('VIDLINK_TV_URL', 'https://vidlink.pro/tv/{id}/{season}/{episode}'),
        ],
        [
            'name' => 'SuperEmbed',
            'driver' => 'embed',
            'movie_url' => env('SUPEREMBED_MOVIE_URL', 'https://multiembed.mov/?video_id={id}&tmdb=1'),
            'tv_url' => env('SUPEREMBED_TV_URL', 'https://multiembed.mov/?video_id={id}&tmdb=1&s={season}&e={episode}'),
        ],
        [
            'name' => 'Embed API',
            'driver' => 'embed',
            'movie_url' => env('EMBEDAPI_MOVIE_URL', 'https://player.embed-api.stream/?id={id}'),
            'tv_url' => env('EMBEDAPI_TV_URL', 'https://player.embed-api.stream/?id={id}&s={season}&e={episode}'),
        ],
        [
            'name' => 'Trailer',
            'driver' => 'trailer',
        ],
    ],

    'cache_ttl' => 60,
];
