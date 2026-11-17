<x-filament-panels::page>
    <style>
        .lj-ringkas { color: var(--tinta-2); }
        .lj-ringkas strong { color: var(--tinta); }
        .lj-daftar { display: grid; }
        .lj-kepala, .lj-baris { display: grid; grid-template-columns: minmax(0, 1fr) repeat(3, 8.5rem); gap: .75rem; align-items: center; padding: .65rem 0; }
        .lj-kepala { color: var(--tinta-2); font-size: var(--teks-kecil); font-weight: 700; border-bottom: 1px solid var(--garis); }
        .lj-kepala span:not(:first-child) { text-align: center; }
        .lj-baris + .lj-baris { border-top: 1px solid var(--garis); }
        .lj-nama { min-width: 0; overflow-wrap: anywhere; }
        .lj-nama.utama { font-weight: 700; }
        .lj-sel { display: flex; justify-content: center; }
        .lj-sel .label { display: none; }
        .lj-nilai { display: inline-flex; justify-content: center; min-width: 4rem; padding: .15rem .6rem; border-radius: 999px; font-weight: 700; font-size: .875rem; }
        .lj-ya { background: var(--ya-latar); color: var(--ya); }
        .lj-tidak { background: var(--tidak-latar); color: var(--tidak); }
        .lj-kosong { color: var(--tinta-2); }
        @media (max-width: 767px) {
            .lj-kepala { display: none; }
            .lj-baris { grid-template-columns: repeat(3, minmax(0, 1fr)); row-gap: .4rem; }
            .lj-nama { grid-column: 1 / -1; }
            .lj-sel { flex-direction: column; align-items: flex-start; gap: .15rem; }
            .lj-sel .label { display: block; color: var(--tinta-2); font-size: var(--teks-kecil); }
        }
    </style>

    <p class="lj-ringkas">
        Status: <strong>{{ $this->record->status->label() }}</strong> ·
        Terjawab lengkap: <strong>{{ $progres['terjawab'] }}/{{ $progres['total'] }}</strong>
        @if ($this->record->submitted_at) · Dikirim {{ $this->record->submitted_at->translatedFormat('j F Y H:i') }} @endif
    </p>

    <x-filament::section>
        <div class="lj-daftar" role="table" aria-label="Jawaban per unit">
            <div class="lj-kepala" role="row">
                <span role="columnheader">Unit</span>
                @foreach ($indikator as $k)
                    <span role="columnheader">{{ \App\Pemetaan\Indikator::label($k) }}</span>
                @endforeach
            </div>
            @foreach ($tabs as $tab)
                @foreach ($tab->units() as $u)
                    @php($dalam = (int) ($u->kedalaman ?? 0))
                    <div class="lj-baris" role="row">
                        <div class="lj-nama {{ $dalam === 0 ? 'utama' : '' }}" role="cell" style="padding-left: {{ $dalam * 1.25 }}rem">{{ \App\Pemetaan\Format::namaUnit($u->nama_unit) }}</div>
                        @foreach ($indikator as $k)
                            @php($v = $u->answer?->{$k})
                            <div class="lj-sel" role="cell">
                                <span class="label">{{ \App\Pemetaan\Indikator::label($k) }}</span>
                                @if ($v === null) <span class="lj-nilai lj-kosong">Belum</span>
                                @elseif ($v) <span class="lj-nilai lj-ya">Ya</span>
                                @else <span class="lj-nilai lj-tidak">Tidak</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endforeach
        </div>
    </x-filament::section>
</x-filament-panels::page>
