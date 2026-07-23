<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotification>
 */
class UserNotificationFactory extends Factory
{
    /**
     * @var array<int, array{type: string, title: string, message: string, tmdb_id: int|null, media_type: string|null, poster: string|null}>
     */
    private static array $notifications = [
        ['type' => 'follow', 'title' => 'New follower', 'message' => 'Someone started following you!', 'tmdb_id' => null, 'media_type' => null, 'poster' => null],
        ['type' => 'new_release', 'title' => 'Dune: Part Two is now streaming', 'message' => 'Available on multiple platforms', 'tmdb_id' => 693134, 'media_type' => 'movie', 'poster' => '/1pdfLvkbY9ohJlCjQH2CZjjYVvJ.jpg'],
        ['type' => 'activity', 'title' => 'New review on Parasite', 'message' => 'Someone you follow wrote a review', 'tmdb_id' => 496243, 'media_type' => 'movie', 'poster' => '/7IiTTgloJzvGI1TAYymCfbfl3vT.jpg'],
        ['type' => 'new_release', 'title' => 'The Last of Us Season 2 is coming', 'message' => 'New season announced for a show on your watchlist', 'tmdb_id' => 100088, 'media_type' => 'tv', 'poster' => '/uKvVjHNqB5VmOrdxqAt2F7J78ED.jpg'],
        ['type' => 'watch_party', 'title' => 'Watch party invitation', 'message' => 'You\'ve been invited to a watch party', 'tmdb_id' => 1396, 'media_type' => 'tv', 'poster' => '/ggFHVNu6YYI5L9pCfOacjizRGt.jpg'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $notification = fake()->randomElement(self::$notifications);

        return [
            'user_id' => User::factory(),
            'type' => $notification['type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'tmdb_id' => $notification['tmdb_id'],
            'media_type' => $notification['media_type'],
            'poster_path' => $notification['poster'],
            'link' => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (): array => ['read_at' => now()]);
    }
}
