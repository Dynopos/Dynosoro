<?php

namespace App\Services\Content;

use App\Exceptions\AnalysisRequired;
use App\Exceptions\VertexException;
use App\Models\Article;
use App\Models\Site;
use App\Models\Topic;
use App\Services\Ai\AiGateway;
use App\Services\Ai\PromptRepository;
use Illuminate\Support\Str;

/**
 * Keeps a backlog of general topics per website (spec §5.1). The cheap model plans a batch;
 * code drops anything whose keyword repeats an existing topic or article (rule 8).
 */
class TopicPlanner
{
    public const SCHEMA = [
        'type' => 'OBJECT',
        'properties' => [
            'topics' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'title' => ['type' => 'STRING'],
                        'target_keyword' => ['type' => 'STRING'],
                        'intent' => ['type' => 'STRING', 'enum' => ['panduan', 'perbandingan', 'senarai', 'penerangan', 'musim']],
                        'cluster' => ['type' => 'STRING'],
                    ],
                    'required' => ['title', 'target_keyword', 'intent'],
                ],
            ],
        ],
        'required' => ['topics'],
    ];

    public function __construct(private AiGateway $ai, private PromptRepository $prompts) {}

    public function plannedCount(Site $site): int
    {
        return $site->topics()->where('status', 'planned')->count();
    }

    /**
     * Plan more topics when fewer than `refill_below` are left, or when a target keyword has
     * no topic yet. Returns the number added.
     */
    public function ensureBacklog(Site $site): int
    {
        if (! $site->hasConfirmedAnalysis()) {
            return 0;
        }

        if ($this->plannedCount($site) >= (int) config('dynoseo.articles.refill_below') && $site->pendingTargetKeywords() === []) {
            return 0;
        }

        return $this->plan($site);
    }

    /** @throws AnalysisRequired */
    public function plan(Site $site): int
    {
        if (! $site->hasConfirmedAnalysis()) {
            throw new AnalysisRequired;
        }

        $existing = $this->existingTitles($site);
        $targets = $site->pendingTargetKeywords();
        $prompt = $this->prompts->render('plan-topics', [], [
            'niche' => $site->fieldContext(),
            'audience' => $site->audience ?: '-',
            'area' => $site->area ?: 'Malaysia',
            'banned' => $site->banned_topics ?: '-',
            'existing' => $existing->isEmpty() ? '-' : $existing->take(200)->map(fn ($t) => '- '.$t)->implode("\n"),
            'count' => (int) config('dynoseo.articles.topics_per_plan'),
            'targets' => $targets === [] ? '-' : implode("\n", array_map(fn ($k) => '- '.$k, $targets)),
        ]);

        $response = $this->ai->text(
            purpose: 'plan',
            model: (string) config('dynoseo.ai.model_check'),
            system: $prompt->system,
            user: $prompt->user,
            maxTokens: (int) config('dynoseo.ai.max_tokens_plan'),
            schema: self::SCHEMA,
            thinkingBudget: $this->thinking('check'),
            temperature: 0.9,
            siteId: $site->id,
        );

        $topics = $response->json()['topics'] ?? null;

        if (! is_array($topics)) {
            throw new VertexException('Pelan topik tidak sah (bukan JSON).');
        }

        $added = 0;
        foreach ($topics as $topic) {
            if (is_array($topic) && $this->onField($site, $topic) && $this->add($site, (string) ($topic['title'] ?? ''), (string) ($topic['target_keyword'] ?? ''), $topic['intent'] ?? null, $topic['cluster'] ?? null)) {
                $added++;
            }
        }

        // A target keyword the AI left out still gets a topic (its own words as the title), so
        // planning never repeats just to chase one keyword.
        foreach ($site->pendingTargetKeywords() as $keyword) {
            if (! $this->isBanned($site, $keyword) && $this->add($site, Str::ucfirst($keyword), $keyword, 'penerangan', null, 'target')) {
                $added++;
            }
        }

        $this->targetsFirst($site);

        return $added;
    }

    /** Planned topics for the admin's target keywords are written before the rest. */
    private function targetsFirst(Site $site): void
    {
        $keys = array_map(fn ($k) => Topic::keyFor($k), $site->targetKeywords());

        if ($keys === []) {
            return;
        }

        $first = (int) $site->topics()->where('status', 'planned')->min('position');
        $targets = $site->topics()->where('status', 'planned')->whereIn('keyword_key', $keys)->orderBy('position')->get();

        foreach ($targets->values() as $i => $topic) {
            $topic->forceFill(['position' => $first - $targets->count() + $i])->save();
        }
    }

    /** Add one topic unless it repeats an existing topic/article or touches a banned topic. */
    public function add(Site $site, string $title, string $keyword, ?string $intent = null, ?string $cluster = null, string $source = 'ai'): ?Topic
    {
        $title = Str::limit(Str::squish($title), 180, '');
        $keyword = Str::limit(Str::squish($keyword ?: $title), 180, '');
        $key = Topic::keyFor($keyword);

        if ($title === '' || $key === '' || $this->isDuplicate($site, $title, $key) || $this->isBanned($site, $title.' '.$keyword)) {
            return null;
        }

        return $site->topics()->create([
            'workspace_id' => $site->workspace_id,
            'title' => $title,
            'target_keyword' => $keyword,
            'keyword_key' => $key,
            'intent' => $intent,
            'cluster' => $cluster ? Str::limit($cluster, 180, '') : null,
            'position' => (int) $site->topics()->max('position') + 1,
            'status' => 'planned',
            'source' => $source,
        ]);
    }

    /** After an analysis is confirmed: AI topics planned earlier that are off-field are skipped. */
    public function dropOffField(Site $site): int
    {
        return $site->topics()->where('status', 'planned')->where('source', 'ai')->get()
            ->reject(fn (Topic $t) => $this->onField($site, ['title' => $t->title, 'target_keyword' => $t->target_keyword, 'cluster' => $t->cluster]))
            ->each(fn (Topic $t) => $t->forceFill(['status' => 'skipped'])->save())
            ->count();
    }

    /**
     * Code check on an AI topic: it must sit in one of the confirmed topic clusters and must
     * not touch anything the analysis lists as off-field (e.g. "oksigen" for a skylift company).
     */
    public function onField(Site $site, array $topic): bool
    {
        $text = mb_strtolower(($topic['title'] ?? '').' '.($topic['target_keyword'] ?? ''));

        foreach ($site->avoidList() as $avoid) {
            $avoid = mb_strtolower(trim($avoid));
            if ($avoid !== '' && str_word_count($avoid) <= 4 && str_contains($text, $avoid)) {
                return false;
            }
        }

        // The admin chose this keyword for this website: it needs no cluster match.
        $keywordKey = Topic::keyFor((string) ($topic['target_keyword'] ?? ''));
        if ($keywordKey !== '' && in_array($keywordKey, array_map(fn ($k) => Topic::keyFor($k), $site->targetKeywords()), true)) {
            return true;
        }

        $pillars = array_map(fn ($p) => Topic::keyFor($p), $site->pillarNames());
        if ($pillars === []) {
            return true;
        }

        $cluster = Topic::keyFor((string) ($topic['cluster'] ?? ''));

        return $cluster !== '' && collect($pillars)->contains(
            fn ($p) => $p === $cluster || str_contains($p, $cluster) || str_contains($cluster, $p)
        );
    }

    public function isDuplicate(Site $site, string $title, string $keywordKey): bool
    {
        $titleKey = Topic::keyFor($title);

        if ($site->topics()->where('keyword_key', $keywordKey)->exists()) {
            return true;
        }

        return $site->topics()->pluck('title')
            ->merge(Article::query()->where('site_id', $site->id)->whereNotNull('title')->pluck('title'))
            ->contains(fn ($t) => Topic::keyFor((string) $t) === $titleKey);
    }

    private function isBanned(Site $site, string $text): bool
    {
        $text = mb_strtolower($text);

        foreach ($site->bannedTopicList() as $banned) {
            if (str_contains($text, mb_strtolower($banned))) {
                return true;
            }
        }

        return false;
    }

    private function existingTitles(Site $site)
    {
        return Article::query()->where('site_id', $site->id)->whereNotNull('title')->latest('id')->pluck('title')
            ->merge($site->topics()->latest('id')->pluck('title'))
            ->unique()
            ->values();
    }

    private function thinking(string $purpose): ?int
    {
        $value = config("dynoseo.ai.thinking_{$purpose}");

        return is_numeric($value) ? (int) $value : null;
    }
}
