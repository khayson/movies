<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    /**
     * @var array<int, array{id: int, title: string, type: string}>
     */
    private static array $titles = [
        ['id' => 550, 'title' => 'Fight Club', 'type' => 'movie'],
        ['id' => 680, 'title' => 'Pulp Fiction', 'type' => 'movie'],
        ['id' => 278, 'title' => 'The Shawshank Redemption', 'type' => 'movie'],
        ['id' => 155, 'title' => 'The Dark Knight', 'type' => 'movie'],
        ['id' => 27205, 'title' => 'Inception', 'type' => 'movie'],
        ['id' => 496243, 'title' => 'Parasite', 'type' => 'movie'],
        ['id' => 872585, 'title' => 'Oppenheimer', 'type' => 'movie'],
        ['id' => 1396, 'title' => 'Breaking Bad', 'type' => 'tv'],
        ['id' => 66732, 'title' => 'Stranger Things', 'type' => 'tv'],
        ['id' => 100088, 'title' => 'The Last of Us', 'type' => 'tv'],
    ];

    /**
     * @var array<int, array{title: string, body: string}>
     */
    private static array $reviews = [
        ['title' => 'A masterpiece of modern cinema', 'body' => 'Every frame is meticulously crafted. The performances are raw and authentic, and the storytelling never lets up. This is the kind of film that stays with you for days after watching.'],
        ['title' => 'Good but overhyped', 'body' => 'Solid film with great production values, but after hearing everyone rave about it, I expected something more groundbreaking. The pacing drags in the second act.'],
        ['title' => 'Changed my perspective on the genre', 'body' => 'I normally avoid this type of movie, but a friend insisted I watch it. It completely subverts expectations and delivers something genuinely original.'],
        ['title' => 'Visually stunning, emotionally flat', 'body' => 'The cinematography and effects are jaw-dropping. Every shot looks like a painting. But the emotional core feels hollow — I never connected with any of the characters.'],
        ['title' => 'Absolutely gripping from start to finish', 'body' => 'I couldn\'t look away. The tension is relentless, the performances are powerhouse-level, and the plot twists actually make sense within the story.'],
        ['title' => 'My new all-time favorite', 'body' => 'I\'ve watched this three times now and notice something new each time. The layered storytelling, the subtle foreshadowing, the brilliant soundtrack — everything works in harmony.'],
        ['title' => 'Decent popcorn entertainment', 'body' => 'Not every movie needs to be a deep philosophical experience. This one knows exactly what it is — fun, flashy, and entertaining. Perfect for a Friday night.'],
        ['title' => 'Perfect date night movie', 'body' => 'Watched this with my partner and we both loved it. It has the right balance of action, humor, and heart. Not too heavy, not too shallow.'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $media = fake()->randomElement(self::$titles);
        $review = fake()->randomElement(self::$reviews);

        return [
            'user_id' => User::factory(),
            'tmdb_id' => $media['id'],
            'media_type' => $media['type'],
            'title' => $review['title'],
            'rating' => fake()->numberBetween(4, 10),
            'body' => $review['body'],
            'contains_spoilers' => fake()->boolean(15),
            'helpful_count' => fake()->numberBetween(0, 35),
        ];
    }

    public function spoiler(): static
    {
        return $this->state(['contains_spoilers' => true]);
    }
}
