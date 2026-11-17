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
