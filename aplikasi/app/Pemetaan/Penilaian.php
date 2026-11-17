<?php

namespace App\Pemetaan;

use App\Enums\StatusOpd;
use App\Models\Answer;
use App\Models\Opd;
use App\Models\Unit;
use App\Models\User;
use App\Pemetaan\Exceptions\BelumLengkap;
use App\Pemetaan\Exceptions\JawabanTerkunci;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Logika inti penilaian: susunan tab, progres, simpan, kirim, kunci (spec bagian 5).
 * Hanya unit aktif & dinilai yang ikut.
 */
final class Penilaian
{
    /**
     * Setiap unit dinilai muncul tepat satu kali, sehingga jumlah unit di tab selalu sama dengan progres.total,
     * termasuk bila admin membuat struktur yang tidak biasa (kepala tidak dinilai, induk melingkar, level tertukar).
     *
     * @return Collection<int, TabPenilaian>
     */
    public function tabs(Opd $opd): Collection
    {
        $units = $this->unitDinilai($opd);
        if ($units->isEmpty()) {
            return collect();
        }

        $anakDari = $units->groupBy(fn (Unit $u) => (int) $u->parent_id);
        $ditempatkan = [];
        $kepala = $units->firstWhere('level', 0);
        if ($kepala) {
            $ditempatkan[$kepala->id] = true;
        }

        $tabs = [];
        foreach ($units->where('level', 1) as $level1) {
            if (isset($ditempatkan[$level1->id])) {
                continue;   // sudah tampil sebagai turunan unit level 1 lain
            }
            $ditempatkan[$level1->id] = true;
            $anak = $this->turunan($level1, $anakDari, 1, $ditempatkan);
            $tabs[] = new TabPenilaian($level1, $anak, (string) $level1->kategori);
        }

        // Unit yang belum tertampung (induk tidak dinilai/tidak ada, atau induk melingkar) masuk tab pertama.
        $sisa = $units->reject(fn (Unit $u) => isset($ditempatkan[$u->id]))->values();
        $idSisa = $sisa->pluck('id')->flip();
        $lain = collect();
        $tempatkan = function (Unit $u) use (&$lain, $anakDari, &$ditempatkan) {
            $ditempatkan[$u->id] = true;
            $u->kedalaman = 1;
            $lain->push($u);
            $lain = $lain->concat($this->turunan($u, $anakDari, 2, $ditempatkan));
        };
        foreach ($sisa as $u) {
            if (! isset($ditempatkan[$u->id]) && ! $idSisa->has($u->parent_id)) {
                $tempatkan($u);
            }
        }
        foreach ($sisa as $u) {
            if (! isset($ditempatkan[$u->id])) {
                $tempatkan($u);   // siklus induk
            }
        }

        if ($kepala) {
            array_unshift($tabs, new TabPenilaian($kepala, $lain->values(), 'OPD'));
        } elseif ($lain->isNotEmpty()) {
            $pertama = $lain->shift();
            $pertama->kedalaman = 0;
            array_unshift($tabs, new TabPenilaian($pertama, $lain->values(), 'Unit lainnya'));
        }

        return collect($tabs);
    }

    /** @return array{terjawab: int, total: int} */
    public function progres(Opd $opd): array
    {
        $units = $this->unitDinilai($opd);

        return [
            'terjawab' => $units->filter(fn (Unit $u) => $u->answer?->lengkap())->count(),
            'total' => $units->count(),
        ];
    }

    public function simpanJawaban(Unit $unit, string $indikator, bool $nilai, User $oleh): Answer
    {
        if (! Indikator::valid($indikator)) {
            throw new InvalidArgumentException("Indikator tidak dikenal: {$indikator}");
        }
        $opd = $unit->opd()->first();
        if ($opd->status === StatusOpd::Terkirim) {
            throw new JawabanTerkunci;
        }

        // Upsert atomik: aman untuk klik cepat/berulang.
        $sekarang = now();
        Answer::query()->upsert(
            [['unit_id' => $unit->id, $indikator => $nilai, 'updated_by' => $oleh->id, 'created_at' => $sekarang, 'updated_at' => $sekarang]],
            ['unit_id'],
            [$indikator, 'updated_by', 'updated_at'],
        );

        Opd::query()->whereKey($opd->id)->where('status', StatusOpd::BelumMulai)->update(['status' => StatusOpd::Mengisi]);

        return Answer::query()->where('unit_id', $unit->id)->firstOrFail();
    }

    public function kirim(Opd $opd): void
    {
        if ($opd->status === StatusOpd::Terkirim) {
            throw new JawabanTerkunci;
        }
        ['terjawab' => $terjawab, 'total' => $total] = $this->progres($opd);
        if ($terjawab < $total) {
            throw new BelumLengkap($total - $terjawab);
        }
        $opd->update(['status' => StatusOpd::Terkirim, 'submitted_at' => now()]);
    }

    public function bukaKunci(Opd $opd): void
    {
        $opd->update(['status' => StatusOpd::Mengisi, 'submitted_at' => null]);
    }

    public function perluDilengkapi(Opd $opd): bool
    {
        if ($opd->status !== StatusOpd::Terkirim) {
            return false;
        }
        $p = $this->progres($opd);

        return $p['terjawab'] < $p['total'];
    }

    /** @return Collection<int, Unit> */
    private function unitDinilai(Opd $opd): Collection
    {
        return $opd->units()->dinilai()->with('answer')->orderBy('urutan')->orderBy('id')->get();
    }

    /** Pre-order turunan $induk dengan atribut kedalaman. */
    private function turunan(Unit $induk, Collection $anakDari, int $kedalaman, array &$ditempatkan): Collection
    {
        $hasil = collect();
        foreach ($anakDari->get($induk->id, collect()) as $anak) {
            if (isset($ditempatkan[$anak->id])) {
                continue;
            }
            $ditempatkan[$anak->id] = true;
            $anak->kedalaman = $kedalaman;
            $hasil->push($anak);
            $hasil = $hasil->concat($this->turunan($anak, $anakDari, $kedalaman + 1, $ditempatkan));
        }

        return $hasil;
    }
}
