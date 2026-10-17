<?php

use App\Enums\ArticleStatus;
use App\Enums\SiteStatus;
use App\Jobs\PublishSiteArticle;
use App\Livewire\ArticlesPage;
use App\Livewire\SitesPage;
use App\Livewire\TopicsPage;
use App\Models\AiUsage;
use App\Models\Article;
use App\Models\Site;
use App\Services\Content\TopicPlanner;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

it('renders every admin screen', function () {
    $site = Site::factory()->create();
    Article::factory()->create(['site_id' => $site->id]);
    AiUsage::query()->create(['site_id' => $site->id, 'model' => 'gemini-2.5-flash', 'purpose' => 'write', 'cost_estimate' => 0.05]);

    actingAsAdmin()->get(route('sites'))->assertOk()->assertSee($site->name);
    actingAsAdmin()->get(route('topics', $site->id))->assertOk();
    actingAsAdmin()->get(route('articles'))->assertOk()->assertSee('Gabion wall vs concrete wall');
    actingAsAdmin()->get(route('costs'))->assertOk()->assertSee('RM0.05');
});

it('sends guests to the login page', function () {
    $this->get(route('sites'))->assertRedirect(route('login'));
    $this->get('/masuk')->assertOk()->assertSee('Masuk');
});

it('sets up a new website from five fields, and starts it once the business analysis is confirmed', function () {
    $github = fakeGitHub(['Dynopos/gabion' => siteRepo()]);
    fakeVertex(github: $github);
    actingAsAdmin();

    $page = Livewire::test(SitesPage::class)
        ->call('create')
        ->set('form.name', 'Gabion Pantai Timur')
        ->set('form.niche', 'Pasang gabion dan tembok penahan cerun di Kelantan')
        ->set('form.domain', 'https://GabionPantaiTimur.my/')
        ->set('form.github_repo', 'Dynopos/gabion')
        ->set('form.cta_whatsapp', '012-345 6789')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('belum aktif')
        ->assertSee('Betul, sahkan');

    $site = Site::query()->sole();
    expect($site->status)->toBe(SiteStatus::Paused)->and($site->analysis)->not->toBeNull();

    $page->call('activate', $site->id)->assertSee('Sahkan analisis bisnes dahulu');
    $page->call('confirmAnalysis', $site->id)->call('activate', $site->id)->assertSee('aktif');

    $site->refresh();
    expect($site->status)->toBe(SiteStatus::Active)
        ->and($site->installed_at)->not->toBeNull()
        ->and($site->domain)->toBe('gabionpantaitimur.my')
        ->and($site->docroot)->toBe('')
        ->and($site->cta['title'])->toBe('Berminat dengan Gabion Pantai Timur?')
        ->and($site->cta['button'])->toBe('💬 WhatsApp Kami')
        ->and($site->cta['whatsapp'])->toBe('012-345 6789')
        ->and($github->file('Dynopos/gabion', 'blog/index.html'))->toContain('Blog Gabion Pantai Timur');
});

it('detects a Laravel app and writes the blog into public/', function () {
    $github = fakeGitHub(['Dynopos/Dynolead' => ['artisan' => '#!/usr/bin/env php', 'public/index.php' => '<?php']]);
    fakeVertex(github: $github);
    actingAsAdmin();

    Livewire::test(SitesPage::class)
        ->call('create')
        ->set('form.name', 'Dyno Lead')
        ->set('form.niche', 'App cari pelanggan untuk SME')
        ->set('form.domain', 'dynolead.my')
        ->set('form.github_repo', 'Dynopos/Dynolead')
        ->set('form.cta_whatsapp', '0182889932')
        ->call('save')
        ->assertSee('belum aktif');

    expect(Site::query()->sole()->docroot)->toBe('public')
        ->and($github->file('Dynopos/Dynolead', 'public/blog/index.html'))->not->toBeNull()
        ->and($github->file('Dynopos/Dynolead', 'blog/index.html'))->toBeNull();
});

it('keeps a new website paused when the repo is not reachable', function () {
    $github = fakeGitHub([]);
    fakeVertex(github: $github);
    actingAsAdmin();

    Livewire::test(SitesPage::class)
        ->call('create')
        ->set('form.name', 'Kedai X')
        ->set('form.niche', 'Kedai runcit')
        ->set('form.domain', 'kedaix.my')
        ->set('form.github_repo', 'Dynopos/tiada')
        ->set('form.cta_whatsapp', '0123456789')
        ->call('save')
        ->assertSee('belum aktif');

    expect(Site::query()->sole()->status)->toBe(SiteStatus::Paused);
});

it('still installs and activates step by step from the website card', function () {
    $github = fakeGitHub(['Dynopos/gabion' => siteRepo()]);
    fakeVertex(github: $github);
    $site = Site::factory()->create(['github_repo' => 'Dynopos/gabion', 'status' => 'paused', 'installed_at' => null]);
    actingAsAdmin();

    Livewire::test(SitesPage::class)
        ->call('activate', $site->id)->assertSee('Pasang blog dahulu')
        ->call('check', $site->id)->assertSee('Repo Dynopos/gabion OK')
        ->call('install', $site->id)->assertSee('Blog dipasang')
        ->call('activate', $site->id)->assertSee('aktif');

    expect($site->fresh()->status)->toBe(SiteStatus::Active);
});

it('requires a mobile WhatsApp number or a link for the hook', function () {
    actingAsAdmin();

    Livewire::test(SitesPage::class)
        ->call('create')
        ->set('form.name', 'X Bisnes')
        ->set('form.domain', 'x.my')
        ->set('form.github_repo', 'Dynopos/x')
        ->set('form.niche', 'cerun')
        ->set('form.cta_whatsapp', '03-1234 5678')
        ->call('save')
        ->assertHasErrors('form.cta_whatsapp');
});

it('queues an article on demand', function () {
    Queue::fake();
    $site = Site::factory()->create();
    actingAsAdmin();

    Livewire::test(SitesPage::class)->call('publishNow', $site->id)->assertSee('sedang ditulis');

    Queue::assertPushed(PublishSiteArticle::class, fn ($job) => $job->siteId === $site->id && $job->manual);
});

it('resumes websites paused for AI', function () {
    $site = Site::factory()->create(['status' => SiteStatus::AiPaused, 'status_note' => 'Vertex AI berhenti']);
    actingAsAdmin();

    Livewire::test(SitesPage::class)->assertSee('Sambung semula semua')->call('resumeAi');

    expect($site->fresh()->status)->toBe(SiteStatus::Active)->and($site->fresh()->status_note)->toBeNull();
});

it('adds a manual topic at the top and refuses duplicates', function () {
    $site = Site::factory()->create();
    app(TopicPlanner::class)->add($site, 'Tanda cerun akan runtuh', 'tanda cerun runtuh');
    actingAsAdmin();

    Livewire::test(TopicsPage::class, ['siteId' => $site->id])
        ->set('title', 'Gabion wall vs concrete wall')
        ->call('add')->assertSee('Topik ditambah')
        ->set('title', 'Concrete wall vs gabion wall')
        ->call('add')->assertSee('sama dengan topik');

    expect($site->topics()->where('status', 'planned')->orderBy('position')->first()->title)->toBe('Gabion wall vs concrete wall');
});

it('edits a published article and commits the change to the website', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    $article = Article::factory()->create(['site_id' => $site->id]);
    fakeVertex(github: $github);
    actingAsAdmin();

    Livewire::test(ArticlesPage::class)
        ->call('startEdit', $article->id)
        ->set('edit.title', 'Tajuk Baru Gabion')
        ->set('edit.body_html', articleBody(300).'<script>alert(1)</script>')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('dikemas kini');

    $html = $github->file($site->github_repo, $article->pagePath());
    expect($html)->toContain('Tajuk Baru Gabion')->not->toContain('alert(1)')
        ->and($article->fresh()->content_updated_at)->not->toBeNull()
        ->and($github->commits[0]['message'])->toStartWith('Kemas kini artikel');
});

it('deletes a published article from the website', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    $article = Article::factory()->create(['site_id' => $site->id, 'image_path' => 'blog/img/x.webp']);
    $github->repos[$site->github_repo][$article->pagePath()] = 'old';
    $github->repos[$site->github_repo]['blog/img/x.webp'] = 'img';
    fakeVertex(github: $github);
    actingAsAdmin();

    Livewire::test(ArticlesPage::class)->call('delete', $article->id)->assertSee('dipadam');

    expect($article->fresh()->status)->toBe(ArticleStatus::Deleted)
        ->and($github->file($site->github_repo, $article->pagePath()))->toBeNull()
        ->and($github->file($site->github_repo, 'blog/img/x.webp'))->toBeNull()
        ->and($github->file($site->github_repo, 'blog/sitemap.xml'))->not->toContain($article->slug);
});
