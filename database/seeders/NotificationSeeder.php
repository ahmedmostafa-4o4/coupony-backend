<?php

namespace Database\Seeders;

use App\Domain\Notification\Models\Notification;
use App\Domain\User\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('Please run UserSeeder first!');
            return;
        }

        // Create notifications for each user
        foreach ($users->take(10) as $user) {
            // Welcome notification
            Notification::create([
                'user_id' => $user->id,
                'type' => 'welcome',
                'title' => 'Welcome to Coupony!',
                'message' => 'Thank you for joining Coupony. Start exploring amazing deals and discounts.',
                'channel' => 'email',
                'status' => 'sent',
                'sent_at' => now()->subDays(rand(1, 30)),
                'read_at' => fake()->boolean(70) ? now()->subDays(rand(0, 29)) : null,
            ]);

            // Random notifications
            for ($i = 0; $i < rand(2, 5); $i++) {
                Notification::create([
                    'user_id' => $user->id,
                    'type' => fake()->randomElement(['info', 'success', 'promotion', 'reminder']),
                    'title' => fake()->sentence(4),
                    'message' => fake()->paragraph(),
                    'channel' => fake()->randomElement(['email', 'push', 'in_app']),
                    'status' => fake()->randomElement(['sent', 'read', 'pending']),
                    'sent_at' => fake()->optional(0.8)->dateTimeBetween('-30 days', 'now'),
                    'read_at' => fake()->optional(0.5)->dateTimeBetween('-29 days', 'now'),
                ]);
            }
        }

        $this->command->info('Notifications created for users');
    }
}
