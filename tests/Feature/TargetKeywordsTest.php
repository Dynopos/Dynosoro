<?php

use App\Livewire\SitesPage;
use App\Services\Content\TopicPlanner;
use Livewire\Livewire;

/**
 * Target keywords: up to five per website, chosen by the admin. Each one gets a topic before
 * the rest of the backlog, but never one the analysis or banned topics rule out (rule 9).
 */
beforeEach(function () {
    fakeGitHub([]);
});

it('asks the planner for the target keywords and writes them first', function () {
    $site = makeSite(['target_keywords' => ['sewa gabion murah kelantan']]);
    $site->topics()->create(['workspace_id' => $site->workspace_id, 'title' => 'Topik lama', 'target_keyword' => 'topik lama', 'keyword_key' => 'lama topik', 'position' => 1, 'status' => 'planned']);

    fakeVertex(plan: [geminiReply(planReply([
        ['title' => 'Batu gabion: saiz dan jenis', 'target_keyword' => 'batu gabion', 'intent' => 'panduan', 'cluster' => 'tembok'],
        // Outside the confirmed clusters, but the admin chose this keyword: kept.
        ['title' => 'Apa perlu tahu sebelum cari gabion di Kelantan', 'target_keyword' => 'sewa gabion murah kelantan', 'intent' => 'panduan', 'cluster' => 'lain'],
    ]))]);

    app(TopicPlanner::class)->plan($site);

    expect(json_encode(vertexRequests('plan')->first()['contents'], JSON_UNESCAPED_UNICODE))->toContain('- sewa gabion murah kelantan')
        ->and($site->topics()->where('status', 'planned')->orderBy('position')->first()->target_keyword)->toBe('sewa gabion murah kelantan')
        ->and($site->pendingTargetKeywords())->toBe([]);
});

it('still gives a target keyword a topic when the AI leaves it out, so planning does not repeat', function () {
    $site = makeSite(['target_keywords' => ['tembok penahan tanah murah']]);
    fakeVertex();

    app(TopicPlanner::class)->plan($site);

    $topic = $site->topics()->where('target_keyword', 'tembok penahan tanah murah')->sole();
    expect($topic->source)->toBe('target')
        ->and($topic->title)->toBe('Tembok penahan tanah murah')
        ->and($site->pendingTargetKeywords())->toBe([]);
});

it('plans for a new target keyword even when the backlog is full', function () {
    $site = makeSite(['target_keywords' => ['gabion vs batu bata']]);
    foreach (range(1, 6) as $n) {
        $site->topics()->create(['workspace_id' => $site->workspace_id, 'title' => "Topik {$n}", 'target_keyword' => "topik {$n}", 'keyword_key' => "{$n} topik", 'position' => $n, 'status' => 'planned']);
    }
    fakeVertex();

    expect(app(TopicPlanner::class)->ensureBacklog($site))->toBeGreaterThan(0)
        ->and(vertexRequests('plan'))->toHaveCount(1)
        ->and(app(TopicPlanner::class)->ensureBacklog($site->refresh()))->toBe(0);
});

it('saves up to five keywords from the Website form and plans them at once', function () {
    $site = makeSite();
    fakeVertex();
    actingAsAdmin();

    Livewire::test(SitesPage::class)
        ->call('edit', $site->id)
        ->set('form.target_keywords', "gabion kelantan\ntembok penahan tanah")
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('Topik untuk keyword sasaran dah dirancang');

    expect($site->refresh()->targetKeywords())->toBe(['gabion kelantan', 'tembok penahan tanah'])
        ->and($site->pendingTargetKeywords())->toBe([]);
});

it('refuses more than five keywords, and keywords the website must not write about', function () {
    $site = makeSite(['banned_topics' => 'harga']);
    actingAsAdmin();

    Livewire::test(SitesPage::class)
        ->call('edit', $site->id)
        ->set('form.target_keywords', "a1\nb2\nc3\nd4\ne5\nf6")
        ->call('save')
        ->assertHasErrors(['form.target_keywords'])
        ->set('form.target_keywords', 'tangki oksigen murah')
        ->call('save')
        ->assertHasErrors(['form.target_keywords'])
        ->set('form.target_keywords', 'harga gabion')
        ->call('save')
        ->assertHasErrors(['form.target_keywords']);

    expect($site->refresh()->target_keywords)->toBeNull();
});
