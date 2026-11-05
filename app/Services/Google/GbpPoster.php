<?php

namespace App\Services\Google;

use App\Enums\ArticleStatus;
use App\Exceptions\GoogleBusinessException;
use App\Models\Article;
use App\Models\GoogleConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Str;

/**
 * One Google Business Profile post per published article: the summary, the article image, and
 * a "Learn more" button to the article. Text comes from the article itself (no AI call, no
 * cost). A failed post never touches the article or its website; the error is shown on the
 * Artikel screen.
 */
class GbpPoster
{
    public function __construct(private GoogleBusinessClient $google) {}

    /** Whether this article should get a post: published, website linked to a profile, not posted yet. */
    public function due(Article $article): bool
    {
        return $this->google->configured()
            && $article->status === ArticleStatus::Published
            && $article->gbp_post === null
            && filled($article->site?->gbp_location);
    }

    /** Post it. Returns true when Google accepted the post. */
    public function post(Article $article): bool
    {
        if (! $this->due($article)) {
            return false;
        }

        $connection = GoogleConnection::query()->where('workspace_id', $article->workspace_id)->first();

        if ($connection === null) {
            $article->forceFill(['gbp_error' => 'Google belum disambung. Tekan Sambung Google di skrin Website.'])->save();

            return false;
        }

        $payload = $this->payload($article);

        try {
            $name = $this->create($connection, $article, $payload);
        } catch (GoogleBusinessException $e) {
            $article->forceFill(['gbp_error' => mb_substr($e->getMessage(), 0, 500)])->save();

            return false;
        } catch (ConnectionException) {
            $article->forceFill(['gbp_error' => 'Tidak dapat hubungi Google. Tekan Post ke Google untuk cuba lagi.'])->save();

            return false;
        }

        $article->forceFill(['gbp_post' => $name, 'gbp_posted_at' => now(), 'gbp_error' => null])->save();

        return true;
    }

    /** The local post body (Business Profile API v4). */
    public function payload(Article $article): array
    {
        $post = [
            'languageCode' => 'ms',
            'topicType' => 'STANDARD',
            'summary' => $this->summary($article),
            'callToAction' => ['actionType' => 'LEARN_MORE', 'url' => $article->url()],
        ];

        if ($article->image_path) {
            $post['media'] = [['mediaFormat' => 'PHOTO', 'sourceUrl' => $article->site->baseUrl().'/'.$article->image_path]];
        }

        return $post;
    }

    /**
     * Title, then the article's own summary. Google rejects posts with phone numbers in the
     * text, and the link already sits on the button, so both are taken out.
     */
    public function summary(Article $article): string
    {
        $text = trim((string) $article->title)."\n\n".trim((string) ($article->summary ?: $article->meta_description))
            ."\n\nBaca artikel penuh di website kami.";

        $text = preg_replace('#https?://\S+#u', '', $text);
        $text = preg_replace('/(?:\+?6?0)[\d\s-]{7,13}\d/u', '', (string) $text);
        $text = preg_replace('/[ \t]{2,}/u', ' ', (string) $text);

        return Str::limit(trim((string) $text), (int) config('dynoseo.gbp.max_chars', 1500) - 3);
    }

    /** With the image first; if Google cannot use the image (not deployed yet, too small), without it. */
    private function create(GoogleConnection $connection, Article $article, array $payload): string
    {
        try {
            return $this->google->createPost($connection, (string) $article->site->gbp_location, $payload);
        } catch (GoogleBusinessException $e) {
            if (! isset($payload['media']) || $e->status !== 400) {
                throw $e;
            }

            unset($payload['media']);

            return $this->google->createPost($connection, (string) $article->site->gbp_location, $payload);
        }
    }
}
