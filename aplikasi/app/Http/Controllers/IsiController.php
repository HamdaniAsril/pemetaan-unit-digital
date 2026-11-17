<?php

namespace App\Http\Controllers;

use App\Enums\StatusOpd;
use App\Models\Setting;
use App\Models\Unit;
use App\Pemetaan\Exceptions\BelumLengkap;
use App\Pemetaan\Exceptions\JawabanTerkunci;
use App\Pemetaan\Format;
use App\Pemetaan\Indikator;
use App\Pemetaan\Penilaian;
use App\Pemetaan\TabPenilaian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IsiController extends Controller
{
    public function __construct(private Penilaian $penilaian) {}

    public function index(Request $request): View
    {
        $opd = $request->user()->opd;
        $tabs = $this->penilaian->tabs($opd)->map(fn (TabPenilaian $t) => [
            'judul' => Format::namaUnit($t->kepala->nama_unit),
            'label' => $t->label,
            'units' => $t->units()->map(fn (Unit $u) => [
                'id' => $u->id,
                'nama' => Format::namaUnit($u->nama_unit),
                'jabatan' => $u->nama_jabatan ? Format::namaUnit($u->nama_jabatan) : '',
                'kategori' => (string) $u->kategori,
                'kedalaman' => (int) ($u->kedalaman ?? 0),
                'jawaban' => collect(Indikator::KODE)->mapWithKeys(fn ($k) => [
                    $k => $u->answer?->{$k} === null ? null : ($u->answer->{$k} ? 'ya' : 'tidak'),
                ]),
            ])->values(),
        ])->values();

        return view('isi.index', [
            'opd' => $opd,
            'tabs' => $tabs,
            'terkirim' => $opd->status === StatusOpd::Terkirim,
            'progres' => $this->penilaian->progres($opd),
            'kontak' => Setting::ambil('kontak_admin'),
            'indikator' => collect(Indikator::KODE)->map(fn ($k) => [
                'id' => $k, 'label' => Indikator::label($k), 'pertanyaan' => Indikator::pertanyaan($k),
                'ringkas' => Indikator::ringkas($k), 'petunjuk' => Indikator::petunjuk($k),
            ]),
            'baruTerkirim' => session()->has('status'),
        ]);
    }

    public function jawaban(Request $request): JsonResponse
    {
        $data = $request->validate([
            'unit_id' => ['required', 'integer'],
            'indikator' => ['required', Rule::in(Indikator::KODE)],
            'nilai' => ['required', Rule::in(['ya', 'tidak'])],
        ]);
        $opd = $request->user()->opd;
        $unit = Unit::query()->dinilai()->whereKey($data['unit_id'])->where('opd_id', $opd->id)->first();
        abort_unless($unit, 403, 'Unit tidak termasuk OPD Anda.');

        try {
            $this->penilaian->simpanJawaban($unit, $data['indikator'], $data['nilai'] === 'ya', $request->user());
        } catch (JawabanTerkunci $e) {
            return response()->json(['pesan' => $e->getMessage()], 423);
        }

        return response()->json([
            'progres' => $this->penilaian->progres($opd),
            'status' => $opd->fresh()->status->value,
        ]);
    }

    public function kirim(Request $request): RedirectResponse
    {
        try {
            $this->penilaian->kirim($request->user()->opd);
        } catch (BelumLengkap|JawabanTerkunci $e) {
            return redirect('/isi')->withErrors(['kirim' => $e->getMessage()]);
        }

        return redirect('/isi')->with('status', 'Jawaban berhasil dikirim. Terima kasih.');
    }
}
