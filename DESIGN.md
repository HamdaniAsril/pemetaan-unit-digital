# Design — Pemetaan Unit Digital

Gaya: **bersih dengan satu aksen biru elektrik**, ditetapkan oleh pemilik. Latarnya putih dan abu sangat muda, garisnya tipis, hurufnya Manrope. Warna cerah hanya dipakai untuk hal yang perlu dilihat atau diklik. Mode: Operate (tugas lebih penting daripada ekspresi).

## Design Read dan dial

**Design Read:** form layanan publik untuk operator OPD yang awam TI, dengan bahasa visual bersih dan satu aksen biru elektrik.

**Dial: ENERGY 1 / RHYTHM 1 / MOTION 1**

- **ENERGY 1:** pengisi datang untuk menyelesaikan tugas, bukan untuk terkesan.
- **RHYTHM 1:** setiap tab punya susunan yang sama, supaya pengisi tidak perlu belajar ulang di setiap tab.
- **MOTION 1:** gerak hanya untuk umpan balik, yaitu batang progres, status simpan, dan lencana Terkirim sekali.

**Motif identitas: butir progres.** Setiap unit digambar sebagai satu butir kecil yang terisi biru (atau hijau bila tab selesai) saat unitnya lengkap. Motif ini diulang di tiga tempat:
- rel tab di form OPD
- kepala lembar Periksa & Kirim
- kolom Progres di tabel "Perlu ditindaklanjuti" di dasbor admin

Butir ini menggambarkan cara kerja aplikasi: pekerjaan dihitung per unit.

## Alasan keputusan

| Keputusan | Alasan |
|---|---|
| **Alasan tema terang** | Hanya tema terang (form OPD dan `darkMode(false)` di admin). Aplikasi dipakai di kantor pada siang hari dan dari HP, pemilik meminta gaya putih bersih, dan satu tema berarti satu set kontras yang sudah diuji. |
| **Alasan ikon** | Garis 2px berujung bulat, dipilih agar serasi dengan terminal Manrope. Ikon hanya dipakai di tempat yang fungsinya diperjelas oleh ikon: centang/silang pada pilihan Ya/Tidak, status simpan, tombol kirim, gembok terkunci, panah navigasi Sebelumnya/Lanjut, menu, kata sandi, dan keluar. Ikon hiasan (buku, daftar centang, panah di "Masuk" dan "Lengkapi") sudah dihapus. |
| **Bayangan** | Hanya untuk benda yang benar-benar terangkat di atas konten: dialog konfirmasi, menu akun di HP, dan rel tab yang menempel di HP saat konten digulir. Kartu dan tombol tidak memakai bayangan; batasnya cukup garis 1px. |
| **Label kolom** | Huruf biasa, 13px, tebal 700, abu (`--tinta-2`). Bukan huruf kapital berjarak lebar. |
| **Panel login** | Bidang biru polos dengan nama aplikasi dan satu kalimat tugas, tanpa hiasan dan tanpa daftar langkah berikon. |

## Sumber token

Semua nilai desain hanya ada di **`aplikasi/config/tema.php`**. File itu dirender menjadi CSS custom properties oleh `resources/views/partials/token.blade.php`, yang dipakai oleh:
- halaman OPD (`layouts/app.blade.php`)
- panel admin Filament (`filament/tema.blade.php`; warna `primary`/`danger`/`success` di `AdminPanelProvider` juga membaca config yang sama)

`resources/css/app.css` tidak boleh berisi kode warna langsung; hanya `var(--…)`. Salinan token untuk alat desain ada di `.impeccable/design.json`.

## Peran warna

| Token | Nilai | Dipakai untuk |
|---|---|---|
| `--aksen` | #2b63f6 (biru elektrik) | Tombol utama, tab aktif, progres, tautan, cincin fokus. Sedikit lebih gelap dari #2F6BFF agar teks putih kontrasnya ≥4.5:1. |
| `--aksen-2` | #1d4fd8 | Hover dan teks tab aktif |
| `--aksen-muda`, `--aksen-garis` | #ebf1ff, #c9d8ff | Latar tab aktif, sorotan unit yang baru disimpan |
| `--latar`, `--permukaan` | #f6f7f9, #ffffff | Latar halaman dan kartu |
| `--garis`, `--garis-kuat` | #e7e9ee, #d5d9e2 | Garis pemisah dan tepi kontrol |
| `--tinta`, `--tinta-2` | #141821, #5a6272 | Teks utama dan sekunder |
| `--ya` / `--tidak` | #0f8a48 / #d6363c | Hanya jawaban Ya / Tidak |
| `--sukses` | #0f8a48 | Tab selesai, status lengkap, lencana terkirim |
| `--galat` | #c0182f | Pesan kesalahan |

## Huruf

- **Manrope** (variabel 200–800), di-host sendiri di `public/fonts`. Teks dasar ditulis dengan ketebalan 500, label 700, dan judul 800 dengan jarak huruf -0.02em.
- **Skala ukuran:** `--teks-kecil` 13px (label kolom, nomor tab), `--teks` 16px, `--teks-besar` 18px (nama OPD), `--judul` 24px.

## Komponen

- **Daftar unit:** satu kartu putih berisi baris-baris unit. Kepala kolom lengket di layar ≥1241px. Pada 901–1240px nama unit menempati baris sendiri dan tiga pertanyaan berlabel ada di bawahnya. Pada ≤900px semua ditumpuk.
- **Pilihan Ya/Tidak:** segmented control di atas latar abu muda. Pilihan yang aktif terisi hijau (Ya) atau merah (Tidak). Isinya radio asli dalam `role="radiogroup"` yang diberi label oleh nama indikator dan nama unit. Kelas `.on` dan `.fokus` dipasang lewat JS. Target sentuh minimal 40–44px.
- **Rel tab:** panel putih. Tab aktif berlatar biru muda dengan nomor di kotak biru. Tab yang selesai bernomor hijau. Butir kecil per unit menunjukkan sisa pekerjaan.
- **Status simpan:** pil di kepala area kerja (desktop) atau baris di rel lengket (HP).
- **Lencana Terkirim:** lingkaran hijau bertanda centang yang "pop" sekali setelah kirim, dan dimatikan bila pengguna memilih *reduced motion*.
- **Login:** panel biru elektrik penuh di kiri (di HP pindah ke atas) dan form putih di kanan.
