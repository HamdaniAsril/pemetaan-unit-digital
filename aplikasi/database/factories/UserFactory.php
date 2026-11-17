<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Opd;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Opd,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => Role::Admin, 'opd_id' => null]);
    }

    public function untukOpd(Opd $opd): static
    {
        return $this->state(['role' => Role::Opd, 'opd_id' => $opd->id, 'name' => $opd->nama]);
    }
}
