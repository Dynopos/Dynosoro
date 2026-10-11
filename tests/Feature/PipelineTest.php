<?php

use App\Enums\ArticleStatus;
use App\Jobs\PublishSiteArticle;
use App\Models\AiUsage;
use App\Models\Article;
use App\Models\PublishLog;
use App\Services\Content\TopicPlanner;

it('writes, checks, illustrates and publishes one article in one commit', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    $article = Article::query()->sole();
    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->slug)->toBe('gabion-wall-vs-concrete-wall')
        ->and($article->image_path)->toBe('blog/img/gabion-wall-vs-concrete-wall.webp')
        ->and($article->commit_sha)->not->toBeNull();

    // One commit with every file the article touches.
    expect($github->commits)->toHaveCount(1)
        ->and($github->commits[0]['paths'])->toContain(
            'blog/gabion-wall-vs-concrete-wall/index.html',
            'blog/img/gabion-wall-vs-concrete-wall.webp',
            'blog/index.html',
            'blog/sitemap.xml',
            'blog/feed.xml',
            'index.html',
        );

    $repo = $github->repos[$site->github_repo];
    expect($repo['blog/gabion-wall-vs-concrete-wall/index.html'])
        ->toContain('<h1>Gabion Wall vs Concrete Wall untuk Cerun Rumah</h1>')
        ->toContain('application/ld+json')
        ->toContain('"@type":"BlogPosting"')
        ->toContain('rel="canonical" href="https://'.$site->domain.'/blog/gabion-wall-vs-concrete-wall/"')
        ->and(substr($repo['blog/img/gabion-wall-vs-concrete-wall.webp'], 8, 4))->toBe('WEBP')
        ->and($repo['blog/index.html'])->toContain('/blog/gabion-wall-vs-concrete-wall/')
        ->and($repo['blog/sitemap.xml'])->toContain('https://'.$site->domain.'/blog/gabion-wall-vs-concrete-wall/')
        ->and($repo['index.html'])->toContain('dynoseo-latest');

    // Plan, write, check and image calls are all recorded in this workspace.
    expect(AiUsage::query()->pluck('purpose')->sort()->values()->all())->toBe(['check', 'image', 'plan', 'write'])
        ->and(AiUsage::query()->where('workspace_id', $site->workspace_id)->count())->toBe(4);

    $site->refresh();
    expect($site->next_publish_at->isAfter(now()->addDay()))->toBeTrue()
        ->and($site->next_publish_at->isBefore(now()->addDays(3)))->toBeTrue()
        ->and($site->failure_count)->toBe(0)
        ->and($site->last_published_at)->not->toBeNull()
        ->and(PublishLog::query()->where('action', 'publish')->whereNotNull('commit_sha')->count())->toBe(1);
});

it('installs the blog before the first article on a new website', function () {
    $github = fakeGitHub([]);
    $site = makeSite(['installed_at' => null]);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    $repo = $github->repos[$site->github_repo];
    expect($github->commits)->toHaveCount(2)
        ->and($github->commits[0]['message'])->toBe('Dyno SEO: pasang blog')
        ->and($repo)->toHaveKeys(['_dynoseo/article.html', '_dynoseo/cta.html', 'blog/assets/dynoseo.css', 'robots.txt', 'sitemap-pages.xml'])
        ->and($repo['sitemap.xml'])->toContain('<sitemapindex')->toContain('/blog/sitemap.xml')->toContain('/sitemap-pages.xml')
        ->and($repo['sitemap-pages.xml'])->toContain('<urlset')
        ->and($repo['robots.txt'])->toContain('Sitemap: https://'.$site->domain.'/sitemap.xml')
        ->and($site->fresh()->installed_at)->not->toBeNull();
});

it('uses the website\'s own templates from _dynoseo when present', function () {
    $github = fakeGitHub([]);
    $site = makeSite([], ['_dynoseo/article.html' => '<main class="mine">{{title}}|{{body}}|{{cta}}</main>']);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    expect($github->file($site->github_repo, 'blog/gabion-wall-vs-concrete-wall/index.html'))
        ->toStartWith('<main class="mine">Gabion Wall vs Concrete Wall untuk Cerun Rumah|<h2>')
        ->toContain('dynoseo-cta');
});

it('skips the image but still publishes when Imagen returns nothing', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    fakeVertex(github: $github, image: false);

    PublishSiteArticle::dispatchSync($site->id);

    $article = Article::query()->sole();
    expect($article->status)->toBe(ArticleStatus::Published)
        ->and($article->image_path)->toBeNull()
        ->and(vertexRequests('image'))->toHaveCount(2);
});

it('does nothing for paused websites unless run manually', function () {
    $github = fakeGitHub([]);
    $site = makeSite(['status' => 'paused']);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);
    expect(vertexCalls())->toBe(0)->and($github->commits)->toBe([]);

    PublishSiteArticle::dispatchSync($site->id, true);
    expect(Article::query()->published()->count())->toBe(1);
});

it('refills the topic plan only when fewer than five topics are left', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    fakeVertex(github: $github);
    foreach (range(1, 6) as $i) {
        app(TopicPlanner::class)->add($site, "Topik ujian nombor {$i} tentang cerun", "kata kunci {$i} cerun");
    }

    PublishSiteArticle::dispatchSync($site->id);

    expect(vertexRequests('plan'))->toHaveCount(0)
        ->and($site->topics()->where('status', 'used')->count())->toBe(1);
});

it('publishes into public/ for a Laravel website and leaves its sitemap and robots alone', function () {
    $github = fakeGitHub([]);
    $site = makeSite(['docroot' => 'public', 'installed_at' => null, 'domain' => 'dynolead.my']);
    $github->repos[$site->github_repo] = ['routes/web.php' => '<?php // app', 'public/index.php' => '<?php'];
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    $repo = $github->repos[$site->github_repo];
    $paths = collect($github->commits)->pluck('paths')->flatten();

    expect($repo)->toHaveKeys([
        'public/blog/gabion-wall-vs-concrete-wall/index.html',
        'public/blog/img/gabion-wall-vs-concrete-wall.webp',
        'public/blog/index.html',
        'public/blog/sitemap.xml',
        'public/blog/assets/dynoseo.css',
        '_dynoseo/article.html',
    ])
        ->and($paths->reject(fn ($p) => str_starts_with($p, 'public/blog/') || str_starts_with($p, '_dynoseo/'))->all())->toBe([])
        ->and($repo['routes/web.php'])->toBe('<?php // app')
        ->and($repo['public/blog/gabion-wall-vs-concrete-wall/index.html'])->toContain('https://dynolead.my/blog/gabion-wall-vs-concrete-wall/');
});
