<?php

use App\Livewire\ArticlesPage;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\CostsPage;
use App\Livewire\SitesPage;
use App\Livewire\TopicsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public sales page. Everything else (the app) stays out of search engines.
Route::view('/', 'marketing.home')->name('home');

// The sales page, the blog (written by Dyno SEO itself into public/blog) and llms.txt are
// public; the app is not. AI assistants' crawlers get the same rules, named explicitly.
Route::get('/robots.txt', function () {
    $rules = ['Allow: /$', 'Allow: /images/', 'Allow: /videos/', 'Allow: /blog/', 'Allow: /llms.txt', 'Disallow: /'];

    return response(implode("\n", [
        'User-agent: *',
        ...$rules,
        '',
        '# Pembantu AI (ChatGPT, Gemini, Perplexity, Claude)',
        'User-agent: GPTBot',
        'User-agent: OAI-SearchBot',
        'User-agent: ChatGPT-User',
        'User-agent: Google-Extended',
        'User-agent: PerplexityBot',
        'User-agent: ClaudeBot',
        'User-agent: Claude-SearchBot',
        ...$rules,
        '',
        'Sitemap: '.url('/sitemap.xml'),
        'Sitemap: '.url('/blog/sitemap.xml'),
        '',
    ]))->header('Content-Type', 'text/plain');
})->name('robots');

// llms.txt (llmstxt.org) for dynoseo.my: what Dyno SEO is, then the blog's article list,
// which Dyno SEO keeps in public/blog/llms.txt.
Route::get('/llms.txt', function () {
    $text = "# Dyno SEO\n\n"
        .'> Dyno SEO menulis dan menerbitkan artikel blog Bahasa Melayu ke website bisnes setiap 2 hari, '
        ."dengan butang WhatsApp ke bisnes di bawah setiap artikel. Oleh DynoPOS Technologies, Pasir Mas, Kelantan.\n\n"
        .'- Laman web: '.url('/')."\n"
        .'- Harga: RM'.config('dynoseo.price_myr')." sebulan setiap website\n"
        .'- Blog: '.url('/blog/')."\n";

    $blog = public_path('blog/llms.txt');
    if (is_file($blog) && preg_match('/^## Artikel\n.*?(?=\n## |\z)/sm', (string) file_get_contents($blog), $m)) {
        $text .= "\n".trim($m[0])."\n";
    }

    return response($text)->header('Content-Type', 'text/plain; charset=UTF-8');
})->name('llms');

Route::get('/sitemap.xml', fn () => response(
    '<?xml version="1.0" encoding="UTF-8"?>'."\n"
    .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n"
    .'  <url><loc>'.e(url('/')).'</loc><changefreq>weekly</changefreq><priority>1.0</priority></url>'."\n"
    .'</urlset>'."\n"
)->header('Content-Type', 'application/xml'))->name('sitemap');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', Login::class)->name('login');
    Route::get('/lupa-kata-laluan', ForgotPassword::class)->name('password.request');
    Route::get('/tukar-kata-laluan/{token}', ResetPassword::class)->name('password.reset');
});

Route::post('/keluar', function (Request $request) {
    auth()->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

// Fasa 0: Bob (platform admin) only. Customers get their own portal in Fasa 1.
Route::middleware(['auth', 'workspace', 'admin'])->group(function () {
    Route::get('/website', SitesPage::class)->name('sites');
    // Looked up inside the component (after the workspace middleware), not by route model binding.
    Route::get('/website/{siteId}/topik', TopicsPage::class)->name('topics');
    Route::get('/artikel', ArticlesPage::class)->name('articles');
    Route::get('/kos', CostsPage::class)->name('costs');
});
