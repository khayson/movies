<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Nana Otoo',
            'email' => 'nanaotoo77@gmail.com',
            'preferences' => ['streaming_country' => 'us', 'email_notifications' => true, 'show_adult_content' => false],
            'date_of_birth' => '1998-03-15',
            'is_premium' => true,
            'premium_until' => now()->addYear(),
        ]);

        User::factory()->create([
            'name' => 'Sarah Mitchell',
            'email' => 'sarah.mitchell@gmail.com',
            'preferences' => ['streaming_country' => 'us', 'email_notifications' => true],
            'date_of_birth' => '1995-06-22',
            'is_premium' => true,
            'premium_until' => now()->addMonths(6),
        ]);

        User::factory()->create([
            'name' => 'James Chen',
            'email' => 'jchen.movies@gmail.com',
            'preferences' => ['streaming_country' => 'us', 'email_notifications' => false],
            'date_of_birth' => '1990-11-08',
        ]);

        User::factory()->create([
            'name' => 'Maria Santos',
            'email' => 'maria.santos@outlook.com',
            'preferences' => ['streaming_country' => 'gb', 'email_notifications' => true],
            'date_of_birth' => '2001-01-30',
        ]);

        User::factory()->create([
            'name' => 'Alex Thompson',
            'email' => 'alexthompson@hotmail.com',
            'preferences' => ['streaming_country' => 'ca', 'email_notifications' => true],
            'date_of_birth' => '1988-09-12',
            'is_premium' => true,
            'premium_until' => now()->addMonths(3),
        ]);

        User::factory()->create([
            'name' => 'Priya Sharma',
            'email' => 'priya.sharma@yahoo.com',
            'preferences' => ['streaming_country' => 'in', 'email_notifications' => true],
            'date_of_birth' => '1997-04-18',
        ]);

        User::factory()->create([
            'name' => 'Daniel Okafor',
            'email' => 'dan.okafor@gmail.com',
            'preferences' => ['streaming_country' => 'gb', 'email_notifications' => false],
            'date_of_birth' => '1993-12-05',
        ]);

        User::factory()->create([
            'name' => 'Emma Larsson',
            'email' => 'emma.larsson@proton.me',
            'preferences' => ['streaming_country' => 'se', 'email_notifications' => true],
            'date_of_birth' => '2000-07-14',
        ]);

        User::factory()->create([
            'name' => 'Ryan Kim',
            'email' => 'ryankim92@gmail.com',
            'preferences' => ['streaming_country' => 'kr', 'email_notifications' => true],
            'date_of_birth' => '1992-02-28',
            'is_premium' => true,
            'premium_until' => now()->subWeek(),
        ]);

        User::factory()->create([
            'name' => 'Olivia Rossi',
            'email' => 'olivia.rossi@gmail.com',
            'preferences' => ['streaming_country' => 'it', 'email_notifications' => false],
            'date_of_birth' => '1999-08-23',
        ]);

        User::factory()->create([
            'name' => 'Tyler Brooks',
            'email' => 'tylerb@gmail.com',
            'preferences' => ['streaming_country' => 'us', 'email_notifications' => true],
            'date_of_birth' => '2003-05-10',
            'created_at' => now()->subDays(2),
        ]);

        User::factory()->unverified()->create([
            'name' => 'Chris Wagner',
            'email' => 'chris.w.test@gmail.com',
        ]);
    }
}
