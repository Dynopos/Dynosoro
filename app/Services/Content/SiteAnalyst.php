<?php

namespace App\Services\Content;

use App\Exceptions\VertexException;
use App\Models\Site;
use App\Services\Ai\AiGateway;
use App\Services\Ai\PromptRepository;
use App\Services\Publishing\GitHubClient;
use Throwable;

/**
 * Business analysis before any topic is planned: what the business really does, its
 * customers, what they search for, the topic clusters to write about and the topics to stay
 * away from. Read from the website's own home page when possible. The admin confirms the
 * analysis (or corrects it and runs it again); TopicPlanner only plans from a confirmed one.
 */
class SiteAnalyst
{
    public const SCHEMA = [
        'type' => 'OBJECT',
        'properties' => [
            'field' => ['type' => 'STRING'],
            'summary' => ['type' => 'STRING'],
            'services' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'customers' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'searches' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'pillars' => ['type' => 'ARRAY', 'items' => [
                'type' => 'OBJECT',
                'properties' => ['name' => ['type' => 'STRING'], 'about' => ['type' => 'STRING']],
                'required' => ['name', 'about'],
            ]],
            'avoid' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'confidence' => ['type' => 'STRING', 'enum' => ['tinggi', 'sederhana', 'rendah']],
            'question' => ['type' => 'STRING'],
        ],
        'required' => ['field', 'summary', 'services', 'customers', 'searches', 'pillars', 'avoid', 'confidence', 'question'],
    ];

    /** Characters of website text sent to the model. */
    private const WEBSITE_CHARS = 4000;

    public function __construct(
        private AiGateway $ai,
        private PromptRepository $prompts,
        private GitHubClient $github,
    ) {}

    /**
     * Analyse (again). A new analysis always needs a new confirmation.
     *
     * @throws VertexException
     */
    public function analyse(Site $site, ?string $correction = null): Site
    {
        $correction = trim((string) $correction);

        $prompt = $this->prompts->render('analyse-site', [], [
            'name' => $site->name,
            'niche' => $site->niche,
            'audience' => $site->audience ?: '-',
            'area' => $site->area ?: 'Malaysia',
            'domain' => $site->domain,
            'website' => $this->websiteText($site) ?: '(tidak dapat dibaca)',
            'correction' => $correction === '' ? '' : "PEMBETULAN DARI PEMILIK (paling penting, ikut ini):\n".mb_substr($correction, 0, 1000),
        ]);

        $response = $this->ai->text(
            purpose: 'analyse',
            model: (string) config('dynoseo.ai.model_write'),
            system: $prompt->system,
            user: $prompt->user,
            maxTokens: (int) config('dynoseo.ai.max_tokens_analyse', 3000),
            schema: self::SCHEMA,
            thinkingBudget: is_numeric(config('dynoseo.ai.thinking_write')) ? (int) config('dynoseo.ai.thinking_write') : null,
            temperature: 0.2,
            siteId: $site->id,
        );

        $analysis = $this->clean($response->json());

        if ($analysis === null) {
            throw new VertexException('Analisis bisnes tidak lengkap. Cuba sekali lagi.');
        }

        $site->forceFill([
            'analysis' => $analysis + ['version' => $prompt->version, 'correction' => $correction ?: null],
            'analysis_confirmed_at' => null,
        ])->save();

        return $site;
    }

    public function confirm(Site $site): void
    {
        if ($site->analysis === null) {
            return;
        }

        $site->forceFill(['analysis_confirmed_at' => now()])->save();
    }

    /** Visible text of the website's home page, or '' when it cannot be read. */
    public function websiteText(Site $site): string
    {
        try {
            $file = $this->github->getFile($site->github_repo, $site->branch, $site->repoPath('index.html'));
        } catch (Throwable $e) {
            report($e);

            return '';
        }

        if ($file === null) {
            return '';
        }

        $html = (string) preg_replace('#<(script|style|noscript|svg)\b[^>]*>.*?</\1>#is', ' ', $file['content']);
        // Keep the page title and meta description: they often say what the business does.
        preg_match('#<title[^>]*>(.*?)</title>#is', $html, $title);
        preg_match('#<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)#i', $html, $meta);
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(implode(' ', array_filter([$title[1] ?? '', $meta[1] ?? '', $text])));
        $text = (string) preg_replace('/\s+/u', ' ', $text);

        return mb_substr($text, 0, self::WEBSITE_CHARS);
    }

    private function clean(?array $data): ?array
    {
        $text = fn ($v, int $max = 200) => mb_substr(trim((string) $v), 0, $max);
        $list = fn ($items, int $take = 8) => collect(is_array($items) ? $items : [])
            ->map(fn ($v) => $text($v, 160))->filter()->unique()->take($take)->values()->all();

        $pillars = collect($data['pillars'] ?? [])
            ->filter(fn ($p) => is_array($p) && filled($p['name'] ?? null))
            ->map(fn ($p) => ['name' => $text($p['name'], 60), 'about' => $text($p['about'] ?? '', 200)])
            ->unique('name')->take(6)->values()->all();

        if (blank($data['field'] ?? null) || blank($data['summary'] ?? null) || count($pillars) < 2) {
            return null;
        }

        return [
            'field' => $text($data['field'], 120),
            'summary' => $text($data['summary'], 500),
            'services' => $list($data['services'] ?? []),
            'customers' => $list($data['customers'] ?? [], 5),
            'searches' => $list($data['searches'] ?? []),
            'pillars' => $pillars,
            'avoid' => $list($data['avoid'] ?? []),
            'confidence' => in_array($data['confidence'] ?? null, ['tinggi', 'sederhana', 'rendah'], true) ? $data['confidence'] : 'rendah',
            'question' => $text($data['question'] ?? '', 300),
        ];
    }
}
