<?php

use App\Jobs\PostArticleToGoogle;
use App\Jobs\PublishSiteArticle;
use App\Livewire\ArticlesPage;
use App\Livewire\SitesPage;
use App\Models\Article;
use App\Models\GoogleConnection;
use App\Services\Google\GbpPoster;
use App\Support\Tenancy\CurrentWorkspace;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

/**
 * Google Business Profile: the admin's Google account (Manager of each customer's profile) is
 * connected once; each website picks its profile; every published article becomes one post.
 * Off entirely until the OAuth client is configured.
 */
const GBP_LOCATION = 'accounts/111/locations/222';

beforeEach(fn () => fakeGitHub([]));

function enableGoogle(): void
{
    config()->set('services.google_business.client_id', 'client-ujian.apps.googleusercontent.com');
    config()->set('services.google_business.client_secret', 'rahsia-ujian');
}

function connectGoogle(array $attributes = []): GoogleConnection
{
    return GoogleConnection::query()->create($attributes + ['email' => 'bob@example.com', 'refresh_token' => 'refresh-ujian', 'connected_at' => now()]);
}

/** Fake Google: token refresh, the account's profiles, and local posts ($post = response). */
function fakeGoogle(array $post = ['name' => 'accounts/111/locations/222/localPosts/999'], int $status = 200): void
{
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'akses-ujian', 'expires_in' => 3599]),
        'mybusinessaccountmanagement.googleapis.com/*' => Http::response(['accounts' => [['name' => 'accounts/111', 'accountName' => 'Bob']]]),
        'mybusinessbusinessinformation.googleapis.com/*' => Http::response(['locations' => [
            ['name' => 'locations/222', 'title' => 'Gabion Pantai Timur', 'storefrontAddress' => ['locality' => 'Pasir Mas', 'administrativeArea' => 'Kelantan']],
        ]]),
        'mybusiness.googleapis.com/*' => Http::response($post, $status),
    ]);
}

function googlePosts()
{
    return Http::recorded(fn (Request $r) => str_contains($r->url(), 'mybusiness.googleapis.com/v4/'))->map(fn ($pair) => $pair[0]);
}

it('stays off until the OAuth client is configured', function () {
    actingAsAdmin();
    $site = makeSite(['gbp_location' => GBP_LOCATION]);
    $article = Article::factory()->create(['site_id' => $site->id]);
    Http::fake();

    $this->get(route('google.connect'))->assertNotFound();
    Livewire::test(SitesPage::class)->assertDontSee('Post ke Google Business Profile');

    expect(app(GbpPoster::class)->post($article))->toBeFalse();
    Http::assertNothingSent();
});

it('connects the admin Google account with a state check and keeps the token encrypted', function () {
    enableGoogle();
    actingAsAdmin();

    $redirect = $this->get(route('google.connect'))->assertRedirect()->headers->get('Location');
    parse_str((string) parse_url($redirect, PHP_URL_QUERY), $query);

    expect($redirect)->toStartWith('https://accounts.google.com/o/oauth2/v2/auth')
        ->and($query['scope'])->toContain('https://www.googleapis.com/auth/business.manage')
        ->and($query['access_type'])->toBe('offline');

    $this->get(route('google.callback', ['state' => 'salah', 'code' => 'x']))->assertRedirect(route('sites'));
    expect(GoogleConnection::query()->count())->toBe(0);

    $idToken = 'h.'.rtrim(strtr(base64_encode(json_encode(['email' => 'bob@example.com'])), '+/', '-_'), '=').'.s';
    Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'a', 'refresh_token' => 'refresh-rahsia', 'id_token' => $idToken])]);

    $this->withSession(['google_oauth_state' => 'betul'])
        ->get(route('google.callback', ['state' => 'betul', 'code' => 'kod-google']))
        ->assertRedirect(route('sites'));

    $connection = GoogleConnection::query()->sole();
    expect($connection->email)->toBe('bob@example.com')
        ->and($connection->refresh_token)->toBe('refresh-rahsia')
        ->and(DB::table('google_connections')->value('refresh_token'))->not->toContain('refresh-rahsia')
        ->and($connection->workspace_id)->toBe($this->workspace->id);
});

it('lets the admin pick a profile the Google account manages, and only those', function () {
    enableGoogle();
    connectGoogle();
    fakeGoogle();
    actingAsAdmin();
    $site = makeSite();

    Livewire::test(SitesPage::class)
        ->assertSee('Disambung sebagai bob@example.com')
        ->call('edit', $site->id)
        ->assertSee('Gabion Pantai Timur (Pasir Mas, Kelantan)')
        ->set('form.gbp_location', 'accounts/999/locations/888')
        ->call('save')
        ->assertHasErrors(['form.gbp_location'])
        ->set('form.gbp_location', GBP_LOCATION)
        ->call('save')
        ->assertHasNoErrors();

    expect($site->refresh()->gbp_location)->toBe(GBP_LOCATION)
        ->and($site->gbp_title)->toBe('Gabion Pantai Timur');
});

it('queues one post a while after publishing, so the website has deployed', function () {
    enableGoogle();
    $site = makeSite(['gbp_location' => GBP_LOCATION]);
    fakeVertex(github: test()->github);
    Queue::fake([PostArticleToGoogle::class]);

    PublishSiteArticle::dispatchSync($site->id);

    Queue::assertPushed(PostArticleToGoogle::class, fn ($job) => $job->articleId === Article::query()->published()->sole()->id
        && $job->delay !== null);
});

it('posts the article summary with its image and a Learn more button, without phone numbers or links', function () {
    enableGoogle();
    connectGoogle();
    fakeGoogle();
    $site = makeSite(['gbp_location' => GBP_LOCATION]);
    $article = Article::factory()->create([
        'site_id' => $site->id,
        'image_path' => 'blog/img/gabion.webp',
        'summary' => 'Pilih tembok yang sesuai. Hubungi 012-345 6789 atau lihat https://contoh.my/promo sekarang.',
    ]);

    (new PostArticleToGoogle($article->id))->handle(app(GbpPoster::class), app(CurrentWorkspace::class));

    $request = googlePosts()->sole();
    expect($request->url())->toBe('https://mybusiness.googleapis.com/v4/'.GBP_LOCATION.'/localPosts')
        ->and($request['topicType'])->toBe('STANDARD')
        ->and($request['callToAction'])->toBe(['actionType' => 'LEARN_MORE', 'url' => $article->url()])
        ->and($request['media'][0]['sourceUrl'])->toBe($site->baseUrl().'/blog/img/gabion.webp')
        ->and($request['summary'])->toStartWith($article->title)
        ->not->toContain('345')
        ->not->toContain('https://')
        ->and(mb_strlen($request['summary']))->toBeLessThanOrEqual(1500);

    expect($article->refresh()->gbp_post)->toBe('accounts/111/locations/222/localPosts/999')
        ->and($article->gbp_posted_at)->not->toBeNull();

    // Never twice.
    expect(app(GbpPoster::class)->post($article))->toBeFalse()
        ->and(googlePosts())->toHaveCount(1);
});

it('posts without the image when Google cannot use it', function () {
    enableGoogle();
    connectGoogle();
    $site = makeSite(['gbp_location' => GBP_LOCATION]);
    $article = Article::factory()->create(['site_id' => $site->id, 'image_path' => 'blog/img/belum-deploy.webp']);

    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'a']),
        'mybusiness.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Invalid media.']], 400)
            ->push(['name' => 'accounts/111/locations/222/localPosts/1']),
    ]);

    expect(app(GbpPoster::class)->post($article))->toBeTrue()
        ->and(googlePosts()->last()->data())->not->toHaveKey('media');
});

it('records a Google error on the article without touching the published article, and can retry', function () {
    enableGoogle();
    connectGoogle();
    // First try refused (API access not approved yet), the retry accepted.
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'a']),
        'mybusiness.googleapis.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Quota exceeded for quota metric with limit 0.']], 429)
            ->push(['name' => 'accounts/111/locations/222/localPosts/2']),
    ]);
    actingAsAdmin();
    $site = makeSite(['gbp_location' => GBP_LOCATION]);
    $article = Article::factory()->create(['site_id' => $site->id]);

    expect(app(GbpPoster::class)->post($article))->toBeFalse();

    $article->refresh();
    expect($article->gbp_error)->toContain('belum diluluskan Google')
        ->and($article->status->value)->toBe('published');

    Livewire::test(ArticlesPage::class)
        ->assertSee('Google: Akses Business Profile API belum diluluskan')
        ->call('postToGoogle', $article->id)
        ->assertSee('Dipost ke Google');

    expect($article->refresh()->gbp_post)->not->toBeNull()->and($article->gbp_error)->toBeNull();
});

it('never uses another workspace\'s Google connection', function () {
    enableGoogle();
    [$other] = otherWorkspace();
    GoogleConnection::query()->forceCreate(['workspace_id' => $other->id, 'email' => 'lain@example.com', 'refresh_token' => 'milik-orang-lain']);
    Http::fake();
    $site = makeSite(['gbp_location' => GBP_LOCATION]);
    $article = Article::factory()->create(['site_id' => $site->id]);

    expect(app(GbpPoster::class)->post($article))->toBeFalse()
        ->and($article->refresh()->gbp_error)->toContain('Google belum disambung');
    Http::assertNothingSent();
});
