<?php

namespace App\Pemetaan\Exceptions;

use RuntimeException;

class JawabanTerkunci extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Jawaban sudah dikirim dan terkunci.');
    }
}
