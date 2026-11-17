<?php

use App\Enums\StatusOpd;
use App\Filament\Widgets\PerluTindakLanjut;
use App\Models\Answer;
use App\Models\Opd;
use App\Models\Unit;
use App\Models\User;
use App\Pemetaan\Statistik;
use Livewire\Livewire;

beforeEach(function () {
    // OPD A terkirim: 2 unit, satu 3×Ya, satu Ya/Tidak/Tidak
    $this->a = Opd::factory()->create(['nama' => 'DINAS A', 'status' => StatusOpd::Terkirim]);
    $ka = Unit::factory()->for($this->a)->create(['level' => 0]);
    $ba = Unit::factory()->anakDari($ka)->create();
    Answer::factory()->create(['unit_id' => $ka->id, 'mandat' => true, 'keluaran' => true, 'sumber_daya' => true]);
    Answer::factory()->create(['unit_id' => $ba->id, 'mandat' => true, 'keluaran' => false, 'sumber_daya' => false]);
    // unit nonaktif dengan jawaban: tidak dihitung
    $non = Unit::factory()->anakDari($ka)->create(['aktif' => false]);
    Answer::factory()->lengkap()->create(['unit_id' => $non->id]);

    // OPD B mengisi: 2 unit, satu lengkap (semua Tidak), satu kosong
    $this->b = Opd::factory()->create(['nama' => 'DINAS B', 'status' => StatusOpd::Mengisi]);
    $kb = Unit::factory()->for($this->b)->create(['level' => 0]);
    Unit::factory()->anakDari($kb)->create();
    Answer::factory()->lengkap(false)->create(['unit_id' => $kb->id]);

    // OPD C belum mulai: 1 unit
    $this->c = Opd::factory()->create(['nama' => 'DINAS C']);
    Unit::factory()->for($this->c)->create(['level' => 0]);
});

test('statistik ringkasan menghitung hanya unit aktif dan dinilai', function () {
    $s = app(Statistik::class)->ringkasan();

    expect($s['unit_total'])->toBe(5)
        ->and($s['unit_lengkap'])->toBe(3)
        ->and($s['opd_total'])->toBe(3)
        ->and($s['opd_terkirim'])->toBe(1)
        ->and($s['unit_semua_ya'])->toBe(1)
        ->and($s['unit_minimal_satu_ya'])->toBe(2)
        ->and($s['per_indikator'])->toBe([
            'mandat' => ['ya' => 2, 'tidak' => 1],
            'keluaran' => ['ya' => 1, 'tidak' => 2],
            'sumber_daya' => ['ya' => 1, 'tidak' => 2],
        ]);
});

test('dasbor menampilkan ringkasan hasil, bukan daftar status opd', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin')
        ->assertOk()
        ->assertSee('Unit terisi lengkap')
        ->assertSee('3/5')
        ->assertSee('Memenuhi ketiga indikator')
        ->assertSee('Jawaban per indikator')
        ->assertSee('Perlu ditindaklanjuti')
        ->assertDontSeeLivewire(\App\Filament\Widgets\RingkasanStatus::class);
});

test('tabel tindak lanjut hanya berisi opd yang belum selesai, progres terendah dulu', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(PerluTindakLanjut::class)
        ->assertCanSeeTableRecords([$this->c, $this->b], inOrder: true)
        ->assertCanNotSeeTableRecords([$this->a]);
});

test('motif butir progres tampil di tabel tindak lanjut', function () {
    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(PerluTindakLanjut::class)->assertSeeHtml('class="butir-progres"');
});
