<?php

// Penjaga temuan audit antislop #001 (anti-slop/audit-001-2026-11-17.md).

function luminansi(string $hex): float
{
    $c = array_map(fn ($i) => hexdec(substr(ltrim($hex, '#'), $i, 2)) / 255, [0, 2, 4]);
    $c = array_map(fn ($x) => $x <= 0.03928 ? $x / 12.92 : (($x + 0.055) / 1.055) ** 2.4, $c);

    return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function kontras(string $a, string $b): float
{
    [$t, $r] = [max(luminansi($a), luminansi($b)), min(luminansi($a), luminansi($b))];

    return ($t + 0.05) / ($r + 0.05);
}

test('R-02: tidak ada em dash di teks antarmuka', function () {
    $berkas = array_merge(
        glob(resource_path('views/**/*.blade.php')), glob(resource_path('views/*/*/*.blade.php')),
        glob(app_path('Filament/**/*.php')), glob(app_path('Filament/*/*/*.php')), [app_path('Pemetaan/Indikator.php')],
    );
    $temuan = [];
    foreach (array_unique($berkas) as $f) {
        foreach (file($f) as $no => $baris) {
            $t = ltrim($baris);
            if (str_contains($baris, '—') && ! str_starts_with($t, '//') && ! str_starts_with($t, '*') && ! str_starts_with($t, '{{--')) {
                $temuan[] = basename($f).':'.($no + 1);
            }
        }
    }
    expect($temuan)->toBe([]);
});

test('R-25: pasangan warna teks di token memenuhi 4.5:1', function (string $teks, string $latar) {
    $w = config('tema.warna');
    expect(kontras($w[$teks], $w[$latar]))->toBeGreaterThanOrEqual(4.5);
})->with([
    'putih di Ya' => ['aksen-tinta', 'ya'],
    'putih di Tidak' => ['aksen-tinta', 'tidak'],
    'pil Ya' => ['ya', 'ya-latar'],
    'pil Tidak' => ['tidak', 'tidak-latar'],
    'tombol nonaktif' => ['nonaktif-tinta', 'nonaktif'],
    'putih di aksen' => ['aksen-tinta', 'aksen'],
    'teks sekunder' => ['tinta-2', 'latar'],
    'galat' => ['galat', 'galat-latar'],
]);

test('R-03: target sentuh token minimal 44px dan dipakai tombol Ya/Tidak', function () {
    expect((int) config('tema.bentuk.sentuh'))->toBeGreaterThanOrEqual(44);
    expect(file_get_contents(resource_path('css/app.css')))->toMatch('/\.pilih label \{[^}]*min-height: var\(--sentuh\)/');
});

test('R-25: teks di panel biru login tidak dipudarkan', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    expect($css)->not->toMatch('/\.sampul-instansi \{[^}]*opacity/')
        ->and($css)->not->toMatch('/\.sampul-langkah li \{[^}]*color-mix/');
});
