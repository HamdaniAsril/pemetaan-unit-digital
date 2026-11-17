<?php

namespace Database\Factories;

use App\Models\Opd;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    public function definition(): array
    {
        $nama = 'BIDANG '.strtoupper(fake()->unique()->words(2, true));

        return [
            'opd_id' => Opd::factory(),
            'level' => 1,
            'nama_unit' => $nama,
            'nama_jabatan' => 'KEPALA '.$nama,
            'kategori' => 'Bidang',
            'urutan' => fake()->numberBetween(1, 1000),
        ];
    }

    public function anakDari(Unit $induk): static
    {
        return $this->state(['opd_id' => $induk->opd_id, 'parent_id' => $induk->id, 'level' => $induk->level + 1]);
    }
}
