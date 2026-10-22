<?php

namespace App\Services\Sites;

use App\Enums\ArticleStatus;
use App\Enums\SiteStatus;
use App\Exceptions\AnalysisRequired;
use App\Exceptions\BudgetExceeded;
use App\Exceptions\PricesNotConfigured;
use App\Exceptions\VertexException;
use App\Models\Article;
use App\Models\Site;
use App\Services\Content\ArticleProducer;
use App\Services\Publishing\SitePublisher;
use App\Support\AdminAlert;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * One scheduled run for one website (spec §4): write the next article and publish it, then
 * schedule the next run. Failures never loop: a failing website is retried a few hours
 * later and stops (status Ralat) after `max_failures`. A billing error from Vertex (GCP
 * credit used up) pauses every website at once (spec §8.5).
 */
class SiteRunner
{
    public function __construct(
        private ArticleProducer $producer,
        private SitePublisher $publisher,
        private AdminAlert $alert,
    ) {}

    /** True when the last run() skipped because another run for the same website was going. */
    public bool $busy = false;

    /**
     * Returns the published article, or null when nothing was published.
     *
     * One run per website at a time. Without this lock, "Terbit sekarang", the scheduler and
     * `dynoseo:publish` could run together: both see no topics, both plan the same topic, and
     * the same article is written (and paid for) twice.
     */
    public function run(Site $site, bool $manual = false): ?Article
    {
        $lock = Cache::lock('site-run:'.$site->id, (int) config('dynoseo.articles.run_lock_seconds', 1800));
        $this->busy = ! $lock->get();

        if ($this->busy) {
            return null;
        }

        try {
            return $this->runLocked($site->refresh(), $manual);
        } finally {
            $lock->release();
        }
    }

    private function runLocked(Site $site, bool $manual): ?Article
    {
        if ($site->status !== SiteStatus::Active && ! $manual) {
            return null;
        }

        if (! $site->isPaid()) {
            $this->postpone($site, 'Langganan tamat (bayar sehingga '.$site->paid_until->format('d/m/Y').'). Artikel ditangguh.', now()->addDay());

            return null;
        }

        if ($this->publishedToday($site) >= (int) config('dynoseo.articles.max_per_site_per_day')) {
            $this->postpone($site, 'Had artikel sehari untuk website ini dah dicapai.', $this->nextSlot($site, 1));

            return null;
        }

        // No confirmed business analysis: publish nothing, not even topics or drafts made before
        // analyses existed (they may be off-field). Waits for the admin; not a failure.
        if (! $site->hasConfirmedAnalysis()) {
            $this->postpone($site, AnalysisRequired::MESSAGE, now()->addDay());

            return null;
        }

        try {
            // An article written earlier whose publish failed is published first (no new AI cost).
            $article = $site->articles()->where('status', ArticleStatus::Ready)->oldest('id')->first()
                ?? $this->producer->produce($site);

            $this->publisher->publish($article);
        } catch (BudgetExceeded $e) {
            $this->abandonDrafts($site, $e->getMessage());
            $this->postpone($site, $e->getMessage(), now()->addDay()->startOfDay()->addHours(8));
            $this->alert->send('budget', 'Had AI dicapai', $e->getMessage());

            return null;
        } catch (VertexException $e) {
            if ($e->billing) {
                $this->abandonDrafts($site, $e->getMessage());
                $this->pauseAllForAi($e->getMessage());

                return null;
            }

            $this->fail($site, $e);

            return null;
        } catch (PricesNotConfigured $e) {
            $this->stop($site, $e->getMessage());

            return null;
        } catch (Throwable $e) {
            $this->fail($site, $e);

            return null;
        }

        $site->forceFill([
            'failure_count' => 0,
            'status_note' => null,
            'next_publish_at' => $this->nextSlot($site),
        ])->save();

        return $article;
    }

    /** Next publish time: `frequency_days` from today at a random time in the window. */
    public function nextSlot(Site $site, ?int $days = null): CarbonInterface
    {
        [$from, $to] = config('dynoseo.articles.publish_window');
        $day = now()->startOfDay()->addDays(max(1, $days ?? $site->frequency_days));

        return $day->addMinutes(random_int($from * 60, $to * 60 - 1));
    }

    /** Vertex AI is unavailable for everyone (GCP credit used up or billing disabled). */
    public function pauseAllForAi(string $reason): void
    {
        $note = 'Vertex AI berhenti (kredit GCP habis atau billing dimatikan). Sambung semula di halaman Website selepas diselesaikan.';

        Site::query()->withoutGlobalScope('workspace')
            ->where('status', SiteStatus::Active)
            ->update(['status' => SiteStatus::AiPaused, 'status_note' => $note]);

        $this->alert->send('vertex-billing', 'Vertex AI berhenti: semua website ditangguh', $note."\n\nRalat: ".$reason, 72);
    }

    private function fail(Site $site, Throwable $e): void
    {
        $this->abandonDrafts($site, $e->getMessage());
        $failures = $site->failure_count + 1;
        $message = mb_substr($e->getMessage(), 0, 500);

        if ($failures >= (int) config('dynoseo.articles.max_failures')) {
            $site->forceFill(['failure_count' => $failures, 'status' => SiteStatus::Error, 'status_note' => $message])->save();
            $this->alert->send('site-error:'.$site->id, "Website {$site->name} berhenti (Ralat)", "Gagal {$failures} kali berturut-turut.\n\nRalat terakhir: {$message}");

            return;
        }

        $site->forceFill([
            'failure_count' => $failures,
            'status_note' => "Gagal ({$failures}/".config('dynoseo.articles.max_failures')."): {$message}",
            'next_publish_at' => now()->addHours((int) config('dynoseo.articles.retry_after_hours')),
        ])->save();
    }

    /** Drafts left half-written by an error are marked failed; the topic stays planned. */
    private function abandonDrafts(Site $site, string $message): void
    {
        $site->articles()->where('status', ArticleStatus::Writing)
            ->update(['status' => ArticleStatus::Failed, 'error' => mb_substr($message, 0, 500)]);
    }

    private function stop(Site $site, string $message): void
    {
        $this->abandonDrafts($site, $message);
        $site->forceFill(['status' => SiteStatus::Error, 'status_note' => $message])->save();
        $this->alert->send('config', 'Tetapan AI belum lengkap', $message);
    }

    private function postpone(Site $site, string $note, CarbonInterface $until): void
    {
        $site->forceFill(['status_note' => $note, 'next_publish_at' => $until])->save();
    }

    private function publishedToday(Site $site): int
    {
        return $site->articles()->where('status', ArticleStatus::Published)
            ->where('published_at', '>=', now()->startOfDay())
            ->count();
    }
}
