# Desain: Form Pemetaan Unit Digital per OPD

Tanggal: 17 November 2026
Status: draf, menunggu persetujuan
Prototipe tampilan (sudah disetujui): `prototipe/contoh-form-bapenda.html`

## 1. Maksud

Pemerintah Kabupaten Rokan Hilir memetakan unit kerja struktural mana yang tergolong **unit digital**. Setiap OPD menilai unitnya sendiri, mulai dari OPD, lalu Bagian/Bidang/UPT, sampai Subbagian/Subbidang/Seksi, memakai 3 indikator Ya/Tidak yang sama. Hasilnya direkap oleh admin (pemilik proyek) menjadi satu Excel untuk seluruh kabupaten.

### Indikator (sama untuk semua level, jawaban hanya Ya / Tidak)

| Kode | Indikator | Pertanyaan |
|---|---|---|
| `mandat` | Mandat Formal | Apakah terdapat mandat formal dari pimpinan atau peraturan tertulis yang mendasari unit ini sebagai unit digital? |
| `keluaran` | Keluaran Utama | Apakah unit ini menghasilkan keluaran (output) digital sebagai produk atau layanan utamanya? |
| `sumber_daya` | Sumber Daya Khusus | Apakah unit ini memiliki alokasi sumber daya manusia atau anggaran khusus untuk pengelolaan teknologi informasi? |

Teks pertanyaan ditulis persis seperti di atas.

## 2. Tujuan, bukan tujuan, dan yang harus dihindari

**Tujuan**
- 49 OPD bisa login dan mengisi penilaian untuk semua unit strukturalnya.
- Isian bisa dicicil: jawaban tersimpan otomatis dan bisa dilanjutkan kapan saja.
- Admin bisa melihat progres semua OPD dan mengunduh rekap Excel kapan saja.

**Bukan tujuan (tidak dibuat)**
- Lampiran dokumen bukti, kolom keterangan, atau skor/bobot.
- Akun per pegawai. Akun hanya satu per OPD.
- Integrasi ke SIASN atau sistem lain.
- Lebih dari satu periode penilaian.

**Harus dihindari (gagal walaupun aplikasinya berjalan)**
- OPD melihat atau mengubah data OPD lain.
- Jawaban hilang, misalnya karena sesi habis, koneksi putus, atau container di-restart.
- Daftar unit yang tampil tidak sesuai struktur sebenarnya dan OPD tidak bisa melaporkannya.

## 3. Data struktur

**Sumber data**: `output/Struktur Jabatan per OPD.xlsx`, sheet `Struktur per OPD`, hasil dari `scripts/buat_struktur_opd.py` atas data SIASN. Kolom yang dipakai: `Nama OPD`, `Level`, `Nama Jabatan`, `Unit Organisasi`, `Kategori`, `Eselon`, `Atasan Langsung`.

**Isinya**: 49 OPD (19 Dinas, 18 Kecamatan, 6 Badan, Setda, Setwan, Inspektorat, Satpol PP, RSUD, Disdik) dengan 603 unit:
- Level 0: 49 (pimpinan OPD)
- Level 1: 289 (Sekretariat, Bidang, Bagian, UPTD, Kelurahan, Seksi Kecamatan, Irban, Asisten, Staf Ahli)
- Level 2: 264 (Subbag, Subbid, Seksi, Sekretariat Lurah, TU UPTD, dan Bagian di bawah Asisten Setda)
- Level 3: 1 (satu Subbagian di Setda)

**Induk setiap unit** ditentukan dari kolom `Atasan Langsung`, yang dicocokkan dengan `Unit Organisasi` di OPD yang sama. Nama unit tidak ada yang kembar di dalam satu OPD.

**Aturan khusus**
- **Staf Ahli** (3 unit di Setda) **tidak dinilai**, karena Staf Ahli bukan unit kerja. Unitnya tetap diimpor dengan tanda `dinilai = false`, tetapi tidak muncul di form.
- **Asisten** di Setda dinilai seperti Bagian/Bidang. Bagian di bawahnya dan satu Subbagian di level 3 ikut di tab Asisten tersebut.
- **Kelurahan** di Kecamatan diperlakukan sebagai level 1, dan Sekretariat Lurah di bawahnya sebagai sub-unit.

**Data SIASN belum tentu lengkap.** Misalnya, beberapa OPD hanya punya 1 jabatan eselon IV. Karena itu, admin harus bisa menambah, mengubah nama, memindahkan induk, dan menonaktifkan unit lewat panel admin. Impor ulang tidak boleh menghapus jawaban yang sudah ada (lihat bagian 6).

## 4. Pengguna dan akses

| Peran | Jumlah | Bisa |
|---|---|---|
| **OPD** | 49 akun, satu per OPD | Login, mengisi dan mengubah jawaban unit OPD-nya sendiri, mengirim, melihat ringkasan jawabannya |
| **Admin** | 1 atau lebih | Semua yang ada di bagian 6 |

- **Login OPD** memakai username pendek yang dibuat dari singkatan OPD (misalnya `bapenda`, `kec-bangko`) dan kata sandi.
- **Kata sandi awal** dibuat acak saat akun dibuat. Admin mengunduh daftar username dan kata sandi awal (Excel) satu kali untuk dibagikan ke OPD. Kata sandi disimpan dalam bentuk hash.
- **Ganti kata sandi**: OPD bisa mengganti kata sandinya sendiri. Kalau lupa, admin mengatur ulang dari panel admin. Tidak ada reset lewat email.
- **Pembatasan akses**: setiap permintaan dari akun OPD hanya boleh membaca dan menulis unit dengan `opd_id` milik akun itu. Pembatasan ini dicek di server, bukan hanya di tampilan.

## 5. Alur pengisian OPD

Tampilan dan alur mengikuti prototipe yang sudah disetujui.

**Susunan tab**
1. **Tab 1, OPD**: berisi unit level 0.
2. **Tab 2 dan seterusnya**: satu tab untuk setiap unit level 1 yang dinilai, dengan urutan seperti di data (Sekretariat dulu). Isi tab adalah kartu unit level 1 itu, lalu semua unit turunannya (level 2 dan 3) yang tampil menjorok sesuai kedalaman.
3. **Tab terakhir, Periksa & Kirim**: tabel semua unit dan jawabannya.
4. Unit level 2 yang tidak punya induk di level 1 ditampilkan di tab OPD.

**Navigasi**
- Di desktop, daftar tab ada di samping kiri. Tiap tab menampilkan progres (misalnya 1/3), dan sub-unit tampil sebagai butir di bawah tabnya.
- Di HP, tab menjadi baris yang bisa digeser ke samping.
- Tab bisa dibuka dengan urutan bebas. Tersedia juga tombol "Lanjut" dan "Kembali".

**Kartu unit**: nama unit, nama jabatan, kategori, lalu 3 pertanyaan dengan tombol Ya/Tidak. Penjelasan lengkap ketiga indikator tampil di atas setiap tab.

**Simpan otomatis**
- Setiap klik Ya/Tidak langsung dikirim ke server (satu jawaban per permintaan) tanpa tombol simpan.
- Jawaban bisa diubah, termasuk diganti dari Ya ke Tidak, tetapi tidak bisa dikosongkan lagi.
- Kalau gagal tersimpan, tombolnya kembali ke keadaan sebelumnya dan muncul pesan "Gagal menyimpan, coba lagi".

**Kirim**
- Tombol Kirim baru aktif kalau semua unit yang dinilai sudah terjawab ketiga indikatornya.
- Sebelum mengirim, muncul konfirmasi.
- Setelah dikirim, status OPD menjadi **Terkirim**, semua jawaban terkunci (dicek di server), dan OPD hanya bisa melihat ringkasannya.

**Status OPD**: `belum_mulai`, lalu `mengisi` (sejak ada jawaban pertama), lalu `terkirim`. Kalau admin membuka kunci, status kembali ke `mengisi`.

## 6. Panel admin (Filament)

**Dasbor**
- Tabel 49 OPD dengan kolom status, jumlah unit terjawab dibanding total, tanggal terakhir diubah, dan tanggal kirim.
- Filter berdasarkan status.
- Angka ringkasan: jumlah OPD terkirim, sedang mengisi, dan belum mulai.

**Aksi per OPD**
- Lihat jawaban (hanya baca).
- **Buka kunci** setelah terkirim.
- Atur ulang kata sandi, dengan kata sandi baru ditampilkan satu kali.

**Kelola unit**
- Daftar unit per OPD yang bisa ditambah, diubah (nama, jabatan, kategori, induk, urutan), diubah tanda `dinilai`-nya, atau dinonaktifkan.
- Unit yang sudah punya jawaban tidak dihapus, hanya dinonaktifkan. Jawabannya tetap tersimpan tetapi tidak ikut dihitung atau direkap.

**Impor struktur**: perintah `php artisan pemetaan:impor <file.xlsx>`
- Dipakai terutama untuk pengisian awal. Setelah itu, **sumber kebenaran adalah database**, termasuk perubahan yang dibuat admin.
- Impor hanya **membuat** OPD dan unit yang belum ada, dicocokkan berdasarkan OPD dan nama unit organisasi. Unit yang sudah ada tidak pernah ditimpa.
- Unit yang atributnya berbeda dengan file, atau yang tidak ada lagi di file, hanya dilaporkan di output perintah. Admin memutuskan sendiri lewat panel.
- Unit yang pernah diganti namanya oleh admin menyimpan nama aslinya di kolom `nama_impor`, jadi impor ulang tidak membuat duplikat. Pencocokan memakai `nama_impor`.
- Tidak menghapus apa pun dan bisa dijalankan berulang kali dengan hasil yang sama.

**Perubahan unit pada OPD yang sedang mengisi atau sudah terkirim**
- Status OPD tidak berubah otomatis.
- Kalau sebuah OPD `terkirim` tetapi punya unit dinilai yang belum lengkap jawabannya (karena unit ditambah atau diaktifkan setelah dikirim), dasbor menandainya **"Perlu dilengkapi"**. Admin lalu memakai Buka kunci supaya OPD bisa mengisinya.
- Hitungan terjawab/total di dasbor dan rekap selalu dihitung dari unit yang aktif dan dinilai saat itu.

**Melaporkan struktur yang salah**: di setiap halaman form OPD ada teks kontak admin, misalnya "Unit tidak sesuai? Hubungi admin: …". Isinya diatur admin dari panel. Perbaikan dilakukan admin lewat Kelola unit. Tidak ada fitur laporan di dalam aplikasi.
- Akun OPD tidak dibuat oleh perintah ini, tetapi oleh perintah atau aksi terpisah "Buat akun OPD" yang menghasilkan Excel berisi username dan kata sandi awal.

**Unduh rekap Excel**, kapan saja, berisi semua OPD termasuk yang belum selesai:
- Sheet **Ringkasan OPD**: no, OPD, status, unit dinilai, unit terjawab lengkap, tanggal kirim, dan jumlah unit yang menjawab Ya untuk masing-masing indikator.
- Sheet **Jawaban per Unit**: OPD, level, unit organisasi (dengan indentasi sesuai level), jabatan, kategori, eselon, induk, Mandat Formal, Keluaran Utama, Sumber Daya Khusus (Ya/Tidak/kosong), dan jumlah Ya (0–3).

## 7. Arsitektur dan teknologi

| Bagian | Pilihan |
|---|---|
| Framework | Laravel 12 (PHP 8.3) |
| Panel admin | Filament 3 |
| Form OPD | Blade + Alpine.js (tanpa build SPA). Tampilan diambil dari prototipe. |
| Database | SQLite (mode WAL), satu file di volume `storage` (lihat alasannya di bawah) |
| Excel | `maatwebsite/excel` (impor dan ekspor) |
| Login | Login sesi Laravel. Admin dan OPD sama-sama di tabel `users` dengan kolom `role`. |
| Server | VPS yang sudah ada (lihat bagian 7a) |
| Pemasangan | Docker Compose, proyek terpisah `pemetaan`, berisi **satu container** `pemetaan_app` (PHP 8.3 + Nginx internal, misalnya image `serversideup/php:8.3-fpm-nginx`). Tidak ada port yang dibuka ke publik. |
| Backup | Tombol **Unduh backup** di panel admin yang mengunduh salinan file database (dibuat dengan `VACUUM INTO`, aman walaupun aplikasi sedang dipakai). Admin mengunduhnya secara rutin. Ditambah salinan otomatis harian di server yang disimpan 7 hari. |

**Kenapa SQLite, bukan MySQL**
- Penggunanya hanya 49 akun dengan tulisan kecil-kecil, jadi SQLite lebih dari cukup.
- RAM VPS hanya 2 GB dan sudah dipakai situs lain. SQLite tidak menambah container database.
- Backup cukup satu file, sehingga mudah diunduh rutin oleh admin.
- Laravel mendukung SQLite bawaan. Kalau suatu saat perlu pindah ke MySQL, cukup mengubah konfigurasi dan menjalankan migrasi.

### 7a. VPS dan cara memasang

**Kondisi VPS** (dicek 17 November 2026, hanya baca): `ubuntu@43.173.8.198`
- Ubuntu 24.04, 2 CPU, RAM 2 GB (sekitar 1,3 GB tersedia), swap 2 GB, disk sisa 26 GB.
- Docker 29 dan Docker Compose v5.
- Firewall ufw hanya membuka port 22, 80, dan 443.

**Yang sudah berjalan**
- Proyek `akupeta` (Laravel, MySQL 8, PHP 8.3).
- Container **`akupeta_nginx`** memegang port 80/443 untuk semua situs dan melayani `akupeta`, `bpkadpost`, dan `sopku` (semuanya `*.nekomade.com`). Sertifikatnya diurus oleh container `akupeta_certbot`.
- Konfigurasi situs ada di `/home/akupeta/akupeta/docker/prod/nginx/conf.d/`.

**Cara memasang aplikasi ini**
- Kode diletakkan di folder sendiri, misalnya `/srv/pemetaan`, sebagai proyek Compose sendiri. Proyek lain tidak diubah.
- **Tahap uji (belum ada domain)**: aplikasi hanya bisa dibuka lewat SSH tunnel, misalnya `ssh -L 8090:localhost:8090 ubuntu@43.173.8.198`, dengan port container diikat ke `127.0.0.1:8090`. Tidak ada port baru di firewall. OPD belum mengakses aplikasi pada tahap ini.
- **Saat domain siap**: container `pemetaan_app` disambungkan ke jaringan `akupeta_akupeta_net`. Lalu file `pemetaan.conf` ditambahkan ke folder conf.d `akupeta_nginx` dengan `proxy_pass` ke `pemetaan_app`, sertifikat dibuat lewat certbot yang sudah ada, dan Nginx di-reload. Langkah ini menyentuh Nginx bersama, jadi harus dijalankan dengan `nginx -t` sebelum reload, dan hanya setelah ada persetujuan pemilik.
- Batas memori container sekitar 256 MB, supaya tidak mengganggu situs lain.

**Model data (garis besar)**
- `opds`: id, nama, singkatan, urutan, status, submitted_at
- `units`: id, opd_id, parent_id, level, nama_unit, nama_impor (nullable, unik per OPD), nama_jabatan, kategori, eselon, urutan, dinilai, aktif
- `settings`: key, value (untuk teks kontak admin)
- `answers`: id, unit_id (unik), mandat, keluaran, sumber_daya (masing-masing nullable bool), updated_at, updated_by
- `users`: id, name, username, password, role (`admin`/`opd`), opd_id (nullable)

Jawaban disimpan satu baris per unit, dengan satu kolom untuk tiap indikator. Indikatornya tetap tiga dan tidak perlu bisa diatur.

## 8. Keputusan yang diambil pembuat desain (boleh dikoreksi)

Hal-hal ini tidak diputuskan langsung oleh pemilik proyek:
- Jawaban terkunci setelah dikirim, dan admin bisa membuka kuncinya.
- Format username, kata sandi awal acak, dan tidak ada reset lewat email.
- Isi kolom rekap Excel.
- Staf Ahli tidak dinilai.
- Kelurahan diperlakukan sebagai level 1.
- SQLite dipilih, bukan MySQL (alasannya di bagian 7).
- Aplikasi memakai Nginx bersama (`akupeta_nginx`) yang sudah ada dan tidak memasang Caddy. Sebelum domain siap, aplikasi hanya diakses lewat SSH tunnel.
- Impor hanya membuat unit baru dan tidak menimpa perubahan admin.
- Status OPD tidak berubah otomatis saat unitnya diubah. Sebagai gantinya ada tanda "Perlu dilengkapi".
- Laporan struktur yang salah cukup lewat kontak admin yang tampil di form, tanpa fitur laporan di dalam aplikasi.

## 9. Diserahkan ke pembuat aplikasi

- Struktur folder, penamaan class, dan route.
- Detail gaya tampilan, selama tetap mengikuti prototipe.
- Cara memetakan singkatan OPD untuk username. Boleh tabel tetap di seeder, dan admin bisa mengubahnya.
- Strategi pengujian, minimal mencakup: pembatasan akses antar-OPD, kunci setelah kirim, impor yang tidak menghapus jawaban, dan isi rekap Excel.
