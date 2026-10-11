<?php

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('creates the admin with an internal workspace', function () {
    $this->artisan('dynoseo:admin', ['email' => 'Bob@Example.com', '--password' => 'rahsia1234'])->assertSuccessful();

    $user = User::query()->where('email', 'bob@example.com')->sole();
    expect($user->is_admin)->toBeTrue()
        ->and(Hash::check('rahsia1234', $user->password))->toBeTrue()
        ->and($user->workspace->plan)->toBe('dalaman');
});

it('resets the password of an existing admin', function () {
    $this->artisan('dynoseo:admin', ['email' => 'bob@example.com', '--password' => 'pertama123'])->assertSuccessful();
    $this->artisan('dynoseo:admin', ['email' => 'bob@example.com', '--password' => 'keduabaru99'])
        ->expectsOutputToContain('Kata laluan dikemas kini')
        ->assertSuccessful();

    $user = User::query()->where('email', 'bob@example.com')->sole();
    expect(Hash::check('keduabaru99', $user->password))->toBeTrue();

    $this->post('/keluar');
    Livewire::test(Login::class)
        ->set('email', 'bob@example.com')
        ->set('password', 'keduabaru99')
        ->call('login')
        ->assertRedirect(route('sites'));
});

it('refuses a short password', function () {
    $this->artisan('dynoseo:admin', ['email' => 'bob@example.com', '--password' => 'pendek'])->assertFailed();

    expect(User::query()->where('email', 'bob@example.com')->exists())->toBeFalse();
});
