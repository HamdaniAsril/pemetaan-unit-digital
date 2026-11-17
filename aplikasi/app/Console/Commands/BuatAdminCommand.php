<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;

class BuatAdminCommand extends Command
{
    protected $signature = 'pemetaan:buat-admin {username}';

    protected $description = 'Buat akun admin (kata sandi ditanyakan).';

    public function handle(): int
    {
        $username = strtolower(trim($this->argument('username')));
        if (User::query()->where('username', $username)->exists()) {
            $this->error("Username {$username} sudah dipakai.");

            return self::FAILURE;
        }
        $password = $this->secret('Kata sandi (minimal 8 karakter)');
        if (strlen((string) $password) < 8 || $password !== $this->secret('Ulangi kata sandi')) {
            $this->error('Kata sandi terlalu pendek atau tidak sama.');

            return self::FAILURE;
        }
        User::query()->create(['name' => $username, 'username' => $username, 'password' => $password, 'role' => Role::Admin]);
        $this->info("Admin {$username} dibuat.");

        return self::SUCCESS;
    }
}
