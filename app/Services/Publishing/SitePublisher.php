<?php

namespace App\Services\Publishing;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\PublishLog;
use App\Models\Site;
use App\Services\Content\ImageMaker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes a website's blog to its GitHub repo, one commit per action (spec §4 steps 6–9).
 * The database is the source of truth: the blog index, sitemap, feed and home block are
 * rebuilt from published articles every time. Every commit passes RepoGuard (rule 5).
 */
class SitePublisher
{
    /** index.html as read for this commit, so RepoGuard can check only the block changed. */
    private ?string $originalHome = null;

    public function __construct(
        private GitHubClient $github,
        private BlogRenderer $renderer,
        private IndexNowClient $indexNow,
    ) {}

    /** One-off setup: templates, CSS, sitemap index, robots.txt, IndexNow key file. */
    public function install(Site $site): string
    {
        $files = [];

        foreach (SiteTemplates::NAMES as $name) {
            if ($this->github->getFile($site->github_repo, $site->branch, "_dynoseo/{$name}.html") === null) {
                $files["_dynoseo/{$name}.html"] = SiteTemplates::default($name);
            }
        }

        $files['blog/assets/dynoseo.css'] = SiteTemplates::css();

        $sitemap = $this->github->getFile($site->github_repo, $site->branch, 'sitemap.xml');
        if ($sitemap === null || ! str_contains($sitemap['content'], 'blog/sitemap.xml')) {
            if ($sitemap !== null && str_contains($sitemap['content'], '</sitemapindex>')) {
                // Already an index: add the blog sitemap to it.
                $entry = '  <sitemap><loc>'.$site->baseUrl().'/blog/sitemap.xml</loc></sitemap>'."\n";
                $files['sitemap.xml'] = str_replace('</sitemapindex>', $entry.'</sitemapindex>', $sitemap['content']);
            } else {
                if ($sitemap !== null) {
                    // The site's own page list moves to sitemap-pages.xml, unchanged.
                    $files['sitemap-pages.xml'] = $sitemap['content'];
                }
                $files['sitemap.xml'] = $this->renderer->sitemapIndex($site, $sitemap !== null);
            }
        }

        $robots = $this->github->getFile($site->github_repo, $site->branch, 'robots.txt');
        $sitemapLine = 'Sitemap: '.$site->baseUrl().'/sitemap.xml';
        if ($robots === null) {
            $files['robots.txt'] = "User-agent: *\nAllow: /\n\n{$sitemapLine}\n";
        } elseif (! str_contains($robots['content'], $sitemapLine)) {
            $files['robots.txt'] = rtrim($robots['content'])."\n\n{$sitemapLine}\n";
        }

        $key = (string) config('dynoseo.indexnow_key');
        if ($key !== '') {
            $files[$key.'.txt'] = $key;
        }

        $files += $this->listingFiles($site, SiteTemplates::load($site, $this->github), $this->published($site));

        $sha = $this->commit($site, null, 'install', $files, 'Dyno SEO: pasang blog');
        $site->forceFill(['installed_at' => now()])->save();

        return $sha;
    }

    /** Publish a ready article (rule 3: with the hook), then refresh index, sitemap, feed, home block. */
    public function publish(Article $article): string
    {
        $site = $article->site;

        if (! $site->installed_at) {
            $this->install($site);
        }

        $article->status = ArticleStatus::Published;
        $article->published_at ??= now();

        try {
            $sha = $this->writeArticle($site, $article, 'publish', 'Artikel baru: '.$article->title);
        } catch (Throwable $e) {
            $article->status = ArticleStatus::Ready;
            $article->published_at = null;

            throw $e;
        }

        $article->forceFill(['commit_sha' => $sha])->save();
        $site->forceFill(['last_published_at' => now()])->save();
        $this->indexNow->submit($site->host(), [$article->url(), $site->baseUrl().'/blog/']);

        return $sha;
    }

    /** Re-write a published article after an edit (or a new image). */
    public function update(Article $article): string
    {
        $article->content_updated_at = now();
        $sha = $this->writeArticle($article->site, $article, 'update', 'Kemas kini artikel: '.$article->title);
        $article->forceFill(['commit_sha' => $sha])->save();
        $this->indexNow->submit($article->site->host(), [$article->url()]);

        return $sha;
    }

    /** Remove an article page and image from the website. */
    public function delete(Article $article): string
    {
        $site = $article->site;
        $previous = $article->status;
        $article->status = ArticleStatus::Deleted;

        $files = [$article->pagePath() => null];
        if ($article->image_path) {
            $files[$article->image_path] = null;
        }

        try {
            $files += $this->listingFiles($site, SiteTemplates::load($site, $this->github), $this->published($site, $article));
            $sha = $this->commit($site, $article, 'delete', $files, 'Padam artikel: '.$article->title);
        } catch (Throwable $e) {
            $article->status = $previous;

            throw $e;
        }

        $article->save();

        return $sha;
    }

    /** Re-write every published article page (e.g. after changing the templates or the hook). */
    public function rebuild(Site $site): string
    {
        $templates = SiteTemplates::load($site, $this->github);
        $published = $this->published($site);
        $files = ['blog/assets/dynoseo.css' => SiteTemplates::css()];

        foreach ($published as $article) {
            $files += $this->articleFiles($site, $article, $templates, $published);
        }

        $files += $this->listingFiles($site, $templates, $published);

        return $this->commit($site, null, 'rebuild', $files, 'Dyno SEO: bina semula blog');
    }

    private function writeArticle(Site $site, Article $article, string $action, string $message): string
    {
        $templates = SiteTemplates::load($site, $this->github);
        $published = $this->published($site, $article);

        $files = $this->articleFiles($site, $article, $templates, $published)
            + $this->listingFiles($site, $templates, $published);

        return $this->commit($site, $article, $action, $files, $message);
    }

    /** @return array<string, string> */
    private function articleFiles(Site $site, Article $article, SiteTemplates $templates, Collection $published): array
    {
        $related = $published->reject(fn (Article $a) => $a->id === $article->id)
            ->sortByDesc(fn (Article $a) => [$a->topic?->cluster === $article->topic?->cluster ? 1 : 0, $a->published_at?->timestamp])
            ->take((int) config('dynoseo.articles.related'))
            ->values();

        $files = [$article->pagePath() => $this->renderer->articlePage($site, $article, $templates, $related)];

        if ($article->image_path) {
            $image = Storage::disk('local')->get(ImageMaker::storagePath($site, $article->image_path));

            if ($image !== null) {
                $files[$article->image_path] = $image;
            }
        }

        return $files;
    }

    /**
     * Blog index pages, sitemap, feed and the home block.
     *
     * @return array<string, string|null>
     */
    private function listingFiles(Site $site, SiteTemplates $templates, Collection $published): array
    {
        $files = $this->renderer->listingPages($site, $published, $templates);
        $pages = count($files);

        // Remove listing pages that no longer have articles.
        for ($n = $pages + 1; $n <= $site->blog_pages; $n++) {
            $files["blog/page/{$n}/index.html"] = null;
        }

        $files['blog/sitemap.xml'] = $this->renderer->sitemap($site, $published);
        $files['blog/feed.xml'] = $this->renderer->feed($site, $published);

        $home = $this->github->getFile($site->github_repo, $site->branch, 'index.html');
        if ($home !== null && RepoGuard::hasBlock($home['content'])) {
            $latest = $published->take((int) config('dynoseo.articles.home_cards'));
            $files['index.html'] = RepoGuard::replaceBlock($home['content'], $this->renderer->homeBlock($site, $latest, $templates));
            $this->originalHome = $home['content'];
        }

        $site->blog_pages = $pages;

        return $files;
    }

    private function commit(Site $site, ?Article $article, string $action, array $files, string $message): string
    {
        $original = $this->originalHome;
        $this->originalHome = null;

        RepoGuard::assertAllowed($files, $original);

        try {
            $sha = $this->github->commit($site->github_repo, $site->branch, $files, Str::limit($message, 120));
        } catch (Throwable $e) {
            $site->blog_pages = (int) $site->getOriginal('blog_pages');

            PublishLog::query()->create([
                'workspace_id' => $site->workspace_id,
                'site_id' => $site->id,
                'article_id' => $article?->id,
                'action' => $action,
                'paths' => array_keys($files),
                'error' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            throw $e;
        }

        $site->save();

        PublishLog::query()->create([
            'workspace_id' => $site->workspace_id,
            'site_id' => $site->id,
            'article_id' => $article?->id,
            'action' => $action,
            'commit_sha' => $sha,
            'paths' => array_keys($files),
        ]);

        return $sha;
    }

    /**
     * Published articles, newest first. $current is included (or excluded when deleted)
     * with its in-memory state, so the listing reflects the change being committed.
     *
     * @return Collection<int, Article>
     */
    private function published(Site $site, ?Article $current = null): Collection
    {
        $articles = $site->articles()->published()->with('topic')
            ->when($current?->exists, fn ($q) => $q->whereKeyNot($current->id))
            ->get();

        if ($current !== null && $current->status === ArticleStatus::Published) {
            $articles->push($current);
        }

        return $articles->sortByDesc(fn (Article $a) => [$a->published_at?->timestamp ?? 0, $a->id])->values()
            ->each(fn (Article $a) => $a->setRelation('site', $site));
    }
}
