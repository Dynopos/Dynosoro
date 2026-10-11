<?php

use App\Services\Ai\VertexClient;
use Illuminate\Support\Facades\Http;

it('signs a service account JWT and uses the returned token', function () {
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $pem);
    $public = openssl_pkey_get_details($key)['key'];
    $file = tempnam(sys_get_temp_dir(), 'sa');
    file_put_contents($file, json_encode(['client_email' => 'dynoseo@test.iam.gserviceaccount.com', 'private_key' => $pem]));

    config(['services.vertex.access_token' => '', 'services.vertex.credentials' => $file, 'cache.default' => 'array']);

    Http::fake([
        'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.signed']),
        '*aiplatform.googleapis.com/*' => Http::response(geminiReply(['ok' => true])),
    ]);

    $response = app(VertexClient::class)->generate('gemini-2.5-flash', 'sys', 'user', 100);

    expect($response->json())->toBe(['ok' => true]);

    Http::assertSent(function ($request) use ($public) {
        if (! str_contains($request->url(), 'oauth2')) {
            return false;
        }
        [$header, $claims, $signature] = explode('.', $request['assertion']);
        $decoded = json_decode(base64_decode(strtr($claims, '-_', '+/')), true);

        return $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
            && $decoded['iss'] === 'dynoseo@test.iam.gserviceaccount.com'
            && $decoded['scope'] === 'https://www.googleapis.com/auth/cloud-platform'
            && openssl_verify("{$header}.{$claims}", base64_decode(strtr($signature, '-_', '+/')), $public, OPENSSL_ALGO_SHA256) === 1;
    });
    Http::assertSent(fn ($r) => str_contains($r->url(), 'asia-southeast1-aiplatform.googleapis.com/v1/projects/test-project/locations/asia-southeast1/publishers/google/models/gemini-2.5-flash:generateContent')
        && $r->hasHeader('Authorization', 'Bearer ya29.signed'));

    unlink($file);
});

it('counts thinking tokens as output', function () {
    Http::fake(['*aiplatform.googleapis.com/*' => Http::response([
        'candidates' => [['content' => ['parts' => [['text' => 'hidden', 'thought' => true], ['text' => '{"a":1}']]], 'finishReason' => 'STOP']],
        'usageMetadata' => ['promptTokenCount' => 10, 'candidatesTokenCount' => 20, 'thoughtsTokenCount' => 30],
    ])]);

    $response = app(VertexClient::class)->generate('gemini-2.5-flash', 's', 'u', 100);

    expect($response->outputTokens)->toBe(50)->and($response->json())->toBe(['a' => 1]);
});
