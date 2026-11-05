<?php

namespace App\Services\Google;

use App\Exceptions\GoogleBusinessException;
use App\Models\GoogleConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The only class that talks to Google for Business Profiles: OAuth, the list of profiles the
 * connected account manages, and local posts. Nothing here is called unless the OAuth client
 * is configured (services.google_business).
 */
class GoogleBusinessClient
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const ACCOUNTS_URL = 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts';

    private const INFO_URL = 'https://mybusinessbusinessinformation.googleapis.com/v1';

    private const POSTS_URL = 'https://mybusiness.googleapis.com/v4';

    public function configured(): bool
    {
        return filled(config('services.google_business.client_id')) && filled(config('services.google_business.client_secret'));
    }

    public function authUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google_business.client_id'),
            'redirect_uri' => route('google.callback'),
            'response_type' => 'code',
            'scope' => config('services.google_business.scope'),
            'access_type' => 'offline',
            // Always ask again, so Google always returns a refresh token.
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /** @return array{refresh_token:string, email:?string} */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->timeout($this->timeout())->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google_business.client_id'),
            'client_secret' => config('services.google_business.client_secret'),
            'redirect_uri' => route('google.callback'),
            'grant_type' => 'authorization_code',
        ]);

        if ($response->failed() || blank($response->json('refresh_token'))) {
            throw new GoogleBusinessException('Google tidak beri kebenaran ('.$response->status().'). Cuba Sambung Google sekali lagi.', $response->status());
        }

        return [
            'refresh_token' => (string) $response->json('refresh_token'),
            'email' => $this->emailFromIdToken((string) $response->json('id_token')),
        ];
    }

    /**
     * Profiles the connected account manages, for the website form.
     *
     * @return list<array{name:string, title:string, address:string}> name = accounts/{a}/locations/{l}
     */
    public function locations(GoogleConnection $connection): array
    {
        $locations = [];

        foreach ($this->pages($connection, self::ACCOUNTS_URL, 'accounts') as $account) {
            $url = self::INFO_URL.'/'.$account['name'].'/locations';

            foreach ($this->pages($connection, $url, 'locations', ['readMask' => 'name,title,storefrontAddress']) as $location) {
                $id = str_replace('locations/', '', (string) ($location['name'] ?? ''));
                $address = $location['storefrontAddress'] ?? [];

                $locations[] = [
                    'name' => $account['name'].'/locations/'.$id,
                    'title' => (string) ($location['title'] ?? $id),
                    'address' => trim(implode(', ', array_filter([$address['locality'] ?? null, $address['administrativeArea'] ?? null]))),
                ];
            }
        }

        return $locations;
    }

    /** Create one local post. Returns its resource name. */
    public function createPost(GoogleConnection $connection, string $location, array $post): string
    {
        if (preg_match('#^accounts/[0-9]+/locations/[0-9]+$#', $location) !== 1) {
            throw new GoogleBusinessException('Profil Google website ini tidak sah. Pilih semula dalam borang website.');
        }

        $response = $this->http($connection)->post(self::POSTS_URL.'/'.$location.'/localPosts', $post);

        return (string) $this->json($response)['name'];
    }

    /** @return list<array<string, mixed>> */
    private function pages(GoogleConnection $connection, string $url, string $key, array $query = []): array
    {
        $items = [];
        $token = null;

        // At most 10 pages (1,000 items): no unbounded loops against an external API.
        for ($i = 0; $i < 10; $i++) {
            $data = $this->json($this->http($connection)->get($url, array_filter($query + ['pageSize' => 100, 'pageToken' => $token])));
            $items = [...$items, ...($data[$key] ?? [])];
            $token = $data['nextPageToken'] ?? null;

            if (! $token) {
                break;
            }
        }

        return $items;
    }

    private function http(GoogleConnection $connection): PendingRequest
    {
        return Http::withToken($this->accessToken($connection))->acceptJson()->timeout($this->timeout());
    }

    private function accessToken(GoogleConnection $connection): string
    {
        return Cache::remember('gbp-token:'.$connection->id.':'.md5((string) $connection->updated_at), now()->addMinutes(50), function () use ($connection) {
            $response = Http::asForm()->timeout($this->timeout())->post(self::TOKEN_URL, [
                'client_id' => config('services.google_business.client_id'),
                'client_secret' => config('services.google_business.client_secret'),
                'refresh_token' => $connection->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->failed() || blank($response->json('access_token'))) {
                throw new GoogleBusinessException('Sambungan Google tamat atau dibatalkan. Tekan Sambung Google semula (skrin Website).', $response->status());
            }

            return (string) $response->json('access_token');
        });
    }

    private function json(Response $response): array
    {
        if ($response->failed()) {
            throw new GoogleBusinessException($this->describe($response), $response->status());
        }

        return (array) $response->json();
    }

    private function describe(Response $response): string
    {
        $message = trim(mb_substr((string) ($response->json('error.message') ?? $response->body()), 0, 200));
        $lower = mb_strtolower($message);

        $cause = match (true) {
            $response->status() === 429 || str_contains($lower, 'quota') => 'Akses Business Profile API belum diluluskan Google (kuota 0), atau had kadar dicapai.',
            str_contains($lower, 'has not been used') || str_contains($lower, 'disabled') => 'API Google belum diaktifkan dalam projek Google Cloud (lihat DEPLOY.md).',
            $response->status() === 403 || $response->status() === 404 => 'Akaun Google ini bukan Manager profil tersebut, atau profil tiada.',
            $response->status() === 400 => 'Google tolak kandungan post.',
            default => 'Google gagal ('.$response->status().').',
        };

        return $cause.($message !== '' ? ' Mesej Google: "'.$message.'"' : '');
    }

    private function emailFromIdToken(string $idToken): ?string
    {
        $parts = explode('.', $idToken);
        $payload = isset($parts[1]) ? json_decode((string) base64_decode(strtr($parts[1], '-_', '+/')), true) : null;

        return is_array($payload) && is_string($payload['email'] ?? null) ? $payload['email'] : null;
    }

    private function timeout(): int
    {
        return (int) config('services.google_business.timeout', 30);
    }
}
