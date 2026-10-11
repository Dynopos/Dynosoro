<?php

namespace App\Services\Content;

use App\Enums\ArticleStatus;
use App\Exceptions\ArticleRejected;
use App\Models\Article;
use App\Models\Site;
use App\Models\Topic;
use Illuminate\Support\Str;

/**
 * Turns the next planned topic into a ready article (spec §4 steps 1–5):
 * write → code checks → AI check, with at most `max_rewrites` rewrites (rule 4),
 * then one image. Nothing here touches GitHub.
 */
class ArticleProducer
{
    public function __construct(
        private TopicPlanner $planner,
        private ArticleWriter $writer,
        private ArticleValidator $validator,
        private ArticleChecker $checker,
        private ImageMaker $images,
    ) {}

    /** @throws ArticleRejected */
    public function produce(Site $site): Article
    {
        $this->planner->ensureBacklog($site);

        $topic = $site->topics()->where('status', 'planned')->orderBy('position')->orderBy('id')->first()
            ?? throw new ArticleRejected('Tiada topik dalam pelan. Tambah topik atau jana pelan topik.');

        $article = $site->articles()->create([
            'workspace_id' => $site->workspace_id,
            'topic_id' => $topic->id,
            'title' => $topic->title,
            'status' => ArticleStatus::Writing,
            'source' => 'ai',
            'prompt_version' => $this->writer->version(),
        ]);

        return $this->write($site, $topic, $article);
    }

    /** @throws ArticleRejected */
    public function write(Site $site, Topic $topic, Article $article): Article
    {
        $notes = [];
        $maxAttempts = 1 + (int) config('dynoseo.ai.max_rewrites');

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $article->forceFill(['attempts' => $attempt])->save();

            $draft = $this->writer->draft($site, $topic, $article, $notes);
            $notes = $this->validator->issues($site, ['body_html' => $draft['raw_body']] + $draft, $article->id);

            if ($notes === []) {
                $notes = $this->checker->review($site, $article, $draft);
            }

            if ($notes === []) {
                return $this->accept($site, $topic, $article, $draft);
            }
        }

        $article->forceFill([
            'status' => ArticleStatus::Failed,
            'check_notes' => $notes,
            'error' => 'Draf tidak lulus semakan selepas '.$maxAttempts.' cubaan.',
        ])->save();
        $topic->forceFill(['status' => 'skipped'])->save();

        throw new ArticleRejected('Artikel "'.$topic->title.'" tidak lulus semakan selepas '.$maxAttempts.' cubaan: '.implode(' ', $notes), $notes);
    }

    private function accept(Site $site, Topic $topic, Article $article, array $draft): Article
    {
        $article->forceFill([
            'title' => $draft['title'],
            'slug' => $this->uniqueSlug($site, $draft['slug'], $article->id),
            'meta_description' => Str::limit($draft['meta_description'], 160, ''),
            'summary' => $draft['summary'] ?: Str::limit(ArticleValidator::plainText($draft['body_html']), 160),
            'body_html' => $draft['body_html'],
            'faq' => $draft['faq'],
            'cta_bridge' => $draft['cta_bridge'],
            'image_prompt' => $draft['image_prompt'],
            'image_alt' => $draft['image_alt'],
            'word_count' => ArticleValidator::wordCount($draft['body_html']),
            'check_notes' => null,
            'error' => null,
            'status' => ArticleStatus::Ready,
        ])->save();

        $topic->forceFill(['status' => 'used'])->save();

        // Images only after the text passed (CLAUDE.md, kawalan kos). No image is not fatal.
        $article->forceFill(['image_path' => $this->images->make($site, $article)])->save();

        return $article;
    }

    public function uniqueSlug(Site $site, string $slug, ?int $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'artikel';
        $candidate = $base;
        $i = 2;

        while (Article::query()->where('site_id', $site->id)->where('slug', $candidate)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $candidate = $base.'-'.$i++;
        }

        return $candidate;
    }
}
