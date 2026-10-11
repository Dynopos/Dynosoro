<?php

namespace App\Services\Ai;

use App\Exceptions\VertexException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * The only class that talks to Google Vertex AI (Gemini for text, Imagen for images).
 *
 * Auth: a service account key (GOOGLE_CREDENTIALS_BASE64 in .env, or a key file outside the
 * repo at GOOGLE_APPLICATION_CREDENTIALS) is exchanged for a one-hour OAuth token, cached
 * for 50 minutes. VERTEX_ACCESS_TOKEN may be
 * set instead for local testing (e.g. `gcloud auth print-access-token`).
 *
 * Model names come from .env via config, never from code. Do not call this directly: go
 * through AiGateway, which enforces the budget and records ai_usage.
 */
class VertexClient
{
    private const SCOPE = 'https://www.googleapis.com/auth/cloud-platform';

    /**
     * @param  array|null  $schema  Gemini responseSchema (OpenAPI subset, upper-case types)
     */
    public function generate(string $model, string $system, string $user, int $maxTokens, ?array $schema = null, ?int $thinkingBudget = null, float $temperature = 0.7): VertexTextResponse
    {
        $generation = array_filter([
            'maxOutputTokens' => $maxTokens,
            'temperature' => $temperature,
            'responseMimeType' => $schema ? 'application/json' : null,
            'responseSchema' => $schema,
            'thinkingConfig' => $thinkingBudget !== null ? ['thinkingBudget' => $thinkingBudget] : null,
        ], fn ($v) => $v !== null);

        $response = $this->post($this->modelUrl($model, 'generateContent', $this->region()), [
            'systemInstruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
            'generationConfig' => $generation,
        ]);

        $json = (array) $response->json();
        $candidate = $json['candidates'][0] ?? null;
        $text = collect($candidate['content']['parts'] ?? [])
            ->reject(fn ($part) => ($part['thought'] ?? false) === true)
            ->pluck('text')
            ->implode('');
        $usage = (array) ($json['usageMetadata'] ?? []);

        return new VertexTextResponse(
            model: (string) ($json['modelVersion'] ?? $model),
            text: $text,
            finishReason: $candidate['finishReason'] ?? ($json['promptFeedback']['blockReason'] ?? null),
            inputTokens: (int) ($usage['promptTokenCount'] ?? 0),
            // Thinking tokens are billed as output.
            outputTokens: (int) ($usage['candidatesTokenCount'] ?? 0) + (int) ($usage['thoughtsTokenCount'] ?? 0),
        );
    }

    /** One 16:9 image. Returns null bytes when Imagen filtered the prompt. */
    public function image(string $model, string $prompt, string $aspectRatio = '16:9'): VertexImageResponse
    {
        $response = $this->post($this->modelUrl($model, 'predict', $this->imageRegion()), [
            'instances' => [['prompt' => $prompt]],
            'parameters' => [
                'sampleCount' => 1,
                'aspectRatio' => $aspectRatio,
                'personGeneration' => 'allow_adult',
                'addWatermark' => false,
                'outputOptions' => ['mimeType' => 'image/png'],
            ],
        ]);

        $prediction = $response->json('predictions.0') ?? [];
        $data = $prediction['bytesBase64Encoded'] ?? null;

        return new VertexImageResponse(
            model: $model,
            bytes: is_string($data) ? (base64_decode($data, true) ?: null) : null,
            mimeType: (string) ($prediction['mimeType'] ?? 'image/png'),
            filteredReason: $prediction['raiFilteredReason'] ?? null,
        );
    }

    private function post(string $url, array $payload): Response
    {
        $response = Http::withToken($this->accessToken())
            ->timeout((int) config('services.vertex.timeout', 180))
            ->acceptJson()
            ->post($url, $payload);

        if ($response->failed()) {
            $message = (string) ($response->json('error.message') ?? $response->body());
            $status = (string) ($response->json('error.status') ?? '');
            $billing = $response->status() === 403 && preg_match('/billing|BILLING_DISABLED|credit/i', $message.' '.$response->body()) === 1;

            throw new VertexException(
                "Vertex AI gagal ({$response->status()} {$status}): ".mb_substr($message, 0, 300),
                billing: $billing,
                status: $response->status(),
            );
        }

        return $response;
    }

    private function modelUrl(string $model, string $method, string $region): string
    {
        $project = (string) config('services.vertex.project');

        if ($project === '') {
            throw new VertexException('GCP_PROJECT_ID belum diset dalam .env.');
        }

        $host = $region === 'global' ? 'aiplatform.googleapis.com' : "{$region}-aiplatform.googleapis.com";

        return "https://{$host}/v1/projects/{$project}/locations/{$region}/publishers/google/models/{$model}:{$method}";
    }

    private function region(): string
    {
        return (string) (config('services.vertex.region') ?: 'asia-southeast1');
    }

    private function imageRegion(): string
    {
        return (string) (config('services.vertex.image_region') ?: $this->region());
    }

    private function accessToken(): string
    {
        $static = (string) config('services.vertex.access_token');

        if ($static !== '') {
            return $static;
        }

        $key = $this->serviceAccount();

        return Cache::remember('vertex-token:'.md5($key['client_email']), now()->addMinutes(50), function () use ($key) {
            $response = Http::asForm()
                ->timeout(30)
                ->post((string) config('services.vertex.token_url'), [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $this->signedJwt($key),
                ]);

            $token = $response->json('access_token');

            if ($response->failed() || ! is_string($token) || $token === '') {
                throw new VertexException('Gagal dapatkan token Google ('.$response->status().'): '.mb_substr((string) $response->body(), 0, 200));
            }

            return $token;
        });
    }

    private function serviceAccount(): array
    {
        $encoded = (string) config('services.vertex.credentials_base64');
        $path = (string) config('services.vertex.credentials');

        if ($encoded !== '') {
            $json = (string) base64_decode($encoded, true);
        } elseif ($path !== '' && is_file($path)) {
            $json = (string) file_get_contents($path);
        } else {
            throw new VertexException('Kunci service account tiada: isi GOOGLE_CREDENTIALS_BASE64 atau GOOGLE_APPLICATION_CREDENTIALS.');
        }

        $key = json_decode($json, true);

        if (! is_array($key) || empty($key['client_email']) || empty($key['private_key'])) {
            throw new VertexException('Fail kunci service account tidak sah.');
        }

        return $key;
    }

    private function signedJwt(array $key): string
    {
        $now = time();
        $segments = [
            $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])),
            $this->base64Url(json_encode([
                'iss' => $key['client_email'],
                'scope' => self::SCOPE,
                'aud' => (string) config('services.vertex.token_url'),
                'iat' => $now,
                'exp' => $now + 3600,
            ])),
        ];

        if (! openssl_sign(implode('.', $segments), $signature, $key['private_key'], OPENSSL_ALGO_SHA256)) {
            throw new VertexException('Gagal tandatangan JWT dengan kunci service account.');
        }

        $segments[] = $this->base64Url($signature);

        return implode('.', $segments);
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
