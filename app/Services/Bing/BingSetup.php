<?php

namespace App\Services\Bing;

use App\Exceptions\BingWebmasterException;
use App\Models\Site;
use Illuminate\Http\Client\ConnectionException;

/**
 * Bing Webmaster Tools for one website, once: add it to the admin's Bing account, verify it with
 * the BingSiteAuth.xml the app committed, and submit its sitemap. IndexNow then tells Bing about
 * each new article. No AI call, no cost. A failure is stored on the website (shown on its card)
 * and never touches the website or its articles.
 */
class BingSetup
{
    public function __construct(private BingWebmasterClient $bing) {}

    /** Whether anything is left to do for this website. */
    public function due(Site $site): bool
    {
        return $this->bing->configured() && ($site->bing_verified_at === null || $site->bing_sitemap_at === null);
    }

    /** Returns true when the website is verified and its sitemap submitted. */
    public function run(Site $site): bool
    {
        if (! $this->due($site)) {
            return $site->bing_verified_at !== null && $site->bing_sitemap_at !== null;
        }

        $siteUrl = $site->baseUrl().'/';

        try {
            if ($site->bing_verified_at === null) {
                $listed = collect($this->bing->sites())
                    ->first(fn (array $s) => parse_url($s['url'], PHP_URL_HOST) === $site->host());

                if ($listed === null) {
                    $this->bing->addSite($siteUrl);
                }

                if (! ($listed['verified'] ?? false) && ! $this->bing->verifySite($siteUrl)) {
                    $site->forceFill(['bing_error' => "Bing belum dapat sahkan website ini. Pastikan {$siteUrl}BingSiteAuth.xml boleh dibuka, kemudian tekan Daftar Bing sekali lagi."])->save();

                    return false;
                }

                $site->forceFill(['bing_verified_at' => now()])->save();
            }

            if ($site->bing_sitemap_at === null) {
                $this->bing->submitSitemap($siteUrl, $site->sitemapUrl());
                $site->forceFill(['bing_sitemap_at' => now()])->save();
            }
        } catch (BingWebmasterException $e) {
            $site->forceFill(['bing_error' => mb_substr($e->getMessage(), 0, 500)])->save();

            return false;
        } catch (ConnectionException) {
            $site->forceFill(['bing_error' => 'Tidak dapat hubungi Bing. Tekan Daftar Bing untuk cuba lagi.'])->save();

            return false;
        }

        $site->forceFill(['bing_error' => null])->save();

        return true;
    }
}
