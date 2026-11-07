<?php

namespace App\Services\Bing;

use App\Exceptions\BingWebmasterException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The only class that talks to the Bing Webmaster Tools API (JSON): the admin's sites, add a
 * site, verify it (BingSiteAuth.xml) and submit its sitemap. Nothing is called unless the API
 * key is set (services.bing_webmaster.api_key), from Bing Webmaster Tools → Settings → API access.
 */
class BingWebmasterClient
{
    public function configured(): bool
    {
        return filled(config('services.bing_webmaster.api_key'));
    }

    /**
     * Sites in the admin's Bing account.
     *
     * @return list<array{url:string, verified:bool}>
     */
    public function sites(): array
    {
        $sites = $this->send('get', 'GetUserSites')->json('d');

        return collect(is_array($sites) ? $sites : [])
            ->filter(fn ($s) => is_array($s) && filled($s['Url'] ?? null))
            ->map(fn ($s) => ['url' => (string) $s['Url'], 'verified' => (bool) ($s['IsVerified'] ?? false)])
            ->values()
            ->all();
    }

    public function addSite(string $siteUrl): void
    {
        $this->send('post', 'AddSite', ['siteUrl' => $siteUrl]);
    }

    /** True when Bing found the verification file (or tag) and the site is now verified. */
    public function verifySite(string $siteUrl): bool
    {
        return $this->send('post', 'VerifySite', ['siteUrl' => $siteUrl])->json('d') === true;
    }

    public function submitSitemap(string $siteUrl, string $sitemapUrl): void
    {
        $this->send('post', 'SubmitFeed', ['siteUrl' => $siteUrl, 'feedUrl' => $sitemapUrl]);
    }

    private function send(string $method, string $name, array $body = []): Response
    {
        $url = rtrim((string) config('services.bing_webmaster.base_url'), '/').'/'.$name
            .'?apikey='.urlencode((string) config('services.bing_webmaster.api_key'));

        $request = Http::acceptJson()->timeout((int) config('services.bing_webmaster.timeout', 30));
        $response = $method === 'get' ? $request->get($url) : $request->asJson()->post($url, $body);

        if ($response->failed()) {
            $message = (string) ($response->json('Message') ?? '');

            throw new BingWebmasterException(match (true) {
                in_array($response->status(), [401, 403], true) => 'Kunci API Bing ditolak. Semak BING_WEBMASTER_API_KEY.',
                $message !== '' => 'Bing: '.mb_substr($message, 0, 300),
                default => "Bing ralat ({$response->status()}). Cuba lagi kemudian.",
            }, $response->status());
        }

        return $response;
    }
}
