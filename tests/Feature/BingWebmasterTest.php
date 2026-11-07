<?php

use App\Jobs\SetUpBing;
use App\Livewire\SitesPage;
use App\Services\Bing\BingSetup;
use App\Services\Publishing\RepoGuard;
use App\Services\Publishing\SitePublisher;
use App\Services\Sites\SiteService;
use App\Support\Tenancy\CurrentWorkspace;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * Bing Webmaster Tools: the app commits BingSiteAuth.xml (the admin's Bing code) to each website,
 * then, with the API key, adds the website to Bing, verifies it and submits its sitemap once.
 * Off until BING_SITE_AUTH / BING_WEBMASTER_API_KEY are set.
 */
const BING_CODE = '362FDA5B763C6118FFC59194201DF5EF';

beforeEach(fn () => fakeGitHub([]));

function enableBing(bool $api = true): void
{
    config()->set('dynoseo.bing.site_auth', BING_CODE);
    config()->set('services.bing_webmaster.api_key', $api ? 'kunci-bing-ujian' : null);
}

/** Fake Bing API. $sites = GetUserSites list; $verified = VerifySite result. */
function fakeBing(array $sites = [], bool $verified = true, int $status = 200): void
{
    Http::fake([
        'api.github.com/*' => test()->github->handler(),
        'ssl.bing.com/webmaster/api.svc/json/GetUserSites*' => Http::response(['d' => $sites], $status),
        'ssl.bing.com/webmaster/api.svc/json/VerifySite*' => Http::response(['d' => $verified], $status),
        'ssl.bing.com/webmaster/api.svc/json/*' => Http::response(['d' => null], $status),
    ]);
}

function bingCalls(): array
{
    return Http::recorded(fn (Request $r) => str_contains($r->url(), 'ssl.bing.com'))
        ->map(fn ($pair) => preg_replace('#^.*/json/([A-Za-z]+)\?.*$#', '$1', $pair[0]->url()))
        ->values()
        ->all();
}

it('commits BingSiteAuth.xml with the admin Bing code when the blog is installed', function () {
    enableBing(api: false);
    $site = makeSite(['installed_at' => null]);
    fakeBing();

    app(SitePublisher::class)->install($site);

    expect($this->github->repos[$site->github_repo]['BingSiteAuth.xml'])
        ->toBe("<?xml version=\"1.0\"?>\n<users>\n\t<user>".BING_CODE."</user>\n</users>\n")
        ->and(bingCalls())->toBe([]);
});

it('writes nothing for Bing and refuses the file while the code is not set', function () {
    $site = makeSite(['installed_at' => null]);
    fakeBing();

    app(SitePublisher::class)->install($site);

    expect($this->github->repos[$site->github_repo])->not->toHaveKey('BingSiteAuth.xml')
        ->and(RepoGuard::isAllowed('BingSiteAuth.xml'))->toBeFalse();
});

it('never overwrites a BingSiteAuth.xml the owner already has (rule 5)', function () {
    enableBing(api: false);
    $own = "<?xml version=\"1.0\"?>\n<users>\n\t<user>PEMILIK123</user>\n</users>";
    $site = makeSite(['installed_at' => null], ['BingSiteAuth.xml' => $own]);
    fakeBing();

    app(SitePublisher::class)->install($site);
    app(SitePublisher::class)->rebuild($site);

    expect($this->github->repos[$site->github_repo]['BingSiteAuth.xml'])->toBe($own)
        ->and(app(SitePublisher::class)->installBingAuth($site))->toBeFalse();
});

it('puts BingSiteAuth.xml in public/ for a Laravel website', function () {
    enableBing(api: false);
    $site = makeSite(['docroot' => 'public', 'installed_at' => null]);
    $this->github->repos[$site->github_repo] = ['artisan' => '<?php', 'public/index.php' => '<?php'];
    fakeBing();

    app(SitePublisher::class)->install($site);

    expect($this->github->repos[$site->github_repo])->toHaveKey('public/BingSiteAuth.xml')
        ->not->toHaveKey('BingSiteAuth.xml')
        ->and($site->sitemapUrl())->toBe($site->baseUrl().'/blog/sitemap.xml');
});

it('adds the file to a website installed before the Bing code was set', function () {
    $site = makeSite();
    enableBing(api: false);
    fakeBing();

    expect(app(SitePublisher::class)->installBingAuth($site))->toBeTrue()
        ->and($this->github->repos[$site->github_repo])->toHaveKey('BingSiteAuth.xml')
        ->and(collect($this->github->commits)->last()['paths'])->toBe(['BingSiteAuth.xml'])
        ->and(app(SitePublisher::class)->installBingAuth($site))->toBeFalse();
});

it('adds, verifies and submits the sitemap once, with the API key', function () {
    enableBing();
    $site = makeSite();
    fakeBing();

    expect(app(BingSetup::class)->run($site))->toBeTrue();

    $site->refresh();
    expect(bingCalls())->toBe(['GetUserSites', 'AddSite', 'VerifySite', 'SubmitFeed'])
        ->and($site->bing_verified_at)->not->toBeNull()
        ->and($site->bing_sitemap_at)->not->toBeNull()
        ->and($site->bing_error)->toBeNull();

    $sent = Http::recorded(fn (Request $r) => str_contains($r->url(), 'SubmitFeed'))->first()[0];
    expect($sent->url())->toContain('apikey=kunci-bing-ujian')
        ->and($sent['siteUrl'])->toBe($site->baseUrl().'/')
        ->and($sent['feedUrl'])->toBe($site->baseUrl().'/sitemap.xml');

    // Done: nothing more is sent.
    expect(app(BingSetup::class)->run($site))->toBeTrue()
        ->and(bingCalls())->toHaveCount(4);
});

it('does not add a website Bing already has verified', function () {
    enableBing();
    $site = makeSite();
    fakeBing([['__type' => 'Site:#Microsoft.Bing.Webmaster.Api', 'Url' => $site->baseUrl().'/', 'IsVerified' => true]]);

    expect(app(BingSetup::class)->run($site))->toBeTrue()
        ->and(bingCalls())->toBe(['GetUserSites', 'SubmitFeed']);
});

it('keeps a clear error when Bing cannot verify yet, and submits no sitemap', function () {
    enableBing();
    $site = makeSite();
    fakeBing(verified: false);

    expect(app(BingSetup::class)->run($site))->toBeFalse();

    $site->refresh();
    expect($site->bing_verified_at)->toBeNull()
        ->and($site->bing_error)->toContain('BingSiteAuth.xml')
        ->and(bingCalls())->not->toContain('SubmitFeed');
});

it('stores a Bing API error on the website without throwing', function () {
    enableBing();
    $site = makeSite();
    fakeBing(status: 401);

    expect(app(BingSetup::class)->run($site))->toBeFalse()
        ->and($site->fresh()->bing_error)->toContain('BING_WEBMASTER_API_KEY');
});

it('sends nothing to Bing without the API key', function () {
    enableBing(api: false);
    $site = makeSite();
    fakeBing();

    expect(app(BingSetup::class)->run($site))->toBeFalse()
        ->and(bingCalls())->toBe([]);
});

it('queues the Bing setup after install, once the website has had time to deploy', function () {
    enableBing();
    Queue::fake([SetUpBing::class]);
    $site = makeSite(['installed_at' => null]);
    fakeBing();

    app(SiteService::class)->install($site);

    Queue::assertPushed(SetUpBing::class, fn ($job) => $job->siteId === $site->id && $job->delay !== null);

    (new SetUpBing($site->id))->handle(app(BingSetup::class), app(CurrentWorkspace::class));
    expect($site->fresh()->bing_sitemap_at)->not->toBeNull();
});

it('does not queue Bing without the API key', function () {
    enableBing(api: false);
    Queue::fake([SetUpBing::class]);
    $site = makeSite(['installed_at' => null]);
    fakeBing();

    app(SiteService::class)->install($site);

    Queue::assertNotPushed(SetUpBing::class);
});

it('shows Daftar Bing on the website card and hides it once Bing is done', function () {
    actingAsAdmin();
    $site = makeSite();
    fakeBing();

    Livewire::test(SitesPage::class)->assertDontSee('Daftar Bing');

    enableBing();
    Queue::fake([SetUpBing::class]);

    Livewire::test(SitesPage::class)
        ->assertSee('Daftar Bing')
        ->call('bing', $site->id)
        ->assertSee('BingSiteAuth.xml di-commit');

    Queue::assertPushed(SetUpBing::class);

    $site->forceFill(['bing_verified_at' => now(), 'bing_sitemap_at' => now()])->save();
    Livewire::test(SitesPage::class)->assertDontSee('Daftar Bing')->assertSee('sitemap dihantar');
});
