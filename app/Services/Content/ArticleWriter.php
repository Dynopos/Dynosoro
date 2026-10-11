<?php

namespace App\Services\Content;

use App\Enums\ArticleStatus;
use App\Exceptions\VertexException;
use App\Models\Article;
use App\Models\Site;
use App\Models\Topic;
use App\Services\Ai\AiGateway;
use App\Services\Ai\PromptRepository;
use Illuminate\Support\Str;

/** Writes one draft with the writing model (spec §5.2). Returns the cleaned draft fields. */
class ArticleWriter
{
    public const SCHEMA = [
        'type' => 'OBJECT',
        'properties' => [
            'title' => ['type' => 'STRING'],
            'slug' => ['type' => 'STRING'],
            'meta_description' => ['type' => 'STRING'],
            'summary' => ['type' => 'STRING'],
            'body_html' => ['type' => 'STRING'],
            'faq' => [
                'type' => 'ARRAY',
                'items' => [
                    'type' => 'OBJECT',
                    'properties' => ['q' => ['type' => 'STRING'], 'a' => ['type' => 'STRING']],
                    'required' => ['q', 'a'],
                ],
            ],
            'image_prompt' => ['type' => 'STRING'],
            'image_alt' => ['type' => 'STRING'],
            'cta_bridge' => ['type' => 'STRING'],
        ],
        'required' => ['title', 'slug', 'meta_description', 'summary', 'body_html', 'faq', 'image_prompt', 'image_alt'],
    ];

    public function __construct(
        private AiGateway $ai,
        private PromptRepository $prompts,
        private HtmlSanitizer $sanitizer,
    ) {}

    public function version(): string
    {
        return $this->prompts->version('write');
    }

    /**
     * @param  list<string>  $notes  issues from the previous draft (rewrite)
     * @return array{title:string, slug:string, meta_description:string, summary:string, body_html:string, faq:array, image_prompt:string, image_alt:string, cta_bridge:?string, raw_body:string}
     */
    public function draft(Site $site, Topic $topic, Article $article, array $notes = []): array
    {
        $published = Article::query()->where('site_id', $site->id)
            ->where('status', ArticleStatus::Published)
            ->whereKeyNot($article->id)
            ->latest('published_at');

        $existing = (clone $published)->take(30)->get(['title', 'slug'])
            ->map(fn ($a) => "- {$a->title} (/blog/{$a->slug}/)")->implode("\n");
        $images = (clone $published)->whereNotNull('image_prompt')->take(5)->pluck('image_prompt')
            ->map(fn ($p) => '- '.$p)->implode("\n");

        $prompt = $this->prompts->render('write', [], [
            'niche' => $site->niche,
            'audience' => $site->audience ?: '-',
            'area' => $site->area ?: 'Malaysia',
            'banned' => $site->banned_topics ?: '-',
            'topic' => $topic->title,
            'keyword' => $topic->target_keyword,
            'intent' => $topic->intent ?: 'panduan',
            'existing' => $existing ?: '-',
            'images' => $images ?: '-',
            'bridge_rule' => $site->cta_bridge
                ? 'cta_bridge: satu ayat (maksimum 30 patah perkataan) yang menyambung topik artikel kepada pembaca yang mungkin perlukan bantuan pakar dalam bidang ini. Tanpa nama bisnes, harga, tawaran atau janji.'
                : 'cta_bridge: biarkan kosong.',
            'notes' => $notes === [] ? '' : "Draf sebelum ini DITOLAK. Betulkan semua isu ini:\n".collect($notes)->map(fn ($n) => '- '.$n)->implode("\n"),
        ]);

        $response = $this->ai->text(
            purpose: 'write',
            model: (string) config('dynoseo.ai.model_write'),
            system: $prompt->system,
            user: $prompt->user,
            maxTokens: (int) config('dynoseo.ai.max_tokens_write'),
            schema: self::SCHEMA,
            thinkingBudget: is_numeric(config('dynoseo.ai.thinking_write')) ? (int) config('dynoseo.ai.thinking_write') : null,
            temperature: 0.8,
            siteId: $site->id,
            articleId: $article->id,
        );

        $data = $response->json();

        if ($data === null || ! isset($data['title'], $data['body_html'])) {
            throw new VertexException('Draf artikel tidak sah (bukan JSON lengkap, finish: '.($response->finishReason ?? '-').').');
        }

        $faq = collect(is_array($data['faq'] ?? null) ? $data['faq'] : [])
            ->filter(fn ($f) => is_array($f) && trim((string) ($f['q'] ?? '')) !== '' && trim((string) ($f['a'] ?? '')) !== '')
            ->map(fn ($f) => ['q' => Str::squish(strip_tags((string) $f['q'])), 'a' => Str::squish(strip_tags((string) $f['a']))])
            ->take(6)
            ->values()
            ->all();

        $bridge = $site->cta_bridge ? Str::squish(strip_tags((string) ($data['cta_bridge'] ?? ''))) : '';

        return [
            'title' => Str::squish(strip_tags((string) $data['title'])),
            'slug' => $this->slug((string) ($data['slug'] ?? ''), (string) $data['title']),
            'meta_description' => Str::squish(strip_tags((string) ($data['meta_description'] ?? ''))),
            'summary' => Str::squish(strip_tags((string) ($data['summary'] ?? ''))),
            // Validated before cleaning, so the writer is told about links it should not add.
            'raw_body' => (string) $data['body_html'],
            'body_html' => $this->sanitizer->clean((string) $data['body_html']),
            'faq' => $faq,
            'image_prompt' => Str::limit(Str::squish(strip_tags((string) ($data['image_prompt'] ?? $topic->title))), 400, ''),
            'image_alt' => Str::limit(Str::squish(strip_tags((string) ($data['image_alt'] ?? $data['title']))), 150, ''),
            'cta_bridge' => $bridge !== '' ? Str::limit($bridge, 250, '') : null,
        ];
    }

    private function slug(string $slug, string $title): string
    {
        $slug = Str::slug($slug ?: $title);

        return trim(Str::limit($slug !== '' ? $slug : 'artikel', 70, ''), '-');
    }
}
