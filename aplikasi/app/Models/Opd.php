<?php

namespace App\Models;

use App\Enums\StatusOpd;
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
}
