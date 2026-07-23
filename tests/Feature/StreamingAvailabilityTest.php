<?php

use App\Services\StreamingAvailability;

test('getStreamingOptions extracts and sorts options from response data', function () {
    $service = new StreamingAvailability;

    $showData = [
        'streamingOptions' => [
            'us' => [
                [
                    'service' => [
                        'id' => 'netflix',
                        'name' => 'Netflix',
                        'imageSet' => [
                            'lightThemeImage' => 'https://example.com/netflix-light.png',
                            'darkThemeImage' => 'https://example.com/netflix-dark.png',
                        ],
                    ],
                    'type' => 'subscription',
                    'link' => 'https://netflix.com/watch/123',
                    'quality' => 'hd',
                    'price' => null,
                ],
                [
                    'service' => [
                        'id' => 'apple',
                        'name' => 'Apple TV',
                        'imageSet' => [
                            'lightThemeImage' => 'https://example.com/apple-light.png',
                            'darkThemeImage' => 'https://example.com/apple-dark.png',
                        ],
                    ],
                    'type' => 'buy',
                    'link' => 'https://apple.com/buy/123',
                    'quality' => '4k',
                    'price' => ['formatted' => '$14.99'],
                ],
                [
                    'service' => [
                        'id' => 'tubi',
                        'name' => 'Tubi',
                        'imageSet' => [
                            'darkThemeImage' => 'https://example.com/tubi-dark.png',
                        ],
                    ],
                    'type' => 'free',
                    'link' => 'https://tubi.com/watch/123',
                    'quality' => 'sd',
                    'price' => null,
                ],
            ],
        ],
    ];

    $options = $service->getStreamingOptions($showData, 'us');

    expect($options)->toHaveCount(3)
        ->and($options[0]['service'])->toBe('Netflix')
        ->and($options[0]['type'])->toBe('subscription')
        ->and($options[1]['service'])->toBe('Tubi')
        ->and($options[1]['type'])->toBe('free')
        ->and($options[2]['service'])->toBe('Apple TV')
        ->and($options[2]['type'])->toBe('buy')
        ->and($options[2]['price']['formatted'])->toBe('$14.99');
});

test('getStreamingOptions returns empty array for missing country', function () {
    $service = new StreamingAvailability;

    $showData = [
        'streamingOptions' => [
            'us' => [
                [
                    'service' => ['id' => 'netflix', 'name' => 'Netflix', 'imageSet' => []],
                    'type' => 'subscription',
                    'link' => 'https://netflix.com',
                ],
            ],
        ],
    ];

    $options = $service->getStreamingOptions($showData, 'gb');

    expect($options)->toBeEmpty();
});

test('getStreamingOptions deduplicates by service and type', function () {
    $service = new StreamingAvailability;

    $showData = [
        'streamingOptions' => [
            'us' => [
                [
                    'service' => ['id' => 'netflix', 'name' => 'Netflix', 'imageSet' => []],
                    'type' => 'subscription',
                    'link' => 'https://netflix.com/1',
                ],
                [
                    'service' => ['id' => 'netflix', 'name' => 'Netflix', 'imageSet' => []],
                    'type' => 'subscription',
                    'link' => 'https://netflix.com/2',
                ],
            ],
        ],
    ];

    $options = $service->getStreamingOptions($showData, 'us');

    expect($options)->toHaveCount(1);
});

test('leaderboard page loads successfully', function () {
    $response = $this->get('/leaderboard');

    $response->assertStatus(200);
});
