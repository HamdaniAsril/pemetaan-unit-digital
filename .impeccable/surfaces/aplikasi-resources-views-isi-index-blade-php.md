---
version: 1
slug: "aplikasi-resources-views-isi-index-blade-php"
primary_target: "aplikasi/resources/views/isi/index.blade.php"
related_targets: ["aplikasi/resources/views/auth/login.blade.php","aplikasi/resources/views/akun/password.blade.php","aplikasi/app/Providers/Filament/AdminPanelProvider.php"]
---

# Surface brief: aplikasi Pemetaan Unit Digital (login, form OPD, akun, panel admin)

Mode: Operate. Pengisi: operator/staf umum OPD, awam TI, HP dan laptop sama penting. Tugas: menilai setiap unit struktural dengan 3 indikator Ya/Tidak, dicicil, lalu kirim. Admin: pantau, kelola, rekap.

## Direction contract

THESIS: Form ini adalah buku rapor unit kerja: setiap unit satu baris nilai, tiga indikator tiga kolom, dan Kirim adalah tanda tangan wali kelas. Menolak susunan default "header biru + stepper + kartu putih".

OWN-WORLD: Sampul kain hijau rapor (#16382c) memegang shell: sidebar/rel tab, header, layar login. Halaman putih tulang (#f6f3ea) untuk area kerja dengan garis tipis hijau pucat. Emas (#c9a227) hanya untuk "posisi sekarang". Hijau daun = Ya, merah bata = Tidak; tidak dipakai untuk hal lain. Dua ukuran huruf di area kerja, tingkatan lewat tebal dan warna. Kontrol standar web.

STORY: Pengisi paham tugasnya dalam satu layar, yakin setiap jawaban tersimpan, melihat sisa pekerjaan, dan mengirim dengan sadar.

FIRST VIEWPORT: Desktop: rel tab hijau kiri (tinggi tiap tab sebanding jumlah unit, angka progres), area kerja putih tulang: judul tab, ringkasan progres, petunjuk indikator yang bisa dibuka, lalu kartu unit berbentuk baris rapor dengan tiga sel Ya/Tidak berlabel. HP: rel tab menjadi pita geser di atas, kartu satu kolom, tombol besar.

FORM: Buku Rapor, posisi 5 dari daftar grounded, seed b3ae5fc0. Raise: tinggi tab ∝ jumlah unit (Manual Bertab); Kirim terpisah dengan ruang kosong (Console); jawaban baru tersimpan tetap ditandai (Boarding Pass); dua ukuran huruf (Timetable); satu warna satu arti (Exposure Record). Signature move: Kirim dibubuhi stempel "TERKIRIM" seperti stempel rapor, dan ringkasan Periksa & Kirim tersusun sebagai lembar rapor.

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance
