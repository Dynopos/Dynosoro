<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/** Create (or promote) the platform admin with their own internal workspace. */
class MakeAdmin extends Command
{
    protected $signature = 'dynoseo:admin
        {email : E-mel admin}
        {--name=Bob : Nama admin}
        {--business=DynoPOS Technologies : Nama workspace admin}
        {--password= : Kata laluan (untuk Forge Commands yang tidak boleh jawab soalan)}';

    protected $description = 'Cipta atau naikkan akaun admin platform (Bob)';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $password = (string) ($this->option('password') ?: ($this->input->isInteractive() ? $this->secret('Kata laluan (min 8 aksara)') : ''));

            if (mb_strlen($password) < 8) {
                $this->error('Kata laluan mesti sekurang-kurangnya 8 aksara. Guna --password="..." dari Forge Commands.');

                return self::FAILURE;
            }

            $user = DB::transaction(function () use ($email, $password) {
                $workspace = Workspace::query()->where('plan', 'dalaman')->oldest('id')->first()
                    ?? Workspace::query()->create([
                        'name' => (string) $this->option('business'),
                        'slug' => Workspace::uniqueSlug((string) $this->option('business')),
                        'plan' => 'dalaman',
                    ]);

                return User::query()->create([
                    'workspace_id' => $workspace->id,
                    'role' => 'owner',
                    'name' => (string) $this->option('name'),
                    'email' => $email,
                    'password' => $password,
                ]);
            });
        }

        $user->forceFill(['is_admin' => true])->save();

        if ($user->workspace && $user->workspace->plan !== 'dalaman') {
            $user->workspace->forceFill(['plan' => 'dalaman'])->save();
        }

        $this->info("Admin: {$user->email} (workspace #{$user->workspace_id}).");

        return self::SUCCESS;
    }
}
