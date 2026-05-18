<?php

namespace Database\Seeders;

use App\Domain\Store\Models\Social;
use Illuminate\Database\Seeder;

class SocialSeeder extends Seeder
{
    public function run(): void
    {
        $socials = [
            ['name' => 'Facebook', 'icon' => config('app.url').'/storage/socials/icons/facebook.png'],
            ['name' => 'Instagram', 'icon' => config('app.url').'/storage/socials/icons/instagram.png'],
            ['name' => 'X', 'icon' => config('app.url').'/storage/socials/icons/x.png'],
            ['name' => 'TikTok', 'icon' => config('app.url').'/storage/socials/icons/tiktok.png'],
            ['name' => 'YouTube', 'icon' => config('app.url').'/storage/socials/icons/youtube.png'],
            ['name' => 'Snapchat', 'icon' => config('app.url').'/storage/socials/icons/snapchat.png'],
            ['name' => 'LinkedIn', 'icon' => config('app.url').'/storage/socials/icons/linkedin.png'],
            ['name' => 'Telegram', 'icon' => config('app.url').'/storage/socials/icons/telegram.png'],
            ['name' => 'WhatsApp', 'icon' => config('app.url').'/storage/socials/icons/whatsapp.png'],
            ['name' => 'Website', 'icon' => config('app.url').'/storage/socials/icons/web.png'],
        ];

        foreach ($socials as $social) {
            Social::updateOrCreate(
                ['name' => $social['name']],
                ['icon' => $social['icon']]
            );
        }

        $this->command->info(count($socials).' socials seeded');
    }
}
