<?php

namespace App\Pemetaan;

use App\Models\Unit;
use Illuminate\Support\Collection;

final class TabPenilaian
{
    /**
     * @param  Collection<int, Unit>  $anak  turunan kepala (pre-order), masing-masing punya atribut `kedalaman`
     */
    public function __construct(
        public Unit $kepala,
        public Collection $anak,
        public string $label,
    ) {}

    /** @return Collection<int, Unit> kepala diikuti anak */
    public function units(): Collection
    {
        return collect([$this->kepala])->concat($this->anak)->values();
    }
}
