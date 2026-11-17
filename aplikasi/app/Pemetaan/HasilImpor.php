<?php

namespace App\Pemetaan;

final class HasilImpor
{
    /**
     * @param  list<string>  $berbeda
     * @param  list<string>  $hilang
     */
    public function __construct(
        public int $opdBaru = 0,
        public int $unitBaru = 0,
        public array $berbeda = [],
        public array $hilang = [],
    ) {}
}
