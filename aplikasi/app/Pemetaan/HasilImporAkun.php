<?php

namespace App\Pemetaan;

final class HasilImporAkun
{
    /** @param  list<string>  $dilewati  baris yang tidak diproses, beserta alasannya */
    public function __construct(
        public int $dibuat = 0,
        public int $diperbarui = 0,
        public array $dilewati = [],
    ) {}
}
