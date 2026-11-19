<?php

// Konfigurasi compose harus bisa dipakai di server mana pun (server Pemda), dengan tambahan khusus VPS di overlay.

test('compose dasar tidak bergantung pada jaringan Nginx bersama di VPS', function () {
    $dasar = file_get_contents(base_path('compose.yaml'));
    expect($dasar)->not->toContain('akupeta_akupeta_net')
        ->and($dasar)->not->toContain('external: true')
        ->and($dasar)->toContain('127.0.0.1:${PEMETAAN_PORT:-8090}:8080');
});

test('overlay VPS menyambungkan layanan pemetaan ke jaringan Nginx bersama', function () {
    $vps = file_get_contents(base_path('compose.vps.yaml'));
    expect($vps)->toContain('name: akupeta_akupeta_net')
        ->and($vps)->toMatch('/^  pemetaan:\s*\n    networks: \[default, proxy\]/m')
        ->and($vps)->not->toMatch('/^  app:\s*$/m');
});
