<?php

use App\Enums\ArticleStatus;
use App\Enums\SiteStatus;
use App\Jobs\PublishSiteArticle;
use App\Models\Article;
use App\Models\Site;
use Illuminate\Support\Facades\Mail;

it('pauses every website and alerts Bob once when the GCP credit runs out', function () {
    Mail::fake();
    [$other] = otherWorkspace();
    $github = fakeGitHub([]);
    $site = makeSite();
    $second = makeSite();
    $theirs = Site::factory()->create(['workspace_id' => $other->id]);
    $billing = ['__status' => 403, 'body' => ['error' => ['code' => 403, 'status' => 'PERMISSION_DENIED', 'message' => 'This API method requires billing to be enabled.']]];
    fakeVertex(plan: [$billing], github: $github);

    PublishSiteArticle::dispatchSync($site->id);
    PublishSiteArticle::dispatchSync($second->id);

    expect(Site::query()->withoutGlobalScope('workspace')->pluck('status')->unique()->values()->all())->toBe([SiteStatus::AiPaused])
        ->and($theirs->fresh()->status)->toBe(SiteStatus::AiPaused)
        ->and(vertexCalls())->toBe(1)
        ->and($github->commits)->toBe([]);

    Mail::assertSentCount(1);
});

it('stops a website after three failures in a row, retrying a few hours apart', function () {
    Mail::fake();
    $github = fakeGitHub([]);
    $site = makeSite();
    fakeVertex(github: $github);
    $github->failCommits = 100;

    PublishSiteArticle::dispatchSync($site->id);
    $site->refresh();
    expect($site->status)->toBe(SiteStatus::Active)
        ->and($site->failure_count)->toBe(1)
        ->and($site->next_publish_at->diffInHours(now(), true))->toBeGreaterThan(2.9);

    PublishSiteArticle::dispatchSync($site->id);
    PublishSiteArticle::dispatchSync($site->id);

    expect($site->fresh()->status)->toBe(SiteStatus::Error)
        ->and($site->fresh()->status_note)->toContain('GitHub gagal (500)');
    Mail::assertSentCount(1);
});

it('publishes an article that is already written before paying for a new one', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    fakeVertex(github: $github);
    $github->failCommits = 1;

    PublishSiteArticle::dispatchSync($site->id);
    $article = Article::query()->sole();
    expect($article->status)->toBe(ArticleStatus::Ready);
    $calls = vertexCalls();

    PublishSiteArticle::dispatchSync($site->id);

    expect($article->fresh()->status)->toBe(ArticleStatus::Published)
        ->and(vertexCalls())->toBe($calls)
        ->and(Article::query()->count())->toBe(1);
});

it('does not write for websites whose subscription lapsed beyond the grace period', function () {
    $github = fakeGitHub([]);
    $site = makeSite(['paid_until' => now()->subDays(10)->toDateString()]);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    expect(vertexCalls())->toBe(0)->and($site->fresh()->status_note)->toContain('Langganan tamat');
});

it('still writes during the grace period', function () {
    $github = fakeGitHub([]);
    $site = makeSite(['paid_until' => now()->subDays(3)->toDateString()]);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    expect(Article::query()->published()->count())->toBe(1);
});

it('publishes at most one article per website per day', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    Article::factory()->create(['site_id' => $site->id, 'published_at' => now()]);
    fakeVertex(github: $github);

    PublishSiteArticle::dispatchSync($site->id, true);

    expect(vertexCalls())->toBe(0)->and($site->fresh()->status_note)->toContain('Had artikel sehari');
});

it('marks a half-written draft as failed when Vertex errors, keeping the topic for later', function () {
    $github = fakeGitHub([]);
    $site = makeSite();
    fakeVertex(write: [['__status' => 500, 'body' => ['error' => ['message' => 'Internal error']]]], github: $github);

    PublishSiteArticle::dispatchSync($site->id);

    $article = Article::query()->sole();
    expect($article->status)->toBe(ArticleStatus::Failed)
        ->and($article->topic->status)->toBe('planned')
        ->and($site->fresh()->failure_count)->toBe(1);
});
