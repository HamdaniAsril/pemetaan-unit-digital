<?php

namespace Database\Factories;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class AnswerFactory extends Factory
{
    public function definition(): array
    {
        return ['unit_id' => Unit::factory()];
    }

    public function lengkap(bool $nilai = true): static
    {
        return $this->state(['mandat' => $nilai, 'keluaran' => $nilai, 'sumber_daya' => $nilai]);
    }
}
