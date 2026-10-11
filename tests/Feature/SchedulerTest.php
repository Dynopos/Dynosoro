<?php

use App\Jobs\PublishSiteArticle;
use App\Models\Site;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

it('queues only active websites that are due, across workspaces', function () {
    Queue::fake();
    [$other] = otherWorkspace();
    $due = Site::factory()->create(['next_publish_at' => now()->subMinute()]);
    $theirs = Site::factory()->create(['workspace_id' => $other->id, 'next_publish_at' => now()->subHour()]);
    Site::factory()->create(['next_publish_at' => now()->addHour()]);
    Site::factory()->create(['status' => 'paused', 'next_publish_at' => now()->subHour()]);
    Site::factory()->create(['status' => 'error', 'next_publish_at' => now()->subHour()]);

    $this->artisan('dynoseo:dispatch-due')->assertSuccessful();

    Queue::assertPushed(PublishSiteArticle::class, 2);
    Queue::assertPushed(PublishSiteArticle::class, fn ($job) => $job->siteId === $due->id);
    Queue::assertPushed(PublishSiteArticle::class, fn ($job) => $job->siteId === $theirs->id);
});

it('warns Bob when the GitHub token is about to expire', function () {
    Queue::fake();
    Mail::fake();
    config(['dynoseo.github.token_expires_at' => now()->addDays(5)->toDateString()]);

    $this->artisan('dynoseo:dispatch-due')->assertSuccessful();
    $this->artisan('dynoseo:dispatch-due')->assertSuccessful();

    Mail::assertSentCount(1);
});

it('schedules the dispatcher every fifteen minutes', function () {
    $events = collect(app(Schedule::class)->events());

    expect($events->first(fn ($e) => str_contains($e->command, 'dynoseo:dispatch-due'))?->expression)->toBe('*/15 * * * *');
});
