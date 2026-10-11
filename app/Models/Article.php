<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use BelongsToWorkspace, HasFactory;

    protected $fillable = [
        'site_id', 'topic_id', 'title', 'slug', 'meta_description', 'summary', 'body_html', 'faq',
        'cta_bridge', 'image_prompt', 'image_path', 'image_alt', 'word_count', 'status', 'source',
        'attempts', 'check_notes', 'error', 'prompt_version', 'commit_sha', 'published_at',
        'content_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ArticleStatus::class,
            'faq' => 'array',
            'check_notes' => 'array',
            'word_count' => 'integer',
            'attempts' => 'integer',
            'published_at' => 'datetime',
            'content_updated_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatus::Published);
    }

    /** Repo path of the article page. */
    public function pagePath(): string
    {
        return 'blog/'.$this->slug.'/index.html';
    }

    public function urlPath(): string
    {
        return '/blog/'.$this->slug.'/';
    }

    public function url(): string
    {
        return $this->site->baseUrl().$this->urlPath();
    }
}
