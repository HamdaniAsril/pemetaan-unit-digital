<?php

namespace App\Models;

use App\Enums\StatusOpd;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Opd extends Model
{
    use HasFactory;

    protected $fillable = ['nama', 'singkatan', 'urutan', 'status', 'submitted_at'];

    protected $attributes = ['status' => 'belum_mulai'];

    protected function casts(): array
    {
        return [
            'status' => StatusOpd::class,
            'submitted_at' => 'datetime',
            'urutan' => 'integer',
        ];
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    /** Terkirim tetapi masih ada unit dinilai yang belum lengkap (spec bagian 6). */
    public function scopePerluDilengkapi(Builder $query): Builder
    {
        return $query->where('status', StatusOpd::Terkirim)
            ->whereHas('units', fn (Builder $u) => $u->dinilai()->where(fn (Builder $w) => $w
                ->doesntHave('answer')
                ->orWhereHas('answer', fn (Builder $a) => $a->whereNull('mandat')->orWhereNull('keluaran')->orWhereNull('sumber_daya'))));
    }
}
