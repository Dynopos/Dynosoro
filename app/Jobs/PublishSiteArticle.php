<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\Sites\SiteRunner;
use App\Support\Tenancy\CurrentWorkspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Write and publish the next article for one website. One run per website at a time. */
class PublishSiteArticle implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // SiteRunner handles failures itself; a crashed job is not retried (no loops).
    public int $tries = 1;

    public int $timeout = 900;

    public int $uniqueFor = 1800;

    public function __construct(public int $siteId, public bool $manual = false) {}

    public function uniqueId(): string
    {
        return (string) $this->siteId;
    }

    public function handle(SiteRunner $runner, CurrentWorkspace $current): void
    {
        $site = Site::query()->withoutGlobalScope('workspace')->find($this->siteId);

        if ($site === null) {
            return;
        }

        $current->runAs($site->workspace_id, fn () => $runner->run($site, $this->manual));
    }
}
