<?php

namespace App\Enums;

enum StatusOpd: string
{
    case BelumMulai = 'belum_mulai';
    case Mengisi = 'mengisi';
    case Terkirim = 'terkirim';

    public function label(): string
    {
        return match ($this) {
            self::BelumMulai => 'Belum mulai',
            self::Mengisi => 'Mengisi',
            self::Terkirim => 'Terkirim',
        };
    }
}
