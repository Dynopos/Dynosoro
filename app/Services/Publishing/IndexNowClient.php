<?php

namespace App\Services\Publishing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tells Bing/Yandex about new or changed pages (spec §4.9). Google ignores IndexNow and
 * finds articles through the sitemap. Failures are logged, never fatal.
 */
class IndexNowClient
{
    /** @param  list<string>  $urls */
    public function submit(string $host, array $urls): void
    {
        $key = (string) config('dynoseo.indexnow_key');

        if ($key === '' || $urls === []) {
            return;
        }

        try {
            $response = Http::timeout((int) config('services.indexnow.timeout', 15))
                ->asJson()
                ->post((string) config('services.indexnow.endpoint'), [
                    'host' => $host,
                    'key' => $key,
                    'keyLocation' => 'https://'.$host.'/'.$key.'.txt',
                    'urlList' => array_values($urls),
                ]);

            if ($response->failed()) {
                Log::warning('IndexNow failed', ['host' => $host, 'status' => $response->status()]);
            }
        } catch (\Throwable $e) {
            Log::warning('IndexNow error', ['host' => $host, 'error' => $e->getMessage()]);
        }
    }
}
