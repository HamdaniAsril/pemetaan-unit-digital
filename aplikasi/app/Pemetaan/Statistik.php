<?php

namespace App\Pemetaan;

use App\Enums\StatusOpd;
use App\Models\Opd;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** Angka ringkasan seluruh kabupaten untuk dasbor admin. Hanya unit aktif & dinilai yang dihitung. */
final class Statistik
{
    /**
     * @return array{unit_total:int, unit_lengkap:int, opd_total:int, opd_terkirim:int,
     *               unit_semua_ya:int, unit_minimal_satu_ya:int,
     *               per_indikator: array<string, array{ya:int, tidak:int}>}
     */
    public function ringkasan(): array
    {
        $baris = DB::table('units')
            ->leftJoin('answers', 'answers.unit_id', '=', 'units.id')
            ->where('units.aktif', true)->where('units.dinilai', true)
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when answers.mandat is not null and answers.keluaran is not null and answers.sumber_daya is not null then 1 else 0 end) as lengkap')
            ->selectRaw('sum(case when answers.mandat = 1 and answers.keluaran = 1 and answers.sumber_daya = 1 then 1 else 0 end) as semua_ya')
            ->selectRaw('sum(case when answers.mandat = 1 or answers.keluaran = 1 or answers.sumber_daya = 1 then 1 else 0 end) as minimal_satu_ya')
            ->tap(function ($q) {
                foreach (Indikator::KODE as $k) {
                    $q->selectRaw("sum(case when answers.{$k} = 1 then 1 else 0 end) as {$k}_ya")
                        ->selectRaw("sum(case when answers.{$k} = 0 then 1 else 0 end) as {$k}_tidak");
                }
            })
            ->first();

        $perIndikator = [];
        foreach (Indikator::KODE as $k) {
            $perIndikator[$k] = ['ya' => (int) $baris->{"{$k}_ya"}, 'tidak' => (int) $baris->{"{$k}_tidak"}];
        }

        return [
            'unit_total' => (int) $baris->total,
            'unit_lengkap' => (int) $baris->lengkap,
            'opd_total' => Opd::query()->count(),
            'opd_terkirim' => Opd::query()->where('status', StatusOpd::Terkirim)->count(),
            'unit_semua_ya' => (int) $baris->semua_ya,
            'unit_minimal_satu_ya' => (int) $baris->minimal_satu_ya,
            'per_indikator' => $perIndikator,
        ];
    }

    /** Tambahkan kolom unit_total & unit_lengkap (unit aktif & dinilai) ke query OPD. */
    public static function denganProgres(Builder $query): Builder
    {
        $dasar = fn () => DB::table('units')->whereColumn('units.opd_id', 'opds.id')->where('units.aktif', true)->where('units.dinilai', true);

        return $query
            ->addSelect(['unit_total' => $dasar()->selectRaw('count(*)')])
            ->addSelect(['unit_lengkap' => $dasar()->join('answers', 'answers.unit_id', '=', 'units.id')
                ->whereNotNull('answers.mandat')->whereNotNull('answers.keluaran')->whereNotNull('answers.sumber_daya')
                ->selectRaw('count(*)')]);
    }
}
