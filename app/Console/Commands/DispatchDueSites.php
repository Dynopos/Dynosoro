<?php

namespace App\Console\Commands;

use App\Enums\SiteStatus;
use App\Jobs\PublishSiteArticle;
use App\Models\Site;
use App\Support\AdminAlert;
use Carbon\Carbon;
use Illuminate\Console\Command;

/** Every 15 minutes: queue a run for each active website whose next publish time has come. */
class DispatchDueSites extends Command
{
    protected $signature = 'dynoseo:dispatch-due';

    protected $description = 'Hantar website yang sampai masa terbit ke giliran';

    public function handle(AdminAlert $alert): int
    {
        $due = Site::query()->withoutGlobalScope('workspace')
            ->where('status', SiteStatus::Active)
            ->whereNotNull('next_publish_at')
            ->where('next_publish_at', '<=', now())
            ->pluck('id');

        foreach ($due as $id) {
            PublishSiteArticle::dispatch((int) $id);
        }

        $this->warnTokenExpiry($alert);

        $this->info('Website dihantar: '.$due->count());

        return self::SUCCESS;
    }

    private function warnTokenExpiry(AdminAlert $alert): void
    {
        $expires = config('dynoseo.github.token_expires_at');

        if (! $expires) {
            return;
        }

        $date = Carbon::parse($expires);

        if ($date->lte(now()->addDays((int) config('dynoseo.github.warn_days')))) {
            $alert->send('github-token', 'Token GitHub hampir tamat', 'GITHUB_TOKEN tamat pada '.$date->format('d/m/Y').'. Buat token baru (Contents: read & write, repo website sahaja), kemas kini .env di Forge, dan GITHUB_TOKEN_EXPIRES_AT.');
        }
    }
}
