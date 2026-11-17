# Design — Pemetaan Unit Digital

Dunia visual: **Buku Rapor**. Form ini adalah buku rapor unit kerja: setiap unit satu baris nilai, tiga indikator tiga kolom, dan Kirim adalah tanda tangan wali kelas yang dibubuhi stempel. Mode: Operate (tugas lebih penting daripada ekspresi).

## Sumber token

Semua nilai desain hanya ada di **`aplikasi/config/tema.php`**. File itu dirender menjadi CSS custom properties oleh `resources/views/partials/token.blade.php`, yang dipakai oleh:
- halaman OPD (`layouts/app.blade.php`)
- panel admin Filament (`filament/tema.blade.php`; warna `primary`/`danger`/`success` di `AdminPanelProvider` juga membaca config yang sama)

`resources/css/app.css` tidak boleh berisi kode warna langsung; hanya `var(--…)`. Salinan token untuk alat desain ada di `.impeccable/design.json`.

## Peran warna (satu warna satu arti)

| Token | Nilai | Hanya untuk |
|---|---|---|
| `--sampul`, `--sampul-2/3`, `--sampul-garis` | #16382c, #1e4838, #2a5a47, #3b6b57 | Shell: rel tab, layar login, sidebar admin, tombol utama |
| `--sampul-tinta`, `--sampul-tinta-2` | #f1ead2, #b9c7b4 | Teks di atas sampul |
| `--posisi` | #d4ad3a (emas) | Posisi sekarang: nomor tab aktif, cincin fokus, unit yang baru disimpan |
| `--kertas`, `--halaman` | #f6f3ea, #fffdf7 | Latar area kerja dan lembar |
| `--garis`, `--garis-kuat` | #d6e2d6, #b5c8b8 | Garis lembar (hijau pucat seperti garis rapor) |
| `--tinta`, `--tinta-2` | #1c2520, #4d5a52 | Teks utama dan sekunder |
| `--ya` / `--tidak` | #1d7348 / #b0482c | Hanya jawaban Ya / Tidak |
| `--galat` | #8f1f45 | Pesan kesalahan |
| `--stempel` | #46408f | Tinta stempel TERKIRIM |

## Huruf

- **Source Serif 4** (600/700): judul, nama OPD, stempel. Di-host sendiri di `public/fonts`.
- **Atkinson Hyperlegible** (400/700): semua teks kerja. Dipilih karena keterbacaan tinggi untuk pengisi awam; nol bergaris miring adalah ciri font, bukan galat.
- **Area kerja hanya dua ukuran:** `--teks` 16px dan `--judul` 22px. Tingkatan dibawa oleh tebal dan warna. `--teks-rel` 18px dan `--teks-kecil` 13px hanya untuk shell dan stempel.

## Komponen

- **Lembar unit:** satu lembar bergaris (`.lembar-unit`), satu baris per unit. Kepala kolom lengket di layar ≥1241px. Pada 901–1240px nama unit menempati baris sendiri dan tiga pertanyaan berlabel ada di bawahnya. Pada ≤900px semua ditumpuk.
- **Pilihan Ya/Tidak:** radio asli dalam `role="radiogroup"` yang diberi label oleh nama indikator dan nama unit. Kelas `.on` dan `.fokus` dipasang lewat JS, jadi tidak bergantung pada `:has()`. Target sentuh minimal `--sentuh` 44px.
- **Rel tab:** butir kecil per unit (terisi = lengkap) menunjukkan besar pekerjaan. "Periksa & Kirim" dipisah dengan garis.
- **Status simpan:** pil di kepala area kerja (desktop) atau baris di rel lengket (HP).
- **Gerak khas:** stempel TERKIRIM "ditekan" sekali setelah kirim (`@keyframes tekan`), dan dimatikan bila pengguna memilih *reduced motion*.
- **Fokus:** cincin dua lapis (emas di atas sampul) agar terlihat di kertas maupun sampul.
