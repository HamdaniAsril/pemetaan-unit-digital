<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OpdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => 'DINAS '.strtoupper(fake()->unique()->words(3, true)),
            'urutan' => fake()->numberBetween(1, 100),
        ];
    }
}
