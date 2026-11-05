<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\Google\GbpPoster;
use App\Support\Tenancy\CurrentWorkspace;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Post one published article to its website's Google Business Profile. Never retried. */
class PostArticleToGoogle implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public int $articleId) {}

    public function handle(GbpPoster $poster, CurrentWorkspace $current): void
    {
        $article = Article::query()->withoutGlobalScope('workspace')->find($this->articleId);

        if ($article === null) {
            return;
        }

        $current->runAs($article->workspace_id, fn () => $poster->post($article));
    }
}
