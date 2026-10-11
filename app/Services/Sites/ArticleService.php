<?php

namespace App\Services\Sites;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Services\Content\ArticleValidator;
use App\Services\Content\HtmlSanitizer;
use App\Services\Content\ImageMaker;
use App\Services\Publishing\SitePublisher;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Admin edits to articles (spec §3.3). Edits are re-published to the website at once. */
class ArticleService
{
    public function __construct(
        private SitePublisher $publisher,
        private HtmlSanitizer $sanitizer,
        private ImageMaker $images,
    ) {}

    public function update(Article $article, array $data): Article
    {
        $body = $this->sanitizer->clean((string) $data['body_html']);

        if (ArticleValidator::wordCount($body) < 100) {
            throw ValidationException::withMessages(['edit.body_html' => 'Isi artikel terlalu pendek.']);
        }

        $article->fill([
            'title' => Str::squish($data['title']),
            'meta_description' => Str::limit(Str::squish($data['meta_description']), 160, ''),
            'summary' => Str::squish($data['summary']),
            'body_html' => $body,
            'word_count' => ArticleValidator::wordCount($body),
        ]);

        if ($article->status === ArticleStatus::Published) {
            $this->publisher->update($article);
        }

        $article->save();

        return $article;
    }

    public function delete(Article $article): void
    {
        if ($article->status === ArticleStatus::Published) {
            $this->publisher->delete($article);

            return;
        }

        $article->forceFill(['status' => ArticleStatus::Deleted])->save();
    }

    public function publish(Article $article): void
    {
        if ($article->status !== ArticleStatus::Ready) {
            return;
        }

        $this->publisher->publish($article);
    }

    /** New picture for an article (costs one Imagen call). */
    public function regenerateImage(Article $article): bool
    {
        $path = $this->images->make($article->site, $article);

        if ($path === null) {
            return false;
        }

        $article->image_path = $path;

        if ($article->status === ArticleStatus::Published) {
            $this->publisher->update($article);
        }

        $article->save();

        return true;
    }
}
