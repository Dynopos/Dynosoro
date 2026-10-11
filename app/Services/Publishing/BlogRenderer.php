<?php

namespace App\Services\Publishing;

use App\Models\Article;
use App\Models\Site;
use App\Support\MalaysianPhone;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Builds the static blog files from the database (spec §7.3–7.4). Text values are escaped;
 * only the sanitized article body and HTML built here go in unescaped.
 */
class BlogRenderer
{
    private const MONTHS = ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'];

    /** @param  Collection<int, Article>  $related */
    public function articlePage(Site $site, Article $article, SiteTemplates $templates, Collection $related): string
    {
        $canonical = $article->url();
        $image = $this->imageUrl($site, $article);

        return $this->fill($templates->get('article'), [
            'title' => $article->title,
            'meta_description' => $article->meta_description,
            'canonical' => $canonical,
            'og_image' => $image ?? '',
            'site_name' => $site->name,
            'author' => $this->author($site),
            'date' => self::dateMs($article->published_at),
            'date_iso' => $article->published_at?->toAtomString(),
            'updated_iso' => ($article->content_updated_at ?? $article->published_at)?->toAtomString(),
            'home_url' => '/',
            'blog_url' => '/blog/',
            'year' => (string) now()->year,
        ], [
            'image' => $article->image_path
                ? '<img class="dynoseo-hero" src="/'.e($article->image_path).'" alt="'.e($article->image_alt ?: $article->title).'" width="'.config('dynoseo.articles.image_width').'" height="'.config('dynoseo.articles.image_height').'" fetchpriority="high">'
                : '',
            'body' => (string) $article->body_html,
            'faq' => $this->faq($article),
            'cta' => $this->cta($site, $article, $templates),
            'related' => $this->related($site, $related, $templates),
            'schema' => $this->schema($site, $article, $image),
        ]);
    }

    /** Rule 3: every article ends with the hook to the customer's business. */
    public function cta(Site $site, Article $article, SiteTemplates $templates): string
    {
        $cta = (array) $site->cta;

        return $this->fill($templates->get('cta'), [
            'cta_title' => $cta['title'] ?? '',
            'cta_text' => $cta['text'] ?? '',
            'cta_button' => $cta['button'] ?? 'WhatsApp Kami',
            'cta_url' => $this->ctaUrl($site, $article),
        ], [
            'cta_bridge' => $site->cta_bridge && $article->cta_bridge
                ? '<p class="dynoseo-cta-bridge">'.e($article->cta_bridge).'</p>'
                : '',
        ]);
    }

    public function ctaUrl(Site $site, Article $article): string
    {
        $cta = (array) $site->cta;
        $message = str_replace('{tajuk}', (string) $article->title, (string) ($cta['message'] ?? ''));
        $wa = MalaysianPhone::waLink($cta['whatsapp'] ?? null, $message);

        if ($wa !== null) {
            return $wa;
        }

        $url = trim((string) ($cta['url'] ?? ''));

        return preg_match('#^(https?://|/)#i', $url) === 1 ? $url : '/';
    }

    public function card(Site $site, Article $article, SiteTemplates $templates): string
    {
        return $this->fill($templates->get('card'), [
            'url' => $article->urlPath(),
            'title' => $article->title,
            'summary' => $article->summary,
            'date' => self::dateMs($article->published_at),
        ], [
            'image' => $article->image_path
                ? '<img src="/'.e($article->image_path).'" alt="'.e($article->image_alt ?: $article->title).'" width="'.config('dynoseo.articles.image_width').'" height="'.config('dynoseo.articles.image_height').'" loading="lazy">'
                : '',
        ]);
    }

    /**
     * blog/index.html and blog/page/N/index.html.
     *
     * @param  Collection<int, Article>  $published  newest first
     * @return array<string, string>
     */
    public function listingPages(Site $site, Collection $published, SiteTemplates $templates): array
    {
        $perPage = max(1, (int) config('dynoseo.articles.cards_per_page'));
        $chunks = $published->values()->chunk($perPage);
        $chunks = $chunks->isEmpty() ? collect([collect()]) : $chunks;
        $last = $chunks->count();
        $pages = [];

        foreach ($chunks->values() as $index => $chunk) {
            $number = $index + 1;
            $path = $number === 1 ? 'blog/index.html' : "blog/page/{$number}/index.html";
            $prev = $number === 2 ? '/blog/' : ($number > 2 ? '/blog/page/'.($number - 1).'/' : null);
            $next = $number < $last ? '/blog/page/'.($number + 1).'/' : null;

            $pages[$path] = $this->fill($templates->get('index'), [
                'title' => $number === 1 ? "Blog {$site->name}" : "Blog {$site->name} (halaman {$number})",
                'meta_description' => 'Artikel dan panduan tentang '.$this->nicheLine($site).'.',
                'canonical' => $site->baseUrl().($number === 1 ? '/blog/' : "/blog/page/{$number}/"),
                'site_name' => $site->name,
                'niche_line' => 'Panduan dan info tentang '.$this->nicheLine($site).'.',
                'home_url' => '/',
                'blog_url' => '/blog/',
                'year' => (string) now()->year,
            ], [
                'cards' => $chunk->map(fn (Article $a) => $this->card($site, $a, $templates))->implode("\n"),
                'pagination' => ($prev || $next)
                    ? '<nav class="dynoseo-pages">'.($prev ? '<a href="'.$prev.'">← Baru</a>' : '<span></span>').($next ? '<a href="'.$next.'">Lama →</a>' : '').'</nav>'
                    : '',
                'robots' => $number > 1 ? '<meta name="robots" content="noindex, follow">' : '',
            ]);
        }

        return $pages;
    }

    /** @param  Collection<int, Article>  $latest */
    public function homeBlock(Site $site, Collection $latest, SiteTemplates $templates): string
    {
        return '<link rel="stylesheet" href="/blog/assets/dynoseo.css">'."\n"
            .'<div class="dynoseo-latest">'."\n"
            .$latest->map(fn (Article $a) => $this->card($site, $a, $templates))->implode("\n")
            ."\n</div>\n"
            .'<a class="dynoseo-latest-more" href="/blog/">Lihat semua artikel →</a>';
    }

    /** @param  Collection<int, Article>  $published */
    public function sitemap(Site $site, Collection $published): string
    {
        $urls = collect([['loc' => $site->baseUrl().'/blog/', 'lastmod' => $published->first()?->published_at]])
            ->merge($published->map(fn (Article $a) => [
                'loc' => $a->url(),
                'lastmod' => $a->content_updated_at ?? $a->published_at,
            ]));

        $body = $urls->map(fn ($u) => '  <url><loc>'.$this->xml($u['loc']).'</loc>'
            .($u['lastmod'] ? '<lastmod>'.$u['lastmod']->toDateString().'</lastmod>' : '').'</url>')->implode("\n");

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$body."\n</urlset>\n";
    }

    /** Root sitemap index pointing at the site's own pages (if any) and the blog sitemap. */
    public function sitemapIndex(Site $site, bool $withPages): string
    {
        $maps = collect($withPages ? ['/sitemap-pages.xml'] : [])->push('/blog/sitemap.xml')
            ->map(fn ($p) => '  <sitemap><loc>'.$this->xml($site->baseUrl().$p).'</loc></sitemap>')->implode("\n");

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n".$maps."\n</sitemapindex>\n";
    }

    /** @param  Collection<int, Article>  $published */
    public function feed(Site $site, Collection $published): string
    {
        $items = $published->take(20)->map(fn (Article $a) => '  <item>'
            .'<title>'.$this->xml($a->title).'</title>'
            .'<link>'.$this->xml($a->url()).'</link>'
            .'<guid isPermaLink="true">'.$this->xml($a->url()).'</guid>'
            .'<pubDate>'.$a->published_at?->toRssString().'</pubDate>'
            .'<description>'.$this->xml((string) $a->summary).'</description>'
            .'</item>')->implode("\n");

        return '<?xml version="1.0" encoding="UTF-8"?>'."\n"
            .'<rss version="2.0"><channel>'
            .'<title>'.$this->xml("Blog {$site->name}").'</title>'
            .'<link>'.$this->xml($site->baseUrl().'/blog/').'</link>'
            .'<description>'.$this->xml('Artikel tentang '.$this->nicheLine($site)).'</description>'
            .'<language>ms-MY</language>'."\n".$items."\n</channel></rss>\n";
    }

    public static function dateMs(?CarbonInterface $date): string
    {
        return $date ? $date->day.' '.self::MONTHS[$date->month - 1].' '.$date->year : '';
    }

    private function faq(Article $article): string
    {
        $faq = collect($article->faq ?? [])->filter(fn ($f) => ! empty($f['q']) && ! empty($f['a']));

        if ($faq->isEmpty()) {
            return '';
        }

        return '<section class="dynoseo-faq"><h2>Soalan lazim</h2>'
            .$faq->map(fn ($f) => '<details><summary>'.e($f['q']).'</summary><p>'.e($f['a']).'</p></details>')->implode('')
            .'</section>';
    }

    private function related(Site $site, Collection $related, SiteTemplates $templates): string
    {
        if ($related->isEmpty()) {
            return '';
        }

        return '<section class="dynoseo-related"><h2>Artikel berkaitan</h2><div class="dynoseo-cards">'
            .$related->map(fn (Article $a) => $this->card($site, $a, $templates))->implode("\n")
            .'</div></section>';
    }

    private function schema(Site $site, Article $article, ?string $image): string
    {
        $graph = [
            array_filter([
                '@type' => 'BlogPosting',
                'headline' => $article->title,
                'description' => $article->meta_description,
                'image' => $image,
                'datePublished' => $article->published_at?->toAtomString(),
                'dateModified' => ($article->content_updated_at ?? $article->published_at)?->toAtomString(),
                'author' => ['@type' => 'Organization', 'name' => $this->author($site)],
                'publisher' => ['@type' => 'Organization', 'name' => $site->name, 'url' => $site->baseUrl().'/'],
                'mainEntityOfPage' => $article->url(),
                'inLanguage' => 'ms-MY',
            ]),
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Utama', 'item' => $site->baseUrl().'/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Blog', 'item' => $site->baseUrl().'/blog/'],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $article->title, 'item' => $article->url()],
                ],
            ],
        ];

        $faq = collect($article->faq ?? [])->filter(fn ($f) => ! empty($f['q']) && ! empty($f['a']));
        if ($faq->isNotEmpty()) {
            $graph[] = [
                '@type' => 'FAQPage',
                'mainEntity' => $faq->map(fn ($f) => [
                    '@type' => 'Question',
                    'name' => $f['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
                ])->values()->all(),
            ];
        }

        $json = json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP);

        return '<script type="application/ld+json">'.$json.'</script>';
    }

    private function imageUrl(Site $site, Article $article): ?string
    {
        return $article->image_path ? $site->baseUrl().'/'.$article->image_path : null;
    }

    private function author(Site $site): string
    {
        return 'Pasukan '.$site->name;
    }

    private function nicheLine(Site $site): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $site->niche));
    }

    private function xml(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * {{key}} placeholders: $text values are HTML-escaped, $html values are inserted as is.
     * Unknown placeholders are removed.
     */
    private function fill(string $template, array $text, array $html = []): string
    {
        $pairs = [];
        foreach ($text as $key => $value) {
            $pairs['{{'.$key.'}}'] = e((string) $value);
        }
        foreach ($html as $key => $value) {
            $pairs['{{'.$key.'}}'] = (string) $value;
        }

        return (string) preg_replace('/\{\{[a-z_]+\}\}/', '', strtr($template, $pairs));
    }
}
