<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class PublishLog extends Model
{
    use BelongsToWorkspace;

    public const UPDATED_AT = null;

    protected $fillable = ['site_id', 'article_id', 'action', 'commit_sha', 'paths', 'error'];

    protected function casts(): array
    {
        return ['paths' => 'array'];
    }
}
