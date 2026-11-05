<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleBusinessException;
use App\Models\GoogleConnection;
use App\Services\Google\GoogleBusinessClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Connect the admin's Google account (Manager of the customers' Business Profiles) to this
 * workspace. The refresh token is stored encrypted; the state parameter guards the callback.
 */
class GoogleConnectionController extends Controller
{
    public function connect(Request $request, GoogleBusinessClient $google): RedirectResponse
    {
        abort_unless($google->configured(), 404);

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($google->authUrl($state));
    }

    public function callback(Request $request, GoogleBusinessClient $google): RedirectResponse
    {
        abort_unless($google->configured(), 404);

        $expected = (string) $request->session()->pull('google_oauth_state');

        if ($expected === '' || ! hash_equals($expected, (string) $request->query('state'))) {
            return $this->back('Sambungan Google tidak sah (state). Cuba sekali lagi.', 'danger');
        }

        if ($request->filled('error') || ! $request->filled('code')) {
            return $this->back('Sambungan Google dibatalkan.', 'warning');
        }

        try {
            $tokens = $google->exchangeCode((string) $request->query('code'));
        } catch (GoogleBusinessException $e) {
            return $this->back($e->getMessage(), 'danger');
        }

        $connection = GoogleConnection::query()->firstOrNew();
        $connection->fill([
            'email' => $tokens['email'],
            'refresh_token' => $tokens['refresh_token'],
            'connected_at' => now(),
            'last_error' => null,
        ])->save();

        return $this->back('Google disambung'.($tokens['email'] ? ' ('.$tokens['email'].')' : '').'. Pilih profil Google untuk setiap website dalam borang Edit.');
    }

    public function disconnect(): RedirectResponse
    {
        GoogleConnection::query()->delete();

        return $this->back('Google diputuskan. Artikel baru tidak akan dipost ke Google.');
    }

    private function back(string $message, string $type = 'success'): RedirectResponse
    {
        return redirect()->route('sites')->with('google_flash', [$message, $type]);
    }
}
