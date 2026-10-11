<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = 'Gabion wall vs concrete wall '.Str::lower(Str::random(4));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'meta_description' => 'Bandingkan gabion wall dan concrete wall untuk cerun rumah: kos, ketahanan dan saliran.',
            'summary' => 'Perbandingan ringkas gabion wall dan concrete wall untuk cerun.',
            'body_html' => '<h2>Apa itu gabion</h2><p>Gabion ialah bakul dawai berisi batu.</p><h2>Concrete wall</h2><p>Tembok konkrit kukuh.</p>',
            'faq' => [['q' => 'Gabion tahan lama?', 'a' => 'Ya, jika dawai bersalut.']],
            'image_path' => null,
            'image_alt' => null,
            'word_count' => 900,
            'status' => ArticleStatus::Published,
            'source' => 'ai',
            'published_at' => now()->subDays(2),
        ];
    }
}
