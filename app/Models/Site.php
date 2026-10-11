<?php

namespace App\Models;

use App\Enums\SiteStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A customer website on GitHub Pages that receives articles (spec §3.1). */
class Site extends Model
{
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'name', 'domain', 'github_repo', 'branch', 'docroot', 'niche', 'audience', 'area', 'banned_topics',
        'frequency_days', 'status', 'status_note', 'failure_count', 'next_publish_at',
        'last_published_at', 'paid_until', 'cta', 'cta_bridge', 'installed_at', 'blog_pages',
    ];

    protected function casts(): array
    {
        return [
            'status' => SiteStatus::class,
            'cta' => 'array',
            'cta_bridge' => 'boolean',
            'frequency_days' => 'integer',
            'failure_count' => 'integer',
            'blog_pages' => 'integer',
            'next_publish_at' => 'datetime',
            'last_published_at' => 'datetime',
            'installed_at' => 'datetime',
            'paid_until' => 'date',
        ];
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /** https://domain with no trailing slash. */
    public function baseUrl(): string
    {
        $domain = preg_replace('#^https?://#i', '', trim($this->domain));

        return 'https://'.rtrim((string) $domain, '/');
    }

    public function host(): string
    {
        return (string) parse_url($this->baseUrl(), PHP_URL_HOST);
    }

    /**
     * Repo path for a path in the web root. GitHub Pages serves the repo root; a Laravel
     * app serves public/. Templates (_dynoseo/) always live at the repo root.
     */
    public function repoPath(string $path): string
    {
        $docroot = trim((string) $this->docroot, '/');

        return $docroot === '' || str_starts_with($path, '_dynoseo/') ? $path : $docroot.'/'.$path;
    }

    /** GitHub Pages: the repo root is the web root, so sitemap.xml and robots.txt are files we may manage. */
    public function ownsRootFiles(): bool
    {
        return trim((string) $this->docroot, '/') === '';
    }

    /** Paid (or never billed, e.g. Bob's own sites) within the grace period. */
    public function isPaid(): bool
    {
        if ($this->paid_until === null) {
            return true;
        }

        return $this->paid_until->copy()->endOfDay()->addDays((int) config('dynoseo.articles.grace_days'))->isFuture();
    }

    public function bannedTopicList(): array
    {
        return collect(preg_split('/[,\n]+/', (string) $this->banned_topics))
            ->map(fn ($t) => trim($t))
            ->filter(fn ($t) => mb_strlen($t) >= 3)
            ->values()
            ->all();
    }
}
