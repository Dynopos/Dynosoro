<?php

namespace App\Services\Costs;

use App\Enums\ArticleStatus;
use App\Models\AiUsage;
use App\Models\Article;
use App\Models\Site;
use App\Services\Ai\AiBudget;

/** Numbers for the Kos screen (spec §3.5). */
class CostReport
{
    public function __construct(private AiBudget $budget) {}

    public function month(): array
    {
        $start = now()->startOfMonth();
        $usage = AiUsage::query()->where('created_at', '>=', $start);
        $articles = Article::query()->where('status', ArticleStatus::Published)->where('published_at', '>=', $start)->count();
        $total = (float) (clone $usage)->sum('cost_estimate');

        return [
            'total' => $total,
            'calls' => (clone $usage)->count(),
            'images' => (int) (clone $usage)->sum('images'),
            'input_tokens' => (int) (clone $usage)->sum('input_tokens'),
            'output_tokens' => (int) (clone $usage)->sum('output_tokens'),
            'articles' => $articles,
            'per_article' => $articles > 0 ? $total / $articles : null,
            'by_purpose' => (clone $usage)->selectRaw('purpose, count(*) as calls, sum(cost_estimate) as cost')->groupBy('purpose')->get(),
            'by_site' => Site::query()->get(['id', 'name'])->map(fn (Site $s) => [
                'name' => $s->name,
                'cost' => (float) AiUsage::query()->where('site_id', $s->id)->where('created_at', '>=', $start)->sum('cost_estimate'),
                'articles' => Article::query()->where('site_id', $s->id)->where('status', ArticleStatus::Published)->where('published_at', '>=', $start)->count(),
            ])->sortByDesc('cost')->values(),
            'limit' => $this->budget->limit(),
            'platform_spent' => $this->budget->spentThisMonth(),
            'calls_today' => $this->budget->callsToday(),
            'daily_limit' => $this->budget->dailyCallLimit(),
        ];
    }
}
