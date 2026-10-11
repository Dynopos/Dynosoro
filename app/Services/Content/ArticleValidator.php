<?php

namespace App\Services\Content;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Site;
use App\Models\Topic;
use Illuminate\Support\Str;

/**
 * Code checks before the AI check (spec §5.4). Returns a list of issues in Bahasa Melayu;
 * an empty list means the draft passed. Cheap and deterministic, so it runs first.
 *
 *  - rule 1: no business name, prices, phone numbers, promotions,
 *  - rule 2: no standards codes, percentages or "kajian menunjukkan" claims,
 *  - rule 5: no external links (the sanitizer removes them; flagged so the writer learns),
 *  - rule 8: no title that repeats a published article,
 *  - shape: word count, headings, meta length, Indonesian words.
 */
class ArticleValidator
{
    private const INDONESIAN = ['bisa', 'kamu', 'nggak', 'gak', 'banget', 'silakan', 'uang', 'karena', 'kantor', 'gimana', 'sih', 'aja', 'udah', 'sepeda motor'];

    private const PROMISE = ['diskaun', 'promosi', 'percuma', 'tawaran', 'dijamin', 'jaminan', 'harga istimewa', 'hubungi kami', 'whatsapp kami'];

    /** @param  array{title?:string, meta_description?:string, body_html?:string, faq?:array, cta_bridge?:?string}  $draft */
    public function issues(Site $site, array $draft, ?int $ignoreArticleId = null): array
    {
        $issues = [];
        $title = trim((string) ($draft['title'] ?? ''));
        $meta = trim((string) ($draft['meta_description'] ?? ''));
        $body = (string) ($draft['body_html'] ?? '');
        $faqText = collect($draft['faq'] ?? [])->map(fn ($f) => ($f['q'] ?? '').' '.($f['a'] ?? ''))->implode(' ');
        $bridge = trim((string) ($draft['cta_bridge'] ?? ''));
        $plain = self::plainText($body);
        $all = $title."\n".$meta."\n".$plain."\n".$faqText."\n".$bridge;
        $lower = mb_strtolower($all);

        if ($title === '' || mb_strlen($title) > 90) {
            $issues[] = 'Tajuk mesti ada dan maksimum 90 aksara.';
        }

        $metaLength = mb_strlen($meta);
        if ($metaLength < 50 || $metaLength > 160) {
            $issues[] = "Meta description mesti 50 hingga 155 aksara (sekarang {$metaLength}).";
        }

        $words = self::wordCount($body);
        $min = (int) config('dynoseo.articles.min_words');
        $max = (int) config('dynoseo.articles.max_words');
        if ($words < $min || $words > $max) {
            $issues[] = "Panjang artikel mesti {$min} hingga {$max} patah perkataan (sekarang {$words}).";
        }

        if (substr_count(mb_strtolower($body), '<h2') < 2) {
            $issues[] = 'Artikel perlu sekurang-kurangnya dua subtajuk <h2>.';
        }

        // Rule 1: the article is general; the business only appears in the hook.
        $name = mb_strtolower(trim($site->name));
        if ($name !== '' && mb_strlen($name) >= 4 && str_contains($lower, $name)) {
            $issues[] = 'Jangan sebut nama bisnes dalam artikel.';
        }

        if (preg_match('/\bRM\s?\d|\bringgit\b/iu', $all) === 1) {
            $issues[] = 'Jangan sebut harga (RM).';
        }

        if (preg_match('/(\+?6?0\s?1\d[\s-]?\d{3,4}[\s-]?\d{4})|(\b0[3-9][\s-]?\d{3,4}[\s-]?\d{4}\b)/', $all) === 1) {
            $issues[] = 'Jangan letak nombor telefon.';
        }

        foreach (self::PROMISE as $word) {
            if (preg_match('/\b'.preg_quote($word, '/').'\b/u', $lower) === 1) {
                $issues[] = "Jangan guna ayat jualan/promosi (\"{$word}\").";

                break;
            }
        }

        foreach ($site->bannedTopicList() as $banned) {
            if (str_contains($lower, mb_strtolower($banned))) {
                $issues[] = "Artikel menyentuh topik dilarang: {$banned}.";
            }
        }

        // Rule 2: no invented facts stated as certain.
        if (preg_match('/\b(JKR|SIRIM|ASTM|BS\s?EN|ISO\s?\d{3,5}|MS\s?\d{3,5}|Akta\s+\d+|Seksyen\s+\d+)\b/u', $all, $m) === 1) {
            $issues[] = "Jangan sebut piawaian/undang-undang khusus (\"{$m[0]}\"); tulis secara umum.";
        }

        if (preg_match('/\d+(?:[.,]\d+)?\s?(%|peratus)/iu', $all) === 1) {
            $issues[] = 'Jangan guna peratus atau statistik.';
        }

        if (preg_match('/\b(kajian|statistik|laporan|data)\b[^.]{0,40}\b(menunjukkan|mendapati|membuktikan)\b|\bmenurut\s+(kajian|statistik|laporan|pakar)\b/iu', $all) === 1) {
            $issues[] = 'Jangan petik kajian, statistik atau laporan.';
        }

        // Rule 5: only internal /blog/ links.
        if (preg_match_all('/href\s*=\s*["\']([^"\']*)["\']/i', $body, $links) > 0) {
            foreach ($links[1] as $href) {
                if (! HtmlSanitizer::isInternalBlogLink(trim($href))) {
                    $issues[] = 'Tiada pautan luar; pautan hanya ke /blog/slug-artikel/.';

                    break;
                }
            }
        }

        if (preg_match('/https?:\/\/|www\./i', $plain."\n".$faqText) === 1) {
            $issues[] = 'Jangan tulis alamat web dalam teks.';
        }

        $indonesian = collect(self::INDONESIAN)->filter(fn ($w) => preg_match('/\b'.preg_quote($w, '/').'\b/u', $lower) === 1);
        if ($indonesian->count() >= 2) {
            $issues[] = 'Guna Bahasa Melayu Malaysia, bukan Bahasa Indonesia ('.$indonesian->implode(', ').').';
        }

        // Rule 8: no repeated topic on the same website.
        if ($title !== '' && $this->repeatsPublishedTitle($site, $title, $ignoreArticleId)) {
            $issues[] = 'Tajuk ini sama dengan artikel yang sudah terbit.';
        }

        return array_values(array_unique($issues));
    }

    public function repeatsPublishedTitle(Site $site, string $title, ?int $ignoreArticleId = null): bool
    {
        $key = Topic::keyFor($title);

        return Article::query()
            ->where('site_id', $site->id)
            ->whereIn('status', [ArticleStatus::Published, ArticleStatus::Ready])
            ->when($ignoreArticleId, fn ($q) => $q->whereKeyNot($ignoreArticleId))
            ->pluck('title')
            ->contains(fn ($existing) => Topic::keyFor((string) $existing) === $key);
    }

    public static function plainText(string $html): string
    {
        $text = html_entity_decode(strip_tags(preg_replace('/<(br|\/p|\/h2|\/h3|\/li|\/tr)>/i', "$0\n", $html) ?? $html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[ \t]+/', ' ', $text) ?? $text);
    }

    public static function wordCount(string $html): int
    {
        $text = Str::squish(self::plainText($html));

        return $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    }
}
