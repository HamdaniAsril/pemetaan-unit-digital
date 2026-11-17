<?php

namespace App\Models;

use App\Pemetaan\Indikator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = ['unit_id', 'mandat', 'keluaran', 'sumber_daya', 'updated_by'];

    protected function casts(): array
    {
        return [
            'mandat' => 'boolean',
            'keluaran' => 'boolean',
            'sumber_daya' => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function lengkap(): bool
    {
        foreach (Indikator::KODE as $kode) {
            if ($this->{$kode} === null) {
                return false;
            }
        }

        return true;
    }
}
