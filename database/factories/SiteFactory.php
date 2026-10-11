<?php

namespace Database\Factories;

use App\Enums\SiteStatus;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Site> */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $slug = 'gabion-'.Str::lower(Str::random(5));

        return [
            'name' => 'Gabion Pantai Timur',
            'domain' => $slug.'.my',
            'github_repo' => 'Dynopos/'.$slug,
            'branch' => 'main',
            'niche' => 'gabion, kawalan cerun, tembok penahan tanah',
            'audience' => 'pemilik rumah lereng bukit, kontraktor kecil',
            'area' => 'Kelantan',
            'frequency_days' => 2,
            'status' => SiteStatus::Active,
            'next_publish_at' => now()->subMinute(),
            'cta' => [
                'title' => 'Cerun rumah anda mula runtuh?',
                'text' => 'WhatsApp kami untuk lawatan tapak dan cadangan.',
                'button' => 'WhatsApp Kami',
                'whatsapp' => '0123456789',
                'message' => 'Salam, saya baca artikel "{tajuk}" di website anda. Nak tanya...',
                'url' => '/hubungi/',
            ],
            'installed_at' => now(),
        ];
    }
}
