<?php

use App\Enums\StatusOpd;
use App\Exports\RekapExport;
use App\Filament\Resources\OpdResource\Pages\ListOpds;
use App\Models\Answer;
use App\Models\Opd;
use App\Models\Unit;
use App\Models\User;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;

function bacaRekap(): array
{
    $path = tempnam(sys_get_temp_dir(), 'rekap').'.xlsx';
    app(RekapExport::class)->simpan($path);
    $book = IOFactory::load($path);

    return [
        'ringkasan' => $book->getSheetByName('Ringkasan OPD')->toArray(),
        'unit' => $book->getSheetByName('Jawaban per Unit')->toArray(),
    ];
}

beforeEach(function () {
    $this->a = Opd::factory()->create(['nama' => 'DINAS A', 'urutan' => 1, 'status' => StatusOpd::Terkirim, 'submitted_at' => '2026-11-20 10:00:00']);
    $this->ka = Unit::factory()->for($this->a)->create(['level' => 0, 'nama_unit' => 'DINAS A', 'urutan' => 1, 'kategori' => 'Dinas', 'eselon' => 'II.b']);
    $this->ba = Unit::factory()->anakDari($this->ka)->create(['nama_unit' => 'BIDANG A1', 'urutan' => 2, 'kategori' => 'Bidang']);
    Answer::factory()->create(['unit_id' => $this->ka->id, 'mandat' => true, 'keluaran' => true, 'sumber_daya' => false]);
    Answer::factory()->create(['unit_id' => $this->ba->id, 'mandat' => false, 'keluaran' => null, 'sumber_daya' => true]);

    $this->b = Opd::factory()->create(['nama' => 'DINAS B', 'urutan' => 2]);
    Unit::factory()->for($this->b)->create(['level' => 0, 'nama_unit' => 'DINAS B', 'urutan' => 1]);
});

test('rekap memuat semua opd termasuk belum mulai', function () {
    $r = bacaRekap()['ringkasan'];

    expect($r[0])->toBe(['No', 'OPD', 'Status', 'Unit Dinilai', 'Unit Lengkap', 'Tanggal Kirim', 'Ya Mandat Formal', 'Ya Keluaran Utama', 'Ya Sumber Daya Khusus'])
        ->and($r)->toHaveCount(3)
        ->and($r[1])->toEqual([1, 'DINAS A', 'Terkirim', 2, 1, '20/11/2026 10:00', 1, 1, 1])
        ->and($r[2])->toEqual([2, 'DINAS B', 'Belum mulai', 1, 0, null, 0, 0, 0]);
});

test('rekap jawaban per unit menulis Ya/Tidak/kosong dan jumlah ya', function () {
    $u = bacaRekap()['unit'];

    expect($u[0])->toBe(['OPD', 'Level', 'Unit Organisasi', 'Jabatan', 'Kategori', 'Eselon', 'Induk', 'Mandat Formal', 'Keluaran Utama', 'Sumber Daya Khusus', 'Jumlah Ya'])
        ->and($u[1][2])->toBe('DINAS A')
        ->and(array_slice($u[1], 7))->toEqual(['Ya', 'Ya', 'Tidak', 2])
        ->and($u[2][2])->toBe('    BIDANG A1')
        ->and($u[2][6])->toBe('DINAS A')
        ->and(array_slice($u[2], 7))->toEqual(['Tidak', null, 'Ya', 1])
        ->and($u[3][0])->toBe('DINAS B')
        ->and(array_slice($u[3], 7))->toEqual([null, null, null, 0]);
});

test('rekap mengecualikan unit nonaktif dan tidak dinilai', function () {
    $this->ba->update(['aktif' => false]);
    Unit::factory()->anakDari($this->ka)->create(['nama_unit' => 'STAF AHLI', 'dinilai' => false]);

    $data = bacaRekap();
    expect(collect($data['unit'])->pluck(2)->all())->not->toContain('    BIDANG A1')->not->toContain('    STAF AHLI')
        ->and($data['ringkasan'][1][3])->toEqual(1);
});

test('hanya admin bisa unduh rekap dan backup', function () {
    $opdUser = User::factory()->untukOpd($this->a)->create();
    $this->actingAs($opdUser)->get('/admin/unduh/rekap')->assertForbidden();
    $this->actingAs($opdUser)->get('/admin/unduh/backup')->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())->get('/admin/unduh/rekap')
        ->assertOk()->assertDownload();
});

test('aksi buat semua akun membuat akun untuk opd tanpa akun', function () {
    $this->actingAs(User::factory()->admin()->create());
    Livewire::test(ListOpds::class)->callAction('buatSemuaAkun')->assertFileDownloaded('akun-opd.xlsx');

    expect($this->a->fresh()->user)->not->toBeNull()
        ->and($this->b->fresh()->user)->not->toBeNull();
});
