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

    /** A website whose business analysis has not been done yet. */
    public function unanalysed(): static
    {
        return $this->state(['analysis' => null, 'analysis_confirmed_at' => null]);
    }

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
            'analysis' => [
                'field' => 'kawalan cerun dan tembok penahan tanah',
                'summary' => 'Pasang gabion dan tembok penahan untuk cerun rumah dan tapak.',
                'services' => ['tembok gabion', 'kawalan cerun'],
                'customers' => ['pemilik rumah lereng bukit'],
                'searches' => ['cara elak tanah runtuh'],
                'pillars' => [['name' => 'cerun', 'about' => 'Kestabilan cerun.'], ['name' => 'tembok', 'about' => 'Jenis tembok penahan.']],
                'avoid' => ['oksigen', 'sukan lasak'],
                'confidence' => 'tinggi',
                'question' => '',
                'version' => 'analyse-v1',
            ],
            'analysis_confirmed_at' => now(),
        ];
    }
}
