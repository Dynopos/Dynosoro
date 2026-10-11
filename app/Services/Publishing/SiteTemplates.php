<?php

namespace App\Services\Publishing;

use App\Models\Site;

/**
 * Blog templates for one website: `_dynoseo/*.html` in the website repo when present (so
 * the blog matches that site's design), otherwise the defaults in resources/site-template.
 */
final class SiteTemplates
{
    public const NAMES = ['article', 'index', 'card', 'cta'];

    /** @param  array<string, string>  $templates */
    private function __construct(private array $templates) {}

    public static function load(Site $site, GitHubClient $github): self
    {
        $templates = [];

        foreach (self::NAMES as $name) {
            $file = $github->getFile($site->github_repo, $site->branch, "_dynoseo/{$name}.html");
            $templates[$name] = $file !== null && trim($file['content']) !== '' ? $file['content'] : self::default($name);
        }

        return new self($templates);
    }

    public static function defaults(): self
    {
        return new self(collect(self::NAMES)->mapWithKeys(fn ($n) => [$n => self::default($n)])->all());
    }

    public static function default(string $name): string
    {
        return (string) file_get_contents(resource_path("site-template/{$name}.html"));
    }

    public static function css(): string
    {
        return (string) file_get_contents(resource_path('site-template/dynoseo.css'));
    }

    public function get(string $name): string
    {
        return $this->templates[$name];
    }
}
