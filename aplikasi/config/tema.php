<?php

/*
| Satu-satunya sumber nilai desain ("token") aplikasi.
| Halaman OPD dan panel admin membaca dari sini lewat resources/views/partials/token.blade.php,
| jadi mengganti satu warna cukup di file ini.
|
| Peran warna (satu warna satu arti):
|   sampul*   : shell hijau (rel, login, sidebar admin)
|   posisi    : HANYA penanda posisi sekarang (tab aktif, cincin fokus, unit yang baru disimpan)
|   ya/tidak  : HANYA jawaban Ya / Tidak
|   galat     : pesan kesalahan
|   stempel   : tinta stempel TERKIRIM
*/
return [
    'warna' => [
        'sampul' => '#16382c',
        'sampul-2' => '#1e4838',
        'sampul-3' => '#2a5a47',
        'sampul-garis' => '#3b6b57',
        'sampul-tinta' => '#f1ead2',
        'sampul-tinta-2' => '#b9c7b4',

        'posisi' => '#d4ad3a',

        'kertas' => '#f6f3ea',
        'halaman' => '#fffdf7',
        'garis' => '#d6e2d6',
        'garis-kuat' => '#b5c8b8',
        'tinta' => '#1c2520',
        'tinta-2' => '#4d5a52',
        'nonaktif' => '#9aa79f',
        'nonaktif-tinta' => '#eef1ee',

        'ya' => '#1d7348',
        'ya-latar' => '#e2f0e7',
        'tidak' => '#b0482c',
        'tidak-latar' => '#f7e5de',
        'galat' => '#8f1f45',
        'galat-latar' => '#f8e3ea',
        'peringatan' => '#8a5a00',
        'peringatan-latar' => '#fbf0d6',
        'stempel' => '#46408f',
    ],

    'huruf' => [
        'serif' => '"Source Serif 4", Georgia, "Times New Roman", serif',
        'sans' => '"Atkinson Hyperlegible", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
    ],

    // Area kerja hanya memakai "teks" dan "judul"; ukuran lain khusus shell/stempel.
    'ukuran' => [
        'teks-kecil' => '13px',
        'teks' => '16px',
        'teks-rel' => '18px',
        'judul' => '22px',
    ],

    'bentuk' => [
        'radius' => '12px',
        'radius-kontrol' => '10px',
        'sentuh' => '44px',
    ],
];
