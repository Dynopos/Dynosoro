<?php

namespace App\Services\Content;

use App\Enums\ArticleStatus;
use App\Exceptions\VertexException;
use App\Models\Article;
use App\Models\Site;
use App\Services\Ai\AiGateway;
use App\Services\Ai\PromptRepository;

/** Second check with the cheap model (spec §5.3). Returns issues; empty means pass. */
class ArticleChecker
{
    public const SCHEMA = [
        'type' => 'OBJECT',
        'properties' => [
            'pass' => ['type' => 'BOOLEAN'],
            'issues' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
        ],
        'required' => ['pass', 'issues'],
    ];

    public function __construct(private AiGateway $ai, private PromptRepository $prompts) {}

    public function review(Site $site, Article $article, array $draft): array
    {
        $existing = Article::query()->where('site_id', $site->id)
            ->where('status', ArticleStatus::Published)
            ->whereKeyNot($article->id)
            ->latest('published_at')->take(50)->pluck('title')
            ->map(fn ($t) => '- '.$t)->implode("\n");

        $prompt = $this->prompts->render('check', [], [
            'niche' => $site->niche,
            'existing' => $existing ?: '-',
            'title' => $draft['title'],
            'meta' => $draft['meta_description'],
            'body' => ArticleValidator::plainText($draft['body_html']),
            'faq' => collect($draft['faq'])->map(fn ($f) => "S: {$f['q']}\nJ: {$f['a']}")->implode("\n") ?: '-',
            'bridge' => $draft['cta_bridge'] ? 'Ayat penyambung ke bisnes: '.$draft['cta_bridge'] : '',
        ]);

        $response = $this->ai->text(
            purpose: 'check',
            model: (string) config('dynoseo.ai.model_check'),
            system: $prompt->system,
            user: $prompt->user,
            maxTokens: (int) config('dynoseo.ai.max_tokens_check'),
            schema: self::SCHEMA,
            thinkingBudget: is_numeric(config('dynoseo.ai.thinking_check')) ? (int) config('dynoseo.ai.thinking_check') : null,
            temperature: 0.1,
            siteId: $site->id,
            articleId: $article->id,
        );

        $data = $response->json();

        if ($data === null || ! array_key_exists('pass', $data)) {
            throw new VertexException('Semakan AI tidak sah (bukan JSON).');
        }

        $issues = collect(is_array($data['issues'] ?? null) ? $data['issues'] : [])
            ->map(fn ($i) => trim((string) $i))->filter()->values()->all();

        if ($data['pass'] === true) {
            return [];
        }

        return $issues !== [] ? $issues : ['Semakan AI menolak artikel tanpa sebab yang jelas.'];
    }
}
