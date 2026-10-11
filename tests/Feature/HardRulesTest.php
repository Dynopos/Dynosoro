<?php

use App\Enums\ArticleStatus;
use App\Exceptions\BudgetExceeded;
use App\Exceptions\PublishRuleViolation;
use App\Jobs\PublishSiteArticle;
use App\Models\AiUsage;
use App\Models\Article;
use App\Models\Site;
use App\Services\Ai\AiBudget;
use App\Services\Content\ArticleValidator;
use App\Services\Content\HtmlSanitizer;
use App\Services\Content\TopicPlanner;
use App\Services\Publishing\RepoGuard;

// Rule 1: articles are general; the business only appears in the hook.
it('rejects a draft that names the business or mentions prices, then rewrites it', function () {
    $github = fakeGitHub([]);
    $site = makeSite(['name' => 'Gabion Pantai Timur']);
    fakeVertex(write: [
        geminiReply(draftReply(['body_html' => articleBody(850, '<p>Gabion Pantai Timur pasang dari RM150 sahaja.</p>')])),
        geminiReply(draftReply()),
    ], github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    $rewrite = vertexRequests('write')->last()['contents'][0]['parts'][0]['text'];
    expect(vertexRequests('write'))->toHaveCount(2)
        ->and($rewrite)->toContain('Jangan sebut nama bisnes')->toContain('Jangan sebut harga')
        ->and(Article::query()->sole()->body_html)->not->toContain('Gabion Pantai Timur');
});

it('never gives the writer the business name, phone or hook', function () {
    $github = fakeGitHub([]);
    $site = makeSite(['name' => 'Gabion Pantai Timur']);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    $request = vertexRequests('write')->first();
    $prompt = json_encode($request->data(), JSON_UNESCAPED_UNICODE);
    expect($prompt)->not->toContain('Gabion Pantai Timur')
        ->not->toContain($site->cta['whatsapp'])
        ->not->toContain($site->cta['title']);
});

// Rule 2: no invented facts.
it('flags standards, percentages and quoted studies in code before the AI check', function (string $sentence) {
    $site = Site::factory()->create();
    $issues = app(ArticleValidator::class)->issues($site, draftReply(['body_html' => articleBody(850, "<p>{$sentence}</p>")]));

    expect($issues)->not->toBeEmpty();
})->with([
    'JKR' => 'Tembok mesti ikut spesifikasi JKR terkini.',
    'MS code' => 'Gunakan dawai ikut MS 1234.',
    'percent' => 'Gabion mengurangkan hakisan sebanyak 40% berbanding konkrit.',
    'study' => 'Kajian terbaru menunjukkan gabion lebih tahan.',
]);

it('rewrites when the AI check fails and gives up after two rewrites (rule 4)', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    fakeVertex(check: [geminiReply(['pass' => false, 'issues' => ['Ada fakta yang meragukan tentang kekuatan tanah.']])], github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    $article = Article::query()->sole();
    expect(vertexRequests('write'))->toHaveCount(3)
        ->and(vertexRequests('check'))->toHaveCount(3)
        ->and(vertexRequests('image'))->toHaveCount(0)
        ->and($article->status)->toBe(ArticleStatus::Failed)
        ->and($article->check_notes)->toBe(['Ada fakta yang meragukan tentang kekuatan tanah.'])
        ->and($article->topic->status)->toBe('skipped')
        ->and($github->commits)->toBe([])
        ->and($site->fresh()->failure_count)->toBe(1);
});

// Rule 3: every article has the hook.
it('ends every article with the hook, linking to WhatsApp with the article title', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    $html = $github->file($site->github_repo, 'blog/gabion-wall-vs-concrete-wall/index.html');
    expect($html)->toContain('dynoseo-cta')
        ->toContain('Cerun rumah anda mula runtuh?')
        ->toContain('https://wa.me/60123456789?text='.rawurlencode('Salam, saya baca artikel "Gabion Wall vs Concrete Wall untuk Cerun Rumah" di website anda. Nak tanya...'));
});

it('falls back to the hook link when there is no mobile WhatsApp number', function () {
    $github = fakeGitHub([]);
    $site = makeSite(['cta' => ['title' => 'Perlukan bantuan?', 'text' => 'Hubungi pasukan kami.', 'button' => 'Hubungi', 'whatsapp' => '03-1234 5678', 'url' => '/hubungi/']]);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    expect($github->file($site->github_repo, 'blog/gabion-wall-vs-concrete-wall/index.html'))
        ->toContain('href="/hubungi/"')->not->toContain('wa.me');
});

// Rule 4: budget before every call.
it('makes no AI call when the monthly budget is used up', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    fakeVertex(github: $github);
    AiUsage::query()->create(['model' => 'gemini-2.5-flash', 'purpose' => 'write', 'cost_estimate' => 100]);

    PublishSiteArticle::dispatchSync($site->id);

    expect(vertexCalls())->toBe(0)
        ->and($github->commits)->toBe([])
        ->and($site->fresh()->status_note)->toContain('Had kos AI')
        ->and($site->fresh()->next_publish_at->isTomorrow())->toBeTrue();
});

it('makes no AI call when the daily call limit is reached', function () {
    config(['dynoseo.ai.max_daily_calls' => 2]);
    AiUsage::query()->create(['model' => 'gemini-2.5-flash', 'purpose' => 'write', 'cost_estimate' => 0]);
    AiUsage::query()->create(['model' => 'gemini-2.5-flash', 'purpose' => 'write', 'cost_estimate' => 0]);

    expect(fn () => app(AiBudget::class)->assertCanSpend(0.01))->toThrow(BudgetExceeded::class, BudgetExceeded::DAILY);
});

it('makes no AI call for a model without a price', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    config(['dynoseo.ai.model_check' => 'gemini-unknown']);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    expect(vertexCalls())->toBe(0)
        ->and($site->fresh()->status->value)->toBe('error')
        ->and($site->fresh()->status_note)->toContain('gemini-unknown');
});

// Rule 5: only Dyno SEO files; clean HTML.
it('refuses to write files outside the Dyno SEO paths', function (string $path) {
    expect(fn () => RepoGuard::assertAllowed([$path => 'x']))->toThrow(PublishRuleViolation::class);
})->with(['CNAME', 'about.html', 'assets/app.js', '../blog/x', '.github/workflows/x.yml', '/blog/x.html']);

it('only changes the home page between the dynoseo markers', function () {
    $home = '<h1>Hi</h1><!-- dynoseo:latest -->old<!-- /dynoseo:latest --><p>Footer</p>';

    expect(fn () => RepoGuard::assertAllowed(['index.html' => RepoGuard::replaceBlock($home, 'new')], $home))->not->toThrow(PublishRuleViolation::class)
        ->and(fn () => RepoGuard::assertAllowed(['index.html' => str_replace('Footer', 'Hacked', $home)], $home))->toThrow(PublishRuleViolation::class)
        ->and(fn () => RepoGuard::assertAllowed(['index.html' => 'new'], null))->toThrow(PublishRuleViolation::class);
});

it('leaves the home page alone when it has no markers', function () {
    $github = fakeGitHub([]);
    $site = makeSite([], ['index.html' => '<html><body>No markers</body></html>']);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    expect($github->commits[0]['paths'])->not->toContain('index.html')
        ->and($github->file($site->github_repo, 'index.html'))->toBe('<html><body>No markers</body></html>');
});

it('strips scripts, event handlers, iframes and external links from article HTML', function () {
    $clean = app(HtmlSanitizer::class)->clean(
        '<h1>T</h1><p onclick="x()">Hi <script>alert(1)</script><a href="https://evil.example">luar</a> <a href="/blog/lain/">dalam</a></p>'
        .'<iframe src="https://x"></iframe><style>p{}</style><img src=x onerror=alert(1)><div><span>teks</span></div>'
    );

    expect($clean)->toBe('<h2>T</h2><p>Hi luar <a href="/blog/lain/">dalam</a></p>teks');
});

// Rule 6: data per customer is isolated.
it('runs each website inside its own workspace', function () {
    [$other] = otherWorkspace();
    $github = fakeGitHub([]);
    $site = Site::factory()->create(['workspace_id' => $other->id]);
    $github->repos[$site->github_repo] = siteRepo();
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    expect(Article::query()->count())->toBe(0)
        ->and(Article::query()->withoutGlobalScope('workspace')->sole()->workspace_id)->toBe($other->id)
        ->and(AiUsage::query()->withoutGlobalScope('workspace')->pluck('workspace_id')->unique()->all())->toBe([$other->id]);
});

it('does not show or open another workspace\'s websites', function () {
    [$other] = otherWorkspace();
    $theirs = Site::factory()->create(['workspace_id' => $other->id, 'name' => 'Kedai Orang Lain']);

    actingAsAdmin()->get(route('sites'))->assertOk()->assertDontSee('Kedai Orang Lain');
    actingAsAdmin()->get(route('topics', $theirs->id))->assertNotFound();
});

it('keeps non-admin users out of the Fasa 0 screens', function () {
    [, $user] = otherWorkspace();

    foreach (['sites', 'articles', 'costs'] as $route) {
        $this->actingAs($user)->get(route($route))->assertForbidden();
    }
});

// Rule 7: no secrets in the repo.
it('ships .env.example with names only for secrets', function () {
    $env = collect(file(base_path('.env.example'), FILE_IGNORE_NEW_LINES))
        ->filter(fn ($line) => str_contains($line, '=') && ! str_starts_with($line, '#'))
        ->mapWithKeys(fn ($line) => [strstr($line, '=', true) => substr(strstr($line, '='), 1)]);

    foreach (['APP_KEY', 'DB_PASSWORD', 'MAIL_PASSWORD', 'GITHUB_TOKEN', 'VERTEX_ACCESS_TOKEN', 'GOOGLE_APPLICATION_CREDENTIALS', 'GOOGLE_CREDENTIALS_BASE64', 'INDEXNOW_KEY', 'GCP_PROJECT_ID'] as $key) {
        expect($env[$key] ?? null)->toBe('', $key);
    }

    expect(file_get_contents(base_path('.gitignore')))->toContain('.env')->toContain('service-account*.json');
});

// Rule 8: no repeated topic on the same website.
it('drops planned topics that repeat an existing topic or article', function () {
    $site = Site::factory()->create();
    Article::factory()->create(['site_id' => $site->id, 'title' => 'Cara Jaga Cerun Semasa Musim Tengkujuh']);
    $planner = app(TopicPlanner::class);

    expect($planner->add($site, 'Gabion wall vs concrete wall', 'gabion wall vs concrete wall'))->not->toBeNull()
        ->and($planner->add($site, 'Concrete wall vs gabion wall: mana lebih baik', 'Concrete Wall vs Gabion Wall'))->toBeNull()
        ->and($planner->add($site, 'Cara jaga cerun semasa musim tengkujuh', 'cerun tengkujuh'))->toBeNull()
        ->and($site->topics()->count())->toBe(1);
});

it('rejects an article title that repeats a published one', function () {
    $site = Site::factory()->create();
    Article::factory()->create(['site_id' => $site->id, 'title' => 'Gabion Wall vs Concrete Wall untuk Cerun Rumah']);

    expect(app(ArticleValidator::class)->issues($site, draftReply()))->toContain('Tajuk ini sama dengan artikel yang sudah terbit.');
});

it('skips banned topics', function () {
    $site = Site::factory()->create(['banned_topics' => 'harga, pesaing']);

    expect(app(TopicPlanner::class)->add($site, 'Harga gabion terkini', 'harga gabion'))->toBeNull();
});
