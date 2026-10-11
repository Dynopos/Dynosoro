<?php

namespace App\Support;

use App\Mail\AdminAlertMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Tells the platform admin (Bob) about a problem by e-mail, once per key per period. */
class AdminAlert
{
    public function send(string $key, string $subject, string $body, int $onceEveryHours = 24): bool
    {
        if (! Cache::add('admin-alert:'.$key, true, now()->addHours($onceEveryHours))) {
            return false;
        }

        Log::warning("Dyno SEO: {$subject}", ['body' => $body]);

        $emails = User::query()->where('is_admin', true)->pluck('email')->filter()->all();

        if ($emails === []) {
            return true;
        }

        try {
            Mail::to($emails)->send(new AdminAlertMail($subject, $body));
        } catch (Throwable $e) {
            Log::error('Admin alert e-mail failed', ['error' => $e->getMessage()]);
        }

        return true;
    }
}
