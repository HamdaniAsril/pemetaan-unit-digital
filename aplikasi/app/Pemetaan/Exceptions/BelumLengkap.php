<?php

namespace App\Pemetaan\Exceptions;

use RuntimeException;

class BelumLengkap extends RuntimeException
{
    public function __construct(public readonly int $sisa)
    {
        parent::__construct("Masih ada {$sisa} unit yang belum lengkap.");
    }
}
