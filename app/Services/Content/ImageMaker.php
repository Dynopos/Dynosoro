<?php

namespace App\Services\Content;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Site;
use App\Services\Ai\AiGateway;
use App\Services\Ai\PromptRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * One Imagen picture per article (spec §4.5), converted to a 1200×675 WebP and kept on the
 * app's local disk (so the website can be rebuilt) at sites/{site}/blog/img/{slug}.webp.
 */
class ImageMaker
{
    public function __construct(private AiGateway $ai, private PromptRepository $prompts) {}

    /** Returns the repo path of the image, or null when no image could be made. */
    public function make(Site $site, Article $article): ?string
    {
        $avoid = Article::query()->where('site_id', $site->id)
            ->where('status', ArticleStatus::Published)
            ->whereKeyNot($article->id)
            ->whereNotNull('image_prompt')
            ->latest('published_at')->take(5)->pluck('image_prompt')
            ->map(fn ($p) => '- '.$p)->implode("\n");

        $prompt = $this->prompts->render('image', ['subject' => rtrim((string) $article->image_prompt, '. ')], ['avoid' => $avoid ?: '-']);
        $text = $prompt->system.($avoid !== '' ? "\n\n".$prompt->user : '');

        for ($attempt = 1; $attempt <= (int) config('dynoseo.ai.max_image_attempts'); $attempt++) {
            $response = $this->ai->image((string) config('dynoseo.ai.model_image'), $text, $site->id, $article->id);

            if ($response->bytes === null) {
                Log::info('Imagen returned no image', ['article' => $article->id, 'reason' => $response->filteredReason]);

                continue;
            }

            $webp = $this->toWebp($response->bytes);

            if ($webp === null) {
                continue;
            }

            $path = 'blog/img/'.$article->slug.'.webp';
            Storage::disk('local')->put(self::storagePath($site, $path), $webp);

            return $path;
        }

        return null;
    }

    public static function storagePath(Site $site, string $repoPath): string
    {
        return 'sites/'.$site->id.'/'.$repoPath;
    }

    /** Cover-crop to the configured size and encode as WebP. */
    public function toWebp(string $bytes): ?string
    {
        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            return null;
        }

        $width = (int) config('dynoseo.articles.image_width');
        $height = (int) config('dynoseo.articles.image_height');
        [$sw, $sh] = [imagesx($source), imagesy($source)];
        $scale = max($width / $sw, $height / $sh);
        [$cw, $ch] = [(int) round($width / $scale), (int) round($height / $scale)];

        $target = imagecreatetruecolor($width, $height);
        imagecopyresampled($target, $source, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $width, $height, $cw, $ch);

        ob_start();
        imagewebp($target, null, (int) config('dynoseo.articles.image_quality'));
        $webp = (string) ob_get_clean();

        imagedestroy($source);
        imagedestroy($target);

        return $webp !== '' ? $webp : null;
    }
}
