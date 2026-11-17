<?php

namespace App\Pemetaan\Exceptions;

use RuntimeException;

class TidakAdaUnit extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Belum ada unit yang bisa dinilai. Hubungi admin.');
    }
}
