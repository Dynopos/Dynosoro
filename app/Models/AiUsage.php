<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    use BelongsToWorkspace;

    protected $table = 'ai_usage';

    public const UPDATED_AT = null;

    protected $fillable = [
        'site_id', 'article_id', 'model', 'purpose', 'input_tokens', 'output_tokens', 'images', 'cost_estimate',
    ];

    protected function casts(): array
    {
        return ['cost_estimate' => 'float'];
    }
}
