<?php

namespace Database\Factories;

use App\Models\QuizScore;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizScore>
 */
class QuizScoreFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'quiz_type' => 'guess_title',
            'score' => fake()->numberBetween(0, 10),
            'total' => 10,
            'time_seconds' => fake()->numberBetween(30, 300),
        ];
    }
}
