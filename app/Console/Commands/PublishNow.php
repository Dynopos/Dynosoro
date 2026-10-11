<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\Sites\SiteRunner;
use App\Support\Tenancy\CurrentWorkspace;
use Illuminate\Console\Command;

class PublishNow extends Command
{
    protected $signature = 'dynoseo:publish {site : ID website}';

    protected $description = 'Tulis dan terbit satu artikel sekarang untuk satu website';

    public function handle(SiteRunner $runner, CurrentWorkspace $current): int
    {
        $site = Site::query()->withoutGlobalScope('workspace')->findOrFail((int) $this->argument('site'));
        $article = $current->runAs($site->workspace_id, fn () => $runner->run($site, true));

        if ($article === null) {
            $this->error('Tiada artikel diterbitkan: '.($site->fresh()->status_note ?? '-'));

            return self::FAILURE;
        }

        $this->info('Terbit: '.$article->url());

        return self::SUCCESS;
    }
}
