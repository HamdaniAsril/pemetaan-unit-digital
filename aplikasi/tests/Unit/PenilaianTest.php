<?php

use App\Enums\StatusOpd;
use App\Models\Answer;
use App\Models\Opd;
use App\Models\Unit;
use App\Models\User;
use App\Pemetaan\Exceptions\BelumLengkap;
use App\Pemetaan\Exceptions\JawabanTerkunci;
use App\Pemetaan\Penilaian;

/** OPD: kepala → Sekretariat (subbag1, subbag2), Bidang A (subbid1). */
function opdContoh(): array
{
    $opd = Opd::factory()->create();
    $kepala = Unit::factory()->for($opd)->create(['level' => 0, 'nama_unit' => 'DINAS', 'urutan' => 1, 'kategori' => 'Dinas']);
    $sekre = Unit::factory()->anakDari($kepala)->create(['nama_unit' => 'SEKRETARIAT', 'urutan' => 2, 'kategori' => 'Sekretariat Dinas']);
    $sb1 = Unit::factory()->anakDari($sekre)->create(['nama_unit' => 'SUBBAG 1', 'urutan' => 3]);
    $sb2 = Unit::factory()->anakDari($sekre)->create(['nama_unit' => 'SUBBAG 2', 'urutan' => 4]);
    $bidA = Unit::factory()->anakDari($kepala)->create(['nama_unit' => 'BIDANG A', 'urutan' => 5]);
    $sbd = Unit::factory()->anakDari($bidA)->create(['nama_unit' => 'SUBBID 1', 'urutan' => 6]);

    return compact('opd', 'kepala', 'sekre', 'sb1', 'sb2', 'bidA', 'sbd');
}

function lengkapi(Unit ...$units): void
{
    foreach ($units as $u) {
        Answer::factory()->lengkap()->create(['unit_id' => $u->id]);
    }
}

beforeEach(function () {
    $this->svc = app(Penilaian::class);
    $this->user = User::factory()->create();
});

test('tab disusun opd lalu per unit level 1 dengan turunannya', function () {
    $c = opdContoh();
    $tabs = $this->svc->tabs($c['opd']);

    expect($tabs)->toHaveCount(3)
        ->and($tabs[0]->kepala->is($c['kepala']))->toBeTrue()
        ->and($tabs[0]->label)->toBe('OPD')
        ->and($tabs[0]->anak)->toHaveCount(0)
        ->and($tabs[1]->units()->pluck('nama_unit')->all())->toBe(['SEKRETARIAT', 'SUBBAG 1', 'SUBBAG 2'])
        ->and($tabs[1]->label)->toBe('Sekretariat Dinas')
        ->and($tabs[1]->anak->first()->kedalaman)->toBe(1)
        ->and($tabs[2]->units()->pluck('nama_unit')->all())->toBe(['BIDANG A', 'SUBBID 1']);
});

test('staf ahli tidak menjadi tab dan turunan unit tidak dinilai pindah ke tab opd', function () {
    $c = opdContoh();
    $staf = Unit::factory()->anakDari($c['kepala'])->create(['nama_unit' => 'STAF AHLI', 'dinilai' => false, 'urutan' => 7]);
    Unit::factory()->anakDari($staf)->create(['nama_unit' => 'YATIM', 'urutan' => 8]);

    $tabs = $this->svc->tabs($c['opd']);

    expect($tabs)->toHaveCount(3)
        ->and($tabs[0]->anak->pluck('nama_unit')->all())->toBe(['YATIM'])
        ->and($tabs->flatMap->units()->pluck('nama_unit'))->not->toContain('STAF AHLI');
});

test('level 3 ikut di tab induk level 1 dengan kedalaman 2', function () {
    $c = opdContoh();
    $sub3 = Unit::factory()->anakDari($c['sb1'])->create(['nama_unit' => 'SUBBAG L3', 'urutan' => 9]);

    $tab = $this->svc->tabs($c['opd'])[1];

    expect($tab->units()->pluck('nama_unit')->all())->toBe(['SEKRETARIAT', 'SUBBAG 1', 'SUBBAG L3', 'SUBBAG 2'])
        ->and($tab->anak->firstWhere('id', $sub3->id)->kedalaman)->toBe(2);
});

test('unit nonaktif tidak tampil dan tidak dihitung', function () {
    $c = opdContoh();
    $c['sbd']->update(['aktif' => false]);
    lengkapi($c['sbd'], $c['kepala']);

    expect($this->svc->progres($c['opd']))->toBe(['terjawab' => 1, 'total' => 5])
        ->and($this->svc->tabs($c['opd'])[2]->units()->pluck('nama_unit')->all())->toBe(['BIDANG A']);
});

test('simpan jawaban membuat answer dan mengubah status ke mengisi', function () {
    $c = opdContoh();
    $a = $this->svc->simpanJawaban($c['sb1'], 'mandat', true, $this->user);

    expect($a->mandat)->toBeTrue()
        ->and($a->keluaran)->toBeNull()
        ->and($a->updated_by)->toBe($this->user->id)
        ->and($c['opd']->fresh()->status)->toBe(StatusOpd::Mengisi);
});

test('simpan jawaban idempoten', function () {
    $c = opdContoh();
    $this->svc->simpanJawaban($c['sb1'], 'mandat', true, $this->user);
    $this->svc->simpanJawaban($c['sb1'], 'mandat', true, $this->user);
    $this->svc->simpanJawaban($c['sb1'], 'mandat', false, $this->user);

    expect(Answer::count())->toBe(1)
        ->and(Answer::first()->mandat)->toBeFalse();
});

test('simpan jawaban ditolak bila terkirim', function () {
    $c = opdContoh();
    $c['opd']->update(['status' => StatusOpd::Terkirim]);
    $this->svc->simpanJawaban($c['sb1'], 'mandat', true, $this->user);
})->throws(JawabanTerkunci::class);

test('simpan jawaban indikator tidak dikenal', function () {
    $c = opdContoh();
    $this->svc->simpanJawaban($c['sb1'], 'lain', true, $this->user);
})->throws(InvalidArgumentException::class);

test('kirim ditolak bila ada unit dinilai yang belum lengkap', function () {
    $c = opdContoh();
    lengkapi($c['kepala'], $c['sekre'], $c['sb1'], $c['sb2'], $c['bidA']);

    expect(fn () => $this->svc->kirim($c['opd']))->toThrow(BelumLengkap::class)
        ->and($c['opd']->fresh()->status)->toBe(StatusOpd::BelumMulai);
});

test('kirim berhasil mengunci dan mencatat waktu', function () {
    $c = opdContoh();
    lengkapi($c['kepala'], $c['sekre'], $c['sb1'], $c['sb2'], $c['bidA'], $c['sbd']);

    $this->svc->kirim($c['opd']);

    $opd = $c['opd']->fresh();
    expect($opd->status)->toBe(StatusOpd::Terkirim)
        ->and($opd->submitted_at)->not->toBeNull();
    expect(fn () => $this->svc->kirim($opd))->toThrow(JawabanTerkunci::class);
});

test('buka kunci mengembalikan ke mengisi', function () {
    $c = opdContoh();
    $c['opd']->update(['status' => StatusOpd::Terkirim, 'submitted_at' => now()]);

    $this->svc->bukaKunci($c['opd']);

    expect($c['opd']->fresh()->status)->toBe(StatusOpd::Mengisi)
        ->and($c['opd']->fresh()->submitted_at)->toBeNull();
});

test('perlu dilengkapi bila unit baru ditambah setelah terkirim', function () {
    $c = opdContoh();
    lengkapi($c['kepala'], $c['sekre'], $c['sb1'], $c['sb2'], $c['bidA'], $c['sbd']);
    $this->svc->kirim($c['opd']);
    expect($this->svc->perluDilengkapi($c['opd']->fresh()))->toBeFalse();

    Unit::factory()->anakDari($c['bidA'])->create(['nama_unit' => 'BARU']);

    expect($this->svc->perluDilengkapi($c['opd']->fresh()))->toBeTrue();
});

test('opd tanpa unit level 0 tetap aman', function () {
    $opd = Opd::factory()->create();
    expect($this->svc->tabs($opd))->toHaveCount(0)
        ->and($this->svc->progres($opd))->toBe(['terjawab' => 0, 'total' => 0]);
});

function semuaUnitTab($tabs): array
{
    return $tabs->flatMap->units()->pluck('id')->all();
}

test('kepala tidak dinilai: unit lain tetap tampil dan jumlahnya sama dengan progres', function () {
    $c = opdContoh();
    $c['kepala']->update(['dinilai' => false]);

    $ids = semuaUnitTab($this->svc->tabs($c['opd']));
    expect($ids)->toHaveCount(5)
        ->and(count($ids))->toBe($this->svc->progres($c['opd'])['total']);
});

test('unit dengan induk melingkar tetap tampil di tab opd', function () {
    $c = opdContoh();
    $a = Unit::factory()->for($c['opd'])->create(['level' => 2, 'nama_unit' => 'LOOP A']);
    $b = Unit::factory()->for($c['opd'])->create(['level' => 2, 'nama_unit' => 'LOOP B', 'parent_id' => $a->id]);
    $a->update(['parent_id' => $b->id]);

    $ids = semuaUnitTab($this->svc->tabs($c['opd']));
    expect($ids)->toContain($a->id)->toContain($b->id)
        ->and(count($ids))->toBe($this->svc->progres($c['opd'])['total']);
});

test('unit level 1 di bawah unit level 1 lain tidak tampil dua kali', function () {
    $c = opdContoh();
    $c['bidA']->update(['parent_id' => $c['sekre']->id]);

    $ids = semuaUnitTab($this->svc->tabs($c['opd']));
    expect($ids)->toHaveCount(count(array_unique($ids)))
        ->and(count($ids))->toBe(6);
});
