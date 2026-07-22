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
            'name' => 'AutoEmbed',
            'driver' => 'embed',
            'movie_url' => env('AUTOEMBED_MOVIE_URL', 'https://autoembed.co/movie/tmdb/{id}'),
            'tv_url' => env('AUTOEMBED_TV_URL', 'https://autoembed.co/tv/tmdb/{id}-{season}-{episode}'),
        ],
        [
            'name' => 'MoviesAPI',
            'driver' => 'embed',
            'movie_url' => env('MOVIESAPI_MOVIE_URL', 'https://moviesapi.to/movie/{id}'),
            'tv_url' => env('MOVIESAPI_TV_URL', 'https://moviesapi.to/tv/{id}-{season}-{episode}'),
        ],
        [
            'name' => 'VidBinge',
            'driver' => 'embed',
            'movie_url' => env('VIDBINGE_MOVIE_URL', 'https://vidbinge.to/movie/{id}'),
            'tv_url' => env('VIDBINGE_TV_URL', 'https://vidbinge.to/tv/{id}-{season}-{episode}'),
        ],
        [
            'name' => 'VikingEmbed',
            'driver' => 'embed',
            'movie_url' => env('VIKINGEMBED_MOVIE_URL', 'https://vembed.click/embed/movie/{id}'),
            'tv_url' => env('VIKINGEMBED_TV_URL', 'https://vembed.click/embed/tv/{id}/{season}/{episode}'),
        ],
        [
            'name' => 'Trailer',
            'driver' => 'trailer',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Adult Content Providers (18+ only)
    |--------------------------------------------------------------------------
    |
    | These providers are only used when viewing adult content.
    | 'embed' providers use TMDB IDs — same as regular providers.
    | 'external' providers are linked as external tube sites.
    |
    */
    'adult_providers' => [
        // Embed providers that stream adult movies via TMDB ID
        [
            'name' => 'VidSrc Adult',
            'driver' => 'embed',
            'movie_url' => 'https://vidsrc.mov/embed/movie/{id}',
        ],
        [
            'name' => 'AutoEmbed Adult',
            'driver' => 'embed',
            'movie_url' => 'https://autoembed.co/movie/tmdb/{id}',
        ],
        [
            'name' => 'SuperEmbed Adult',
            'driver' => 'embed',
            'movie_url' => 'https://multiembed.mov/?video_id={id}&tmdb=1',
        ],
        [
            'name' => 'Embed API Adult',
            'driver' => 'embed',
            'movie_url' => 'https://player.embed-api.stream/?id={id}',
        ],
        [
            'name' => 'VidBinge Adult',
            'driver' => 'embed',
            'movie_url' => 'https://vidbinge.to/movie/{id}',
        ],
        [
            'name' => 'MoviesAPI Adult',
            'driver' => 'embed',
            'movie_url' => 'https://moviesapi.to/movie/{id}',
        ],

        // External tube sites — linked out for browsing
        [
            'name' => 'PornHub',
            'driver' => 'external',
            'url' => 'https://www.pornhub.com',
        ],
        [
            'name' => 'XVideos',
            'driver' => 'external',
            'url' => 'https://www.xvideos.com',
        ],
        [
            'name' => 'xHamster',
            'driver' => 'external',
            'url' => 'https://xhamster.com',
        ],
        [
            'name' => 'Eporner',
            'driver' => 'external',
            'url' => 'https://www.eporner.com',
        ],
        [
            'name' => 'RedTube',
            'driver' => 'external',
            'url' => 'https://www.redtube.com',
        ],
    ],

    'rapidapi_key' => env('RAPIDAPI_KEY'),

    'rapidapi_hosts' => [
        'xnxx' => 'porn-xnxx-api.p.rapidapi.com',
        'pornhub' => 'pornhub-api-xnxx.p.rapidapi.com',
        'xvideos' => 'xvideos-com-api.p.rapidapi.com',
        'eporner' => 'eporner-com-api-v2-xnxx.p.rapidapi.com',
    ],

    'cache_ttl' => 60,
];
