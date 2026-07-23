<?php

namespace Database\Seeders;

use App\Models\AffiliateClick;
use App\Models\User;
use Illuminate\Database\Seeder;

class EngagementSeeder extends Seeder
{
    /**
     * @var array<int, array{name: string, id: string}>
     */
    private array $services = [
        ['name' => 'Netflix', 'id' => 'netflix'],
        ['name' => 'Amazon Prime Video', 'id' => 'prime'],
        ['name' => 'Disney+', 'id' => 'disney'],
        ['name' => 'Hulu', 'id' => 'hulu'],
        ['name' => 'Apple TV+', 'id' => 'apple'],
        ['name' => 'HBO Max', 'id' => 'hbo'],
    ];

    /**
     * @var array<int, array{id: int, type: string}>
     */
    private array $titles = [
        ['id' => 550, 'type' => 'movie'],
        ['id' => 278, 'type' => 'movie'],
        ['id' => 155, 'type' => 'movie'],
        ['id' => 27205, 'type' => 'movie'],
        ['id' => 496243, 'type' => 'movie'],
        ['id' => 872585, 'type' => 'movie'],
        ['id' => 693134, 'type' => 'movie'],
        ['id' => 1396, 'type' => 'tv'],
        ['id' => 66732, 'type' => 'tv'],
        ['id' => 100088, 'type' => 'tv'],
    ];

    public function run(): void
    {
        $users = User::whereNotNull('email_verified_at')->get();

        foreach ($users as $user) {
            $clickCount = fake()->numberBetween(1, 6);
            for ($i = 0; $i < $clickCount; $i++) {
                $service = fake()->randomElement($this->services);
                $title = fake()->randomElement($this->titles);

                AffiliateClick::create([
                    'user_id' => $user->id,
                    'service_name' => $service['name'],
                    'service_id' => $service['id'],
                    'tmdb_id' => $title['id'],
                    'media_type' => $title['type'],
                    'link' => 'https://click.justwatch.com/a?r=https%3A%2F%2Fwww.'.$service['id'].'.com',
                    'ip_address' => fake()->ipv4(),
                    'created_at' => fake()->dateTimeBetween('-1 month'),
                ]);
            }
        }
    }
}
