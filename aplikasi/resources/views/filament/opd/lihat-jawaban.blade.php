<x-filament-panels::page>
    <div class="text-sm text-gray-600 dark:text-gray-400">
        Status: <strong>{{ $this->record->status->label() }}</strong> ·
        Terjawab lengkap: <strong>{{ $progres['terjawab'] }}/{{ $progres['total'] }}</strong>
        @if ($this->record->submitted_at) · Dikirim {{ $this->record->submitted_at->translatedFormat('j F Y H:i') }} @endif
    </div>
    <x-filament::section>
        <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b dark:border-gray-700">
                    <th class="py-2 pr-4">Unit</th>
                    @foreach ($indikator as $k)
                        <th class="py-2 px-2 text-center whitespace-nowrap">{{ \App\Pemetaan\Indikator::label($k) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @foreach ($tabs as $tab)
                @foreach ($tab->units() as $u)
                    <tr class="border-b dark:border-gray-800 {{ ($u->kedalaman ?? 0) === 0 ? 'font-semibold' : '' }}">
                        <td class="py-2 pr-4" style="padding-left: {{ ($u->kedalaman ?? 0) * 1.25 }}rem">{{ \App\Pemetaan\Format::namaUnit($u->nama_unit) }}</td>
                        @foreach ($indikator as $k)
                            @php $v = $u->answer?->{$k}; @endphp
                            <td class="py-2 px-2 text-center">
                                @if ($v === null) <span class="text-gray-400">–</span>
                                @elseif ($v) <span class="text-success-600 font-semibold">Ya</span>
                                @else <span class="text-danger-600 font-semibold">Tidak</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
            </tbody>
        </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
