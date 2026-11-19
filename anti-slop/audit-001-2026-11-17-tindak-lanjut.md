# Tindak lanjut audit antislop #001 (17 November 2026)

Diperbaiki sesuai permintaan pemilik: **nomor 1 sampai 5** (semua temuan prioritas TINGGI). Nomor 6 sampai 13 **tidak disentuh**.

| No | Temuan | Perbaikan | Bukti |
|---|---|---|---|
| 1 | Em dash di teks UI (R-02) | Sel jawaban kosong kini bertuliskan **"Belum"**, baik di lembar Periksa maupun di Lihat jawaban. Penanda tingkatan di Kelola unit diganti indentasi, ditambah keterangan level ("Bagian / Bidang / UPT", dan seterusnya). Placeholder tanggal "–" di tabel OPD diganti "Belum ada" dan "Belum dikirim". | Test `R-02: tidak ada em dash di teks antarmuka` memindai semua view dan berkas Filament. Di browser, teks halaman Periksa tidak mengandung "—". |
| 2 | Kontras < 4.5:1 (R-25) | Semua perubahan warna di `config/tema.php`. Hijau Ya `#0c7a3f` (putih 5.42:1, pil 4.86:1). Merah Tidak `#c42d33` (putih 5.57:1, pil 4.88:1). Tombol nonaktif: latar `#e8ebf0`, teks `#5a6272` (5.13:1). Teks panel login kini putih penuh, tanpa opacity atau campuran. | 8 pasangan warna diuji di `R-25: pasangan warna teks di token memenuhi 4.5:1`, ditambah test bahwa teks login tidak dipudarkan. |
| 3 | Target sentuh < 44px (R-03) | Tombol Ya/Tidak memakai `min-height: var(--sentuh)` (44px). Tombol ikon di tabel admin dibesarkan menjadi 44×44px lewat tema. | Test `R-03`. Di browser 375px, tinggi tombol Ya/Tidak terukur 44px. |
| 4 | Tidak ada keadaan kosong (R-27) | OPD tanpa unit dinilai melihat pesan **"Belum ada unit yang perlu dinilai"** beserta kontak admin. Tab, petunjuk, dan tombol kirim tidak tampil. Server menolak kirim dengan 0 unit (`TidakAdaUnit`: "Belum ada unit yang bisa dinilai. Hubungi admin."). | Test `kirim ditolak bila opd tidak punya unit yang dinilai` dan `opd tanpa unit dinilai melihat keadaan kosong dan tidak bisa mengirim`. |
| 5 | Petunjuk belum dikonfirmasi (R-38) | Teks petunjuk kini bisa disunting admin di **Pengaturan → Petunjuk menjawab**. Selama belum disimpan, form OPD menampilkan label **"Contoh penjelasan, belum dikonfirmasi admin"**, dan label itu hilang setelah admin menyimpan. Teks ditampilkan sebagai teks biasa (aman dari HTML). | Test `petunjuk berlabel contoh sampai dikonfirmasi admin` dan `pengaturan menyimpan petunjuk indikator dan menandainya dikonfirmasi`. Di browser, label tampil di form `dishub`. |

**Hasil test:** 96 lulus (sebelumnya 81, ditambah 15 test baru dari audit ini).

**Yang tidak dicek visual:** halaman admin (Pengaturan, Kelola unit, ukuran tombol ikon). Saat itu pemilik sedang login sebagai OPD di browser, jadi sesinya tidak diganggu. Halaman-halaman ini dicakup test Livewire, tetapi belum dilihat langsung.

---

## Putaran 2: nomor 6 sampai 13

| No | Temuan | Perbaikan | Bukti |
|---|---|---|---|
| 6 | Ikon bergaya Lucide tanpa alasan (R-04) | Ikon hiasan dihapus: buku di "Cara menjawab", daftar centang dan kotak ikon di login, panah di "Masuk" dan "Lengkapi". Sprite dipangkas menjadi 17 ikon yang dipakai. Alasan gaya ikon (garis 2px berujung bulat, serasi dengan Manrope, hanya untuk kontrol yang fungsinya diperjelas ikon) ditulis di DESIGN.md. | Test `R-04/R-21/R-20` memeriksa "Alasan ikon" di DESIGN.md. |
| 7 | Label kapital berjarak lebar (R-06) | Kepala kolom form, tabel Periksa, dan Lihat jawaban admin kini huruf biasa, 13px, tebal 700. | Test `R-06` memindai `app.css` dan `lihat-jawaban.blade.php`. |
| 8 | Panah hiasan (R-08) | Panah dilepas dari "Masuk" dan "Lengkapi". Panah pada "Sebelumnya" dan "Lanjut: …" tetap ada karena menunjukkan arah navigasi. | Test `R-08`. Di browser, tombol berisi "Lengkapi" saja (tinggi 44px). |
| 9 | Bayangan berwarna dan bayangan kartu (R-12, R-13) | Bayangan biru, hijau, dan merah di tombol utama serta pilihan Ya/Tidak dihapus. Kartu (daftar unit, lembar, pita terkirim, form kata sandi) kini cukup garis 1px. Bayangan hanya tersisa di dialog, menu HP, dan rel yang menempel di HP; alasannya ditulis di DESIGN.md. | Test `R-12/R-13`. |
| 10 | Cincin hiasan di login (R-01, R-07) | Dihapus. Panel biru kini polos. | Test `R-01/R-07/R-05`. |
| 11 | Pola "3 langkah berikon" di login (R-05) | Diganti satu kalimat tugas: "Setiap OPD menilai unit kerjanya dengan tiga pertanyaan Ya atau Tidak. Jawaban tersimpan otomatis, jadi boleh dicicil. Kirim setelah semua unit terisi." | Test yang sama, ditambah tangkapan layar login 1440px. |
| 12 | Alasan tema terang (R-21) | Alasannya ditulis di DESIGN.md: dipakai di kantor siang hari dan dari HP, pemilik meminta putih bersih, dan satu tema berarti satu set kontras yang teruji. | Test `R-04/R-21/R-20`. |
| 13 | Identitas dan dial (R-20, Blok 3) | DESIGN.md kini memuat Design Read, `Dial: ENERGY 1 / RHYTHM 1 / MOTION 1` beserta alasannya, dan **motif identitas: butir progres** (satu butir per unit). Motif ini diulang di rel tab, kepala lembar Periksa & Kirim, dan kolom Progres di dasbor admin ("Perlu ditindaklanjuti"). | Test `motif butir progres tampil di tabel tindak lanjut` dan pemeriksaan DESIGN.md. Di browser, butir tampil di dasbor admin. |

**Hasil test:** 102 lulus (6 test baru di putaran ini). Detektor desain Impeccable: tidak ada temuan.

**Dicek di browser:**
- form OPD di 1440px (tab dan lembar Periksa)
- login di 1440px
- admin: dasbor, Pengaturan, dan Kelola unit di 1440px, serta tombol ikon tabel OPD di 375px (terukur 44×44px)

Sesi browser dikembalikan ke akun `dishub`.

**Semua 13 temuan audit #001 sudah ditangani.**

---

**Keputusan pemilik (19 November 2026), temuan 5 (R-38):** label "Contoh penjelasan, belum dikonfirmasi admin" dihapus atas permintaan pemilik. Teks petunjuk dipakai apa adanya dan tetap bisa diubah admin di Pengaturan → Petunjuk menjawab.
