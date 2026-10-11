<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\Publishing\SitePublisher;
use App\Support\Tenancy\CurrentWorkspace;
use Illuminate\Console\Command;

class RebuildSite extends Command
{
    protected $signature = 'dynoseo:rebuild {site : ID website}';

    protected $description = 'Bina semula semua halaman blog website dari pangkalan data (selepas ubah template atau hook)';

    public function handle(SitePublisher $publisher, CurrentWorkspace $current): int
    {
        $site = Site::query()->withoutGlobalScope('workspace')->findOrFail((int) $this->argument('site'));
        $sha = $current->runAs($site->workspace_id, fn () => $publisher->rebuild($site));

        $this->info("Siap: commit {$sha}");

        return self::SUCCESS;
    }
}
