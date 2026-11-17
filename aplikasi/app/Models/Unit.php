<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'opd_id', 'parent_id', 'level', 'nama_unit', 'nama_impor', 'nama_jabatan',
        'kategori', 'eselon', 'urutan', 'dinilai', 'aktif',
    ];

    protected $attributes = ['dinilai' => true, 'aktif' => true];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'urutan' => 'integer',
            'dinilai' => 'boolean',
            'aktif' => 'boolean',
        ];
    }

    public function opd(): BelongsTo
    {
        return $this->belongsTo(Opd::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id');
    }

    public function answer(): HasOne
    {
        return $this->hasOne(Answer::class);
    }

    /** Unit yang tampil di form dan dihitung: aktif dan dinilai. */
    public function scopeDinilai(Builder $query): Builder
    {
        return $query->where('aktif', true)->where('dinilai', true);
    }
}
