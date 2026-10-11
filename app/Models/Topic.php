<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Topic extends Model
{
    use BelongsToWorkspace;

    protected $fillable = [
        'site_id', 'title', 'target_keyword', 'keyword_key', 'intent', 'cluster', 'position', 'status', 'source',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** Comparable form of a keyword or title: lowercase ASCII words, sorted stop-word-free. */
    public static function keyFor(string $text): string
    {
        $words = collect(preg_split('/\s+/', Str::of(Str::ascii($text))->lower()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish()->toString()))
            ->reject(fn ($w) => $w === '' || in_array($w, self::STOP_WORDS, true))
            ->unique()
            ->sort()
            ->values();

        return Str::limit($words->implode(' '), 190, '');
    }

    private const STOP_WORDS = [
        'dan', 'atau', 'yang', 'untuk', 'di', 'ke', 'dari', 'dengan', 'ini', 'itu', 'cara', 'apa',
        'anda', 'kita', 'pada', 'dalam', 'bagi', 'adalah', 'vs', 'the', 'a', 'of', 'panduan', 'tips',
        'mengapa', 'kenapa', 'bagaimana', 'perlu', 'tahu', 'lengkap',
    ];
}
