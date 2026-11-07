<?php

namespace App\Jobs;

use App\Models\Site;
use App\Services\Bing\BingSetup;
use App\Support\Tenancy\CurrentWorkspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Add, verify and submit the sitemap of one website in Bing Webmaster Tools. Never retried. */
class SetUpBing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public int $siteId) {}

    public function handle(BingSetup $setup, CurrentWorkspace $current): void
    {
        $site = Site::query()->withoutGlobalScope('workspace')->find($this->siteId);

        if ($site === null) {
            return;
        }

        $current->runAs($site->workspace_id, fn () => $setup->run($site));
    }
}
