# Design — Pemetaan Unit Digital

Gaya: **bersih dengan satu aksen biru elektrik**, ditetapkan oleh pemilik. Latarnya putih dan abu sangat muda, garisnya tipis, hurufnya Manrope. Warna cerah hanya dipakai untuk hal yang perlu dilihat atau diklik. Mode: Operate (tugas lebih penting daripada ekspresi).

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
