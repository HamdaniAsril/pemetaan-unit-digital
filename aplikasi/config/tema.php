<?php

/*
| Satu-satunya sumber nilai desain ("token") aplikasi.
| Halaman OPD dan panel admin membaca dari sini lewat resources/views/partials/token.blade.php,
| jadi mengganti satu warna cukup di file ini.
|
| Gaya: bersih (putih, abu sangat muda) dengan satu aksen biru elektrik.
| Peran warna:
|   aksen*    : tombol utama, tab aktif, progres, tautan, cincin fokus
|   ya/tidak  : HANYA jawaban Ya / Tidak
|   galat     : pesan kesalahan
*/
return [
    'warna' => [
        'aksen' => '#2b63f6',         // biru elektrik (dipilih pemilik); sedikit digelapkan dari #2F6BFF agar teks putih ≥4.5:1
        'aksen-2' => '#1d4fd8',       // hover / tekan
        'aksen-muda' => '#ebf1ff',    // latar tab aktif, sorotan
        'aksen-garis' => '#c9d8ff',
        'aksen-tinta' => '#ffffff',   // teks di atas aksen

        'latar' => '#f6f7f9',
        'permukaan' => '#ffffff',
        'garis' => '#e7e9ee',
        'garis-kuat' => '#d5d9e2',
        'tinta' => '#141821',
        'tinta-2' => '#5a6272',
        'nonaktif' => '#c3c8d2',
        'nonaktif-tinta' => '#ffffff',

        'ya' => '#0f8a48',
        'ya-latar' => '#e7f6ee',
        'tidak' => '#d6363c',
        'tidak-latar' => '#fdecec',
        'galat' => '#c0182f',
        'galat-latar' => '#fdecef',
        'sukses' => '#0f8a48',
        'sukses-latar' => '#e7f6ee',
    ],

    'huruf' => [
        'sans' => '"Manrope", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
    ],

    'ukuran' => [
        'teks-kecil' => '13px',
        'teks' => '16px',
        'teks-besar' => '18px',
        'judul' => '24px',
    ],

    'bentuk' => [
        'radius' => '14px',
        'radius-kontrol' => '10px',
        'sentuh' => '44px',
    ],
];
