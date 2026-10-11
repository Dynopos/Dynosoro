<?php

use Illuminate\Support\Facades\Schedule;

// Every 15 minutes: queue a run for each website whose publish time has come (spec §4).
Schedule::command('dynoseo:dispatch-due')->everyFifteenMinutes()->name('dispatch-due-sites')->withoutOverlapping();

// Safety net for the queue worker: every minute, work the queue until it is empty. If the
// Forge worker is down, articles are still written. If it is up, both share the queue
// safely: a reserved job is not taken again before retry_after.
if (config('dynoseo.queue_via_scheduler')) {
    Schedule::command('queue:work database --queue=default --stop-when-empty --max-time=50 --timeout=900 --tries=1')
        ->everyMinute()
        ->name('queue-safety-net')
        ->withoutOverlapping(20)
        ->runInBackground();
}
