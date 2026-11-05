<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Illuminate\Database\Eloquent\Model;

/**
 * The Google account that manages customers' Business Profiles for this workspace. The refresh
 * token is encrypted at rest (APP_KEY) and never shown or logged (rule 7).
 */
class GoogleConnection extends Model
{
    use BelongsToWorkspace;

    protected $fillable = ['email', 'refresh_token', 'connected_at', 'last_error'];

    protected $hidden = ['refresh_token'];

    protected function casts(): array
    {
        return [
            'refresh_token' => 'encrypted',
            'connected_at' => 'datetime',
        ];
    }
}
