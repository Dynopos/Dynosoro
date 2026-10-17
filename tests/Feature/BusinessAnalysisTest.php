<?php

use App\Enums\SiteStatus;
use App\Exceptions\AnalysisRequired;
use App\Livewire\SitesPage;
use App\Models\Site;
use App\Models\Topic;
use App\Services\Content\SiteAnalyst;
use App\Services\Content\TopicPlanner;
use App\Services\Sites\SiteRunner;
use App\Support\Tenancy\CurrentWorkspace;
use Livewire\Livewire;

beforeEach(fn () => fakeGitHub([]));

/** A skylift rental website: the case where "skylift" turned into posts about oxygen. */
function skyliftSite(array $attributes = []): Site
{
    return makeSite(array_merge([
        'name' => 'Skylift Kelantan',
        'niche' => 'skylift',
        'audience' => null,
    ], $attributes), [
        'index.html' => '<html><head><title>Sewa Skylift Kelantan</title><meta name="description" content="Sewa lori skylift dengan operator untuk kerja tinggi."></head>'
            .'<body><script>var x = 1;</script><h1>Sewa Skylift</h1><p>Pasang papan tanda, lampu jalan, cat bangunan.</p></body></html>',
    ]);
}

it('analyses the business from the website itself before anything is planned', function () {
    $site = skyliftSite(['analysis' => null, 'analysis_confirmed_at' => null]);
    fakeVertex(github: test()->github);

    app(SiteAnalyst::class)->analyse($site, 'Skylift ialah lori platform angkat, bukan oksigen.');
    $site->refresh();

    expect($site->analysis['field'])->toBe('sewa skylift dan kerja di tempat tinggi')
        ->and(array_column($site->analysis['pillars'], 'name'))->toContain('pilih skylift')
        ->and($site->analysis['avoid'])->toContain('oksigen')
        ->and($site->analysis['version'])->toBe('analyse-v1')
        ->and($site->hasConfirmedAnalysis())->toBeFalse();

    $user = json_encode(vertexRequests('analyse')->first()['contents'], JSON_UNESCAPED_UNICODE);
    expect($user)->toContain('Sewa Skylift Kelantan')
        ->toContain('Pasang papan tanda, lampu jalan')
        ->toContain('PEMBETULAN DARI PEMILIK')
        ->toContain('bukan oksigen')
        ->not->toContain('var x = 1');
});

it('plans no topics until the analysis is confirmed', function () {
    $site = skyliftSite();
    $site->forceFill(['analysis_confirmed_at' => null])->save();
    fakeVertex(github: test()->github);

    expect(app(TopicPlanner::class)->ensureBacklog($site))->toBe(0)
        ->and(fn () => app(TopicPlanner::class)->plan($site))->toThrow(AnalysisRequired::class)
        ->and(vertexCalls())->toBe(0);
});

it('publishes nothing for a website without a confirmed analysis, not even old topics, without counting a failure', function () {
    $site = skyliftSite(['analysis' => null, 'analysis_confirmed_at' => null]);
    app(TopicPlanner::class)->add($site, 'Kepentingan tangki oksigen', 'tangki oksigen');
    fakeVertex(github: test()->github);

    expect(app(SiteRunner::class)->run($site))->toBeNull();

    $site->refresh();
    expect($site->status_note)->toBe(AnalysisRequired::MESSAGE)
        ->and($site->failure_count)->toBe(0)
        ->and($site->status)->toBe(SiteStatus::Active)
        ->and(vertexCalls())->toBe(0);
});

it('drops planned topics outside the confirmed clusters or on avoided subjects', function () {
    $site = skyliftSite(['analysis' => analysisReply() + ['version' => 'analyse-v1'], 'analysis_confirmed_at' => now()]);
    fakeVertex(github: test()->github, plan: [geminiReply(planReply([
        ['title' => 'Cara pilih saiz skylift ikut ketinggian kerja', 'target_keyword' => 'saiz skylift', 'intent' => 'panduan', 'cluster' => 'pilih skylift'],
        ['title' => 'Kepentingan tangki oksigen untuk kesihatan', 'target_keyword' => 'tangki oksigen', 'intent' => 'penerangan', 'cluster' => 'pilih skylift'],
        ['title' => 'Tips melancong ke Cameron Highlands', 'target_keyword' => 'cameron highlands', 'intent' => 'senarai', 'cluster' => 'pelancongan'],
        ['title' => 'Senarai semak keselamatan sebelum naik bakul skylift', 'target_keyword' => 'keselamatan skylift', 'intent' => 'senarai', 'cluster' => 'Keselamatan Kerja Tinggi'],
    ]))]);

    expect(app(TopicPlanner::class)->plan($site))->toBe(2)
        ->and(Topic::query()->pluck('title')->all())->toBe([
            'Cara pilih saiz skylift ikut ketinggian kerja',
            'Senarai semak keselamatan sebelum naik bakul skylift',
        ]);
});

it('gives the planner, writer and checker the confirmed field, clusters and avoid list', function () {
    $site = skyliftSite(['analysis' => analysisReply() + ['version' => 'analyse-v1'], 'analysis_confirmed_at' => now()]);
    fakeVertex(github: test()->github, plan: [geminiReply(planReply([
        ['title' => 'Cara pilih saiz skylift ikut ketinggian kerja', 'target_keyword' => 'saiz skylift', 'intent' => 'panduan', 'cluster' => 'pilih skylift'],
    ]))]);

    app(SiteRunner::class)->run($site);

    foreach (['plan', 'write', 'check'] as $purpose) {
        $text = json_encode(vertexRequests($purpose)->first()['contents'], JSON_UNESCAPED_UNICODE);
        expect($text)->toContain('Bidang: sewa skylift dan kerja di tempat tinggi')
            ->toContain('Kluster topik yang dibenarkan: keselamatan kerja tinggi')
            ->toContain('JANGAN tulis tentang (tiada kaitan dengan bisnes ini): oksigen; tangki oksigen; penerbangan');
    }
});

it('lets the admin correct the analysis, run it again and confirm it on the Website screen', function () {
    $site = skyliftSite(['analysis' => analysisReply(['confidence' => 'rendah', 'question' => 'Skylift ini untuk sewa atau jual?']) + ['version' => 'analyse-v1'], 'analysis_confirmed_at' => null]);
    fakeVertex(github: test()->github);
    actingAsAdmin();

    $page = Livewire::test(SitesPage::class)
        ->assertSee('Belum disahkan')
        ->assertSee('AI kurang pasti:')
        ->assertSee('Skylift ini untuk sewa atau jual?')
        ->assertSee('Tak akan ditulis:')
        ->set("corrections.{$site->id}", 'Sewa sahaja, dengan operator.')
        ->call('analyse', $site->id)
        ->assertSee('Analisis bisnes siap');

    expect(json_encode(vertexRequests('analyse')->first()['contents'], JSON_UNESCAPED_UNICODE))->toContain('Sewa sahaja, dengan operator.');

    $page->call('confirmAnalysis', $site->id)->assertSee('disahkan');
    expect($site->refresh()->hasConfirmedAnalysis())->toBeTrue();
});

it('needs a new confirmation after every new analysis', function () {
    $site = skyliftSite();
    fakeVertex(github: test()->github);

    expect($site->hasConfirmedAnalysis())->toBeTrue();
    app(SiteAnalyst::class)->analyse($site);
    expect($site->refresh()->hasConfirmedAnalysis())->toBeFalse();
});

it('cannot analyse or confirm another customer’s website', function () {
    [$other] = otherWorkspace();
    $theirs = app(CurrentWorkspace::class)->runAs($other, fn () => Site::factory()->unanalysed()->create(['workspace_id' => $other->id]));
    fakeVertex();
    actingAsAdmin();

    expect(fn () => Livewire::test(SitesPage::class)->call('analyse', $theirs->id))->toThrow(Exception::class)
        ->and(fn () => Livewire::test(SitesPage::class)->call('confirmAnalysis', $theirs->id))->toThrow(Exception::class)
        ->and(vertexCalls())->toBe(0);
});

it('skips old AI topics that are off-field once the analysis is confirmed', function () {
    $site = skyliftSite(['analysis' => analysisReply() + ['version' => 'analyse-v1'], 'analysis_confirmed_at' => null]);
    $planner = app(TopicPlanner::class);
    $planner->add($site, 'Kepentingan tangki oksigen untuk pesakit', 'tangki oksigen', 'penerangan', 'kesihatan');
    $planner->add($site, 'Cara pilih saiz skylift', 'saiz skylift', 'panduan', 'pilih skylift');
    $planner->add($site, 'Topik manual pemilik', 'topik manual', null, null, 'manual');
    fakeVertex(github: test()->github);
    actingAsAdmin();

    Livewire::test(SitesPage::class)->call('confirmAnalysis', $site->id)->assertSee('1 topik lama yang tersasar bidang dibuang');

    expect(Topic::query()->where('status', 'planned')->pluck('title')->sort()->values()->all())
        ->toBe(['Cara pilih saiz skylift', 'Topik manual pemilik']);
});
