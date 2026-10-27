<?php

namespace App\Services\Publishing;

use App\Exceptions\PublishRuleViolation;

/**
 * Rule 5: the app only touches Dyno SEO files in a website repo (spec §7.3), and in the
 * home page only the block between the dynoseo:latest markers.
 */
class RepoGuard
{
    public const START = '<!-- dynoseo:latest -->';

    public const END = '<!-- /dynoseo:latest -->';

    public static function isAllowed(string $path): bool
    {
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            return false;
        }

        if (in_array($path, ['sitemap.xml', 'sitemap-pages.xml', 'robots.txt', 'index.html', 'llms.txt'], true)) {
            return true;
        }

        $key = (string) config('dynoseo.indexnow_key');
        if ($key !== '' && $path === $key.'.txt') {
            return true;
        }

        return preg_match('#^(blog|_dynoseo)/[A-Za-z0-9._/-]+$#', $path) === 1;
    }

    /**
     * @param  array<string, string|null>  $files
     * @param  string|null  $originalHome  index.html before the change, when it is in $files
     */
    public static function assertAllowed(array $files, ?string $originalHome = null): void
    {
        foreach (array_keys($files) as $path) {
            if (! self::isAllowed((string) $path)) {
                throw new PublishRuleViolation("Dyno SEO tidak dibenarkan menulis {$path}.");
            }
        }

        if (array_key_exists('index.html', $files)) {
            if ($files['index.html'] === null || $originalHome === null
                || self::outsideBlock($files['index.html']) !== self::outsideBlock($originalHome)) {
                throw new PublishRuleViolation('Laman utama hanya boleh diubah di antara penanda dynoseo:latest.');
            }
        }
    }

    public static function hasBlock(string $html): bool
    {
        $start = strpos($html, self::START);

        return $start !== false && strpos($html, self::END, $start) !== false;
    }

    /** Replace only what is between the markers. */
    public static function replaceBlock(string $html, string $inner): string
    {
        $start = strpos($html, self::START);
        $end = $start === false ? false : strpos($html, self::END, $start);

        if ($start === false || $end === false) {
            return $html;
        }

        $from = $start + strlen(self::START);

        return substr($html, 0, $from)."\n".$inner."\n".substr($html, $end);
    }

    private static function outsideBlock(string $html): string
    {
        return self::replaceBlock($html, '');
    }
}
