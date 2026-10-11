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

// The app is private: nothing here should be indexed.
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /\n")->header('Content-Type', 'text/plain'));

Route::redirect('/', '/website');

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
