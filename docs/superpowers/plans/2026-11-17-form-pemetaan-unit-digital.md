# Form Pemetaan Unit Digital — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Aplikasi Laravel tempat 49 OPD menilai unit strukturalnya dengan 3 indikator Ya/Tidak, plus panel admin untuk memantau, mengelola unit, dan mengunduh rekap Excel dan backup.

**Architecture:** Satu aplikasi Laravel 12 dengan database SQLite. Logika penilaian (tab, progres, status, kunci) dikumpulkan di satu service `App\Pemetaan\Penilaian` yang dipakai oleh form OPD (Blade + Alpine, tampilan dari prototipe) dan panel admin Filament. Aplikasi dipasang sebagai satu container Docker di VPS bersama, diikat ke `127.0.0.1:8090`.

**Tech Stack:** PHP 8.3 (lokal 8.4 juga boleh), Laravel 12, Pest, Filament 3, Alpine.js (CDN), maatwebsite/excel, SQLite (WAL), Docker image `serversideup/php:8.3-fpm-nginx`.

**Spec:** `docs/superpowers/specs/2026-11-17-form-pemetaan-unit-digital-design.md`. Baca seluruhnya sebelum mulai. Prototipe tampilan: `prototipe/contoh-form-bapenda.html`.

## Global Constraints

- Kode Laravel diletakkan di `aplikasi/` di root proyek. Semua path di plan ini relatif ke `aplikasi/`, kecuali disebut lain.
- Bahasa antarmuka: Bahasa Indonesia.
- Indikator tetap 3 dengan kode `mandat`, `keluaran`, `sumber_daya`. Label: "Mandat Formal", "Keluaran Utama", "Sumber Daya Khusus". Teks pertanyaan disalin persis dari bagian 1 spec.
- Jawaban disimpan sebagai boolean (`true` = Ya, `false` = Tidak, `null` = belum). Di HTTP/JSON memakai string `'ya'`/`'tidak'`.
- Status OPD (enum `App\Enums\StatusOpd`): `belum_mulai`, `mengisi`, `terkirim`.
- Hanya unit dengan `aktif = true` dan `dinilai = true` yang tampil di form dan dihitung di progres maupun rekap.
- Unit berkategori `Staf Ahli` diimpor dengan `dinilai = false`.
- Setiap permintaan dari akun OPD hanya boleh menyentuh unit dengan `opd_id` miliknya. Pembatasan ini dicek di server.
- Jawaban OPD berstatus `terkirim` tidak bisa diubah. Server menolak dengan HTTP 423.
- Impor tidak pernah menghapus atau menimpa data yang sudah ada.
- Kata sandi disimpan sebagai hash. Kata sandi awal dan hasil reset ditampilkan satu kali saja.
- Data mentah `bahan/` (data SIASN) **tidak** boleh masuk git.
- Deploy ke VPS (Task 9) **tidak** boleh mengubah container atau konfigurasi Nginx situs lain. Menyambungkan ke `akupeta_nginx` dan mengatur domain bukan bagian dari plan ini.

## Review Focus

1. **Sesi habis saat mengisi**: jawaban yang diklik setelah sesi habis harus memunculkan pesan "Sesi berakhir, silakan masuk lagi", bukan gagal diam-diam atau terlempar ke HTML login. Ditest di Task 5 (`jawaban tanpa sesi mengembalikan 401 JSON`).
2. **Klik cepat berulang atau berganti-ganti Ya/Tidak**: hasil akhir harus sama dengan klik terakhir, dan simpan berulang dengan nilai sama tidak boleh error. Ditest di Task 4 (`simpan jawaban idempoten`), dan UI mengirim permintaan berurutan per tombol.
3. **Kirim saat ada unit yang baru ditambah admin dan belum terjawab**: server harus menolak, walaupun tombol Kirim di browser sudah aktif dari tampilan lama. Ditest di Task 4 (`kirim ditolak bila ada unit dinilai yang belum lengkap`) dan Task 5.
4. **Unit nonaktif atau tidak dinilai yang punya jawaban lama**: unit itu tidak boleh ikut dihitung di progres, kelengkapan kirim, maupun rekap. Ditest di Task 4 dan Task 7.
5. **OPD yang belum pernah login tetap muncul di rekap** dengan jawaban kosong. Ditest di Task 7 (`rekap memuat semua OPD termasuk belum mulai`).

---

### Task 1: Kerangka proyek, model, dan migrasi

**Files:**
- Create: `.gitignore` (root proyek), `aplikasi/` (Laravel 12 baru)
- Create: `app/Enums/StatusOpd.php`, `app/Enums/Role.php`, `app/Pemetaan/Indikator.php`
- Create: `app/Models/Opd.php`, `app/Models/Unit.php`, `app/Models/Answer.php`, `app/Models/Setting.php`; Modify: `app/Models/User.php`
- Create: migrasi `opds`, `units`, `answers`, `settings`; Modify: migrasi `users`
- Modify: `config/database.php` (SQLite WAL, `busy_timeout` 5000, `foreign_key_constraints` true)
- Test: `tests/Unit/ModelTest.php`

**Interfaces:**
- Produces:
  - `enum StatusOpd: string { BelumMulai='belum_mulai'; Mengisi='mengisi'; Terkirim='terkirim'; label(): string }`
  - `enum Role: string { Admin='admin'; Opd='opd' }`
  - `final class Indikator { const KODE = ['mandat','keluaran','sumber_daya']; static label(string $kode): string; static pertanyaan(string $kode): string; static ringkas(string $kode): string }` (ringkas = teks pendek di kartu, seperti prototipe)
  - Tabel `opds`: id, nama (unik), singkatan (unik, nullable), urutan int, status (default `belum_mulai`), submitted_at nullable, timestamps
  - Tabel `units`: id, opd_id FK, parent_id FK nullable (self), level tinyint, nama_unit, nama_impor nullable, nama_jabatan nullable, kategori nullable, eselon nullable, urutan int, dinilai bool default true, aktif bool default true, timestamps. Unik (opd_id, nama_impor).
  - Tabel `answers`: id, unit_id FK unik, mandat/keluaran/sumber_daya bool nullable, updated_by FK users nullable, timestamps
  - Tabel `users`: tambah `username` unik, `role` (default `opd`), `opd_id` FK nullable. Kolom `email` dibuat nullable.
  - `settings`: key (PK string), value text nullable. `Setting::ambil(string $key, ?string $default=null): ?string`, `Setting::simpan(string $key, ?string $value): void`
  - Relasi: `Opd::units()`, `Opd::user()` (hasOne), `Unit::opd()`, `Unit::parent()`, `Unit::children()`, `Unit::answer()` (hasOne), `User::opd()`. Scope `Unit::scopeDinilai($q)` untuk `aktif && dinilai`. `Answer::lengkap(): bool` (ketiga kolom tidak null). Cast `Opd::status` → StatusOpd, `User::role` → Role.

- [ ] **Step 1:** Di root proyek: `git init`. Buat `.gitignore` berisi `bahan/`, `.DS_Store`, `~$*`, `prototipe/template.html`. Commit awal (`docs/`, `scripts/`, `output/`, `prototipe/`).
- [ ] **Step 2:** `composer create-project laravel/laravel aplikasi "^12.0"`, lalu `composer require pestphp/pest --dev -W` dan `./vendor/bin/pest --init`. Pastikan `.env` memakai `DB_CONNECTION=sqlite`, dan `APP_LOCALE=id`.
- [ ] **Step 3: Tulis test yang gagal** di `tests/Unit/ModelTest.php` (pakai `RefreshDatabase`):
  - `answer per unit unik`: membuat 2 Answer untuk unit yang sama melempar `QueryException`.
  - `scope dinilai mengecualikan nonaktif dan tidak dinilai`: dari 3 unit (normal, `aktif=false`, `dinilai=false`) → `Unit::dinilai()->count() === 1`.
  - `answer lengkap hanya bila ketiga indikator terisi`: `mandat=true, keluaran=false, sumber_daya=null` → `false`; ketiganya terisi → `true`.
  - `setting simpan dan ambil`: `Setting::simpan('kontak_admin','WA 0812')` lalu `Setting::ambil('kontak_admin') === 'WA 0812'`; key lain → default.
  - `indikator memakai teks spec`: `Indikator::pertanyaan('mandat')` sama persis dengan teks Mandat Formal di spec.
- [ ] **Step 4:** Jalankan `./vendor/bin/pest tests/Unit/ModelTest.php`. Harus FAIL (class/tabel belum ada).
- [ ] **Step 5:** Buat enum, `Indikator`, migrasi, model, factory (`OpdFactory`, `UnitFactory`, `AnswerFactory`, `UserFactory` dengan state `admin()` dan `untukOpd(Opd)`).
- [ ] **Step 6:** Jalankan test di atas. Harus PASS. Jalankan juga `php artisan migrate:fresh`, yang harus sukses.
- [ ] **Step 7:** Commit `feat: kerangka laravel, model dan migrasi pemetaan`.

### Task 2: Impor struktur dari Excel

**Files:**
- Create: `app/Pemetaan/ImporStruktur.php`, `app/Pemetaan/HasilImpor.php`, `app/Console/Commands/ImporStrukturCommand.php`
- Create: `database/data/struktur.xlsx` (salinan `output/Struktur Jabatan per OPD.xlsx`)
- Test: `tests/Feature/ImporStrukturTest.php`, helper `tests/Support/BuatXlsx.php`

**Interfaces:**
- Consumes: model Task 1.
- Produces:
  - `ImporStruktur::impor(string $path): HasilImpor`
  - `final class HasilImpor { int $opdBaru; int $unitBaru; array $berbeda /* list<string> */; array $hilang /* list<string> */ }`
  - Perintah `php artisan pemetaan:impor {path?}`, default `database/data/struktur.xlsx`. Mencetak jumlah OPD dan unit baru, lalu daftar `berbeda` dan `hilang`.

Aturan (dari spec bagian 3 dan 6):
- Membaca sheet `Struktur per OPD`. Kolom: `No`→urutan OPD, `Nama OPD`, `Level`, `Nama Jabatan`, `Unit Organisasi`, `Kategori`, `Eselon`, `Atasan Langsung`.
- Pencocokan unit memakai (opd, `nama_impor` = Unit Organisasi). Unit baru diisi `nama_unit = nama_impor = Unit Organisasi`, dan `urutan` mengikuti urutan baris dalam OPD.
- `parent_id` = unit di OPD yang sama yang `nama_impor`-nya sama dengan `Atasan Langsung`, atau null untuk level 0. Parent di-resolve setelah semua unit OPD dibuat.
- `dinilai = false` bila Kategori `Staf Ahli`.
- Unit yang sudah ada **tidak diubah**. Kalau ada atribut yang berbeda (nama_jabatan, kategori, eselon, level, induk), unit dimasukkan ke `berbeda` dengan format `"<OPD> / <unit>: <atribut> '<db>' → '<file>'"`.
- Unit di DB yang punya `nama_impor` tetapi tidak ada di file dimasukkan ke `hilang`.
- Semua dijalankan dalam satu transaksi.

- [ ] **Step 1: Tulis test yang gagal.** `BuatXlsx::struktur(array $baris): string` membuat file xlsx sementara dengan header di atas. Test:
  - `impor membuat opd, unit, dan induk`: fixture 1 OPD dengan level 0 → 2 level 1 → 1 level 2. Hasilnya 4 unit, `parent` level 2 benar, dan `opdBaru === 1`, `unitBaru === 4`.
  - `staf ahli tidak dinilai`: unit kategori `Staf Ahli` → `dinilai === false`.
  - `impor ulang tidak membuat duplikat`: impor 2× → jumlah unit tetap, dan run ke-2 `unitBaru === 0`.
  - `impor tidak menimpa perubahan admin`: setelah impor, ubah `nama_unit` dan `dinilai` sebuah unit, lalu impor lagi → nilai admin tetap, tidak ada duplikat.
  - `impor tidak menyentuh jawaban`: buat Answer, impor ulang → Answer utuh.
  - `perbedaan dan unit hilang dilaporkan`: impor ulang dengan kategori berubah dan satu baris dihapus → `berbeda` memuat 1 entri, `hilang` memuat 1 entri, dan unit di DB tidak berubah.
  - `data asli terimpor sesuai angka spec`: impor `database/data/struktur.xlsx` → 49 OPD, 603 unit, 3 unit `dinilai=false`, 1 unit level 3.
- [ ] **Step 2:** `./vendor/bin/pest tests/Feature/ImporStrukturTest.php`. Harus FAIL.
- [ ] **Step 3:** `composer require maatwebsite/excel` (atau langsung `phpoffice/phpspreadsheet` untuk membaca). Implementasikan `ImporStruktur`, `HasilImpor`, dan perintahnya.
- [ ] **Step 4:** Test harus PASS.
- [ ] **Step 5:** Commit `feat: impor struktur opd dari excel`.

### Task 3: Akun OPD dan login

**Files:**
- Create: `app/Pemetaan/AkunOpd.php`, `app/Console/Commands/BuatAkunOpdCommand.php`, `database/data/singkatan_opd.php`
- Create: `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Controllers/GantiPasswordController.php`, `app/Http/Middleware/HanyaOpd.php`
- Create: `resources/views/layouts/app.blade.php`, `resources/views/auth/login.blade.php`, `resources/views/akun/password.blade.php`
- Modify: `routes/web.php`, `bootstrap/app.php`
- Test: `tests/Feature/AkunOpdTest.php`, `tests/Feature/LoginTest.php`

**Interfaces:**
- Consumes: model Task 1.
- Produces:
  - `AkunOpd::buatAkun(): Collection` berisi `array{opd: string, username: string, password: string}`, hanya untuk OPD yang belum punya user. Juga mengisi `opds.singkatan` bila kosong.
  - `AkunOpd::username(Opd $opd): string`: dari `database/data/singkatan_opd.php` (map nama OPD → singkatan, misalnya `'BADAN PENDAPATAN DAERAH' => 'bapenda'`). Untuk `KECAMATAN X` hasilnya `kec-` + slug X. Kalau tidak ada di map, hasilnya slug nama yang dipotong 30 karakter. Bila username bentrok, ditambah `-2`, `-3`, dan seterusnya.
  - `AkunOpd::resetPassword(User $user): string` mengembalikan kata sandi baru dalam teks biasa.
  - `AkunOpd::ekspor(Collection $akun, string $path): void` menulis Excel dengan kolom OPD, Username, Kata Sandi Awal.
  - Kata sandi acak: 10 karakter dari alfabet tanpa karakter mirip (tanpa `0 O o 1 l I`).
  - Perintah `php artisan pemetaan:buat-akun {--output=storage/app/akun-opd.xlsx}`, dan `php artisan pemetaan:buat-admin {username}` yang menanyakan kata sandi secara interaktif.
  - Route: `GET /login`, `POST /login` (field `username`, `password`), `POST /logout`, `GET|POST /akun/password` (field `password_lama`, `password`, `password_confirmation`, minimal 8 karakter). Setelah login, admin diarahkan ke `/admin` dan OPD ke `/isi`. `GET /` diarahkan sesuai peran.
  - Middleware alias `opd` (`HanyaOpd`): user harus login, ber-role `opd`, dan punya `opd_id`. Kalau tidak, hasilnya 403.
  - Login dibatasi 5 percobaan gagal per menit per username+IP.

- [ ] **Step 1: Tulis test yang gagal.**
  - `buat akun hanya untuk opd tanpa akun`: 3 OPD, salah satunya sudah punya user → hasil 2 akun. Panggilan kedua → 0 akun.
  - `username unik dan sesuai aturan`: `KECAMATAN BANGKO` → `kec-bangko`. `BADAN PENDAPATAN DAERAH` → `bapenda`. Dua OPD dengan slug sama → `x` dan `x-2`.
  - `password tersimpan hash dan bisa dipakai login`: password hasil `buatAkun` lolos `Hash::check`, dan kolom `password` di DB berbeda dari teks biasanya.
  - `login opd diarahkan ke /isi, admin ke /admin`.
  - `login salah menampilkan pesan dan tidak masuk`.
  - `login dibatasi setelah 5 kali gagal`.
  - `ganti password butuh password lama yang benar`.
  - `reset password mengganti hash dan password lama tidak berlaku`.
- [ ] **Step 2:** Jalankan. Harus FAIL.
- [ ] **Step 3:** Implementasikan. Lengkapi map di `singkatan_opd.php` untuk ke-31 OPD non-kecamatan yang ada di `database/data/struktur.xlsx`, dengan singkatan yang lazim (Bapenda, BKPSDM, BPKAD, Diskominfo, dan seterusnya). Halaman login mengikuti kartu login di prototipe, tanpa dropdown OPD, cukup field username.
- [ ] **Step 4:** Test harus PASS.
- [ ] **Step 5:** Commit `feat: akun opd, login, ganti password`.

### Task 4: Service penilaian (tab, progres, status, kunci)

**Files:**
- Create: `app/Pemetaan/Penilaian.php`, `app/Pemetaan/TabPenilaian.php`, `app/Pemetaan/Exceptions/JawabanTerkunci.php`, `app/Pemetaan/Exceptions/BelumLengkap.php`
- Test: `tests/Unit/PenilaianTest.php`

**Interfaces:**
- Consumes: model Task 1.
- Produces (`Penilaian` di-resolve lewat container dan tidak menyimpan state):
  - `tabs(Opd $opd): Collection<TabPenilaian>`
  - `final class TabPenilaian { public Unit $kepala; public Collection $anak /* Unit dengan atribut tambahan int $kedalaman (1 untuk level di bawah kepala, 2 untuk di bawahnya) */; public string $label /* 'OPD' untuk tab pertama, selain itu kategori kepala */; units(): Collection /* kepala + anak */ }`
    - Tab pertama: kepala = unit level 0. Anak = unit dinilai yang induk level-1-nya tidak dinilai/tidak ada (yatim), beserta turunannya.
    - Tab berikutnya: satu per unit level 1 yang dinilai, urut `urutan`. Anak = semua turunan dinilai (rekursif, urutan pre-order).
    - Hanya unit `dinilai()`. Turunan dari unit level 1 yang tidak dinilai (misalnya Staf Ahli) pindah ke tab OPD.
  - `progres(Opd $opd): array{terjawab: int, total: int}`, dengan terjawab = jumlah unit dinilai yang Answer-nya `lengkap()`.
  - `simpanJawaban(Unit $unit, string $indikator, bool $nilai, User $oleh): Answer`. Lempar `InvalidArgumentException` bila indikator tidak ada di `Indikator::KODE`. Lempar `JawabanTerkunci` bila OPD `terkirim`. Ubah status `belum_mulai` → `mengisi`. Idempoten.
  - `kirim(Opd $opd): void`. Lempar `JawabanTerkunci` bila sudah terkirim, `BelumLengkap` bila `terjawab < total`. Kalau berhasil, status menjadi `terkirim` dan `submitted_at = now()`.
  - `bukaKunci(Opd $opd): void`: status menjadi `mengisi` dan `submitted_at = null`.
  - `perluDilengkapi(Opd $opd): bool`: true bila `terkirim` dan `terjawab < total`.

- [ ] **Step 1: Tulis test yang gagal** (pakai fixture kecil dari factory, bukan file Excel):
  - `tab disusun opd lalu per unit level 1 dengan turunannya`: OPD dengan Sekretariat (2 subbag) dan Bidang A (1 subbid) → 3 tab. Tab 2 berisi `units()` = [Sekretariat, subbag1, subbag2].
  - `staf ahli tidak menjadi tab dan turunan unit tidak dinilai pindah ke tab opd`.
  - `level 3 ikut di tab induk level 1 dengan kedalaman 2` (meniru Asisten → Bagian → Subbagian di Setda).
  - `unit nonaktif tidak tampil dan tidak dihitung`: unit nonaktif yang punya Answer lengkap tidak menambah `terjawab` maupun `total`.
  - `simpan jawaban membuat answer dan mengubah status ke mengisi`.
  - `simpan jawaban idempoten`: simpan `mandat=true` dua kali, lalu `mandat=false` → satu Answer dengan `mandat === false`.
  - `simpan jawaban ditolak bila terkirim` → `JawabanTerkunci`.
  - `simpan jawaban indikator tidak dikenal` → `InvalidArgumentException`.
  - `kirim ditolak bila ada unit dinilai yang belum lengkap` → `BelumLengkap`, status tidak berubah.
  - `kirim berhasil mengunci dan mencatat waktu`.
  - `buka kunci mengembalikan ke mengisi`.
  - `perlu dilengkapi bila unit baru ditambah setelah terkirim`.
- [ ] **Step 2:** Jalankan. Harus FAIL.
- [ ] **Step 3:** Implementasikan. Ambil semua unit OPD dengan satu query (beserta `answer`), lalu susun pohon di memori. `simpanJawaban` memakai `Answer::firstOrNew(['unit_id'=>…])` di dalam transaksi.
- [ ] **Step 4:** Test harus PASS.
- [ ] **Step 5:** Commit `feat: service penilaian (tab, progres, kirim, kunci)`.

### Task 5: Form pengisian OPD

**Files:**
- Create: `app/Http/Controllers/IsiController.php`, `resources/views/isi/index.blade.php`, `resources/views/isi/_kartu.blade.php`, `resources/views/isi/_ringkasan.blade.php`
- Modify: `routes/web.php`, `bootstrap/app.php` (render `AuthenticationException` untuk request JSON sebagai 401 `{"pesan":"Sesi berakhir, silakan masuk lagi"}`, dan `JawabanTerkunci` sebagai 423)
- Test: `tests/Feature/IsiTest.php`

**Interfaces:**
- Consumes: `Penilaian` (Task 4), middleware `opd` (Task 3), `Setting::ambil('kontak_admin')`.
- Produces routes (semua dengan middleware `auth`, `opd`):
  - `GET /isi`: halaman form. Data untuk Alpine di-embed sebagai JSON: tabs (judul, label, units dengan id, nama_unit, nama_jabatan, kategori, kedalaman, jawaban `{mandat,keluaran,sumber_daya}` bernilai `'ya'|'tidak'|null`), status, progres, dan kontak_admin.
  - `POST /isi/jawaban` dengan JSON `{unit_id:int, indikator:string, nilai:'ya'|'tidak'}`. Hasilnya 200 `{progres:{terjawab,total}, status}`, 403 bila unit milik OPD lain atau tidak dinilai, 422 bila validasi gagal, 423 bila terkunci, 401 bila sesi habis.
  - `POST /isi/kirim`: redirect ke `/isi`. Kalau belum lengkap, kembali dengan error `"Masih ada N unit yang belum lengkap."`.

Tampilan mengikuti `prototipe/contoh-form-bapenda.html` (tab samping/geser, kartu, legend indikator, tabel Periksa & Kirim, konfirmasi kirim). Perbedaannya dengan prototipe:
- Data diambil dari server, bukan `DATA` hardcode, dan tidak memakai localStorage.
- Klik Ya/Tidak langsung memperbarui tombol, lalu mengirim POST dengan `X-CSRF-TOKEN`. Kalau gagal, tombol kembali ke nilai sebelumnya dan pesan muncul di bawah kartu: 401 → "Sesi berakhir, silakan masuk lagi" (beserta tautan login), 423 → "Jawaban sudah dikirim dan terkunci", lainnya → "Gagal menyimpan, coba lagi". Permintaan untuk tombol yang sama dikirim berurutan (antrean per unit+indikator).
- Teks "Unit tidak sesuai? Hubungi admin: {kontak_admin}" ditampilkan di bawah judul tab, hanya bila setting itu terisi.
- Saat status `terkirim`, semua tombol nonaktif, dan muncul banner "Jawaban sudah dikirim pada {tanggal}".
- Menu header berisi Ganti kata sandi dan Keluar.
- Fungsi `pretty()` di prototipe dipindah ke helper PHP `App\Pemetaan\Format::namaUnit(string): string`, dengan aturan dan daftar singkatan yang sama. Tambahkan test unit kecil untuk "BIDANG PAJAK DAERAH II" → "Bidang Pajak Daerah II".

- [ ] **Step 1: Tulis test yang gagal:**
  - `halaman isi menampilkan tab opd sendiri`: response memuat nama unit OPD sendiri, tidak memuat unit OPD lain.
  - `admin tidak bisa membuka /isi` → 403.
  - `simpan jawaban berhasil mengembalikan progres`.
  - `simpan jawaban unit opd lain ditolak 403`, dan Answer tidak terbuat.
  - `simpan jawaban unit tidak dinilai ditolak 403`.
  - `nilai selain ya/tidak ditolak 422`.
  - `simpan jawaban saat terkirim ditolak 423`.
  - `jawaban tanpa sesi mengembalikan 401 JSON` (header `Accept: application/json`, tanpa login) → status 401 dan `json('pesan')` berisi "Sesi berakhir".
  - `kirim belum lengkap kembali dengan pesan jumlah unit`.
  - `kirim lengkap mengunci dan halaman menampilkan banner terkirim`.
  - `kontak admin tampil bila diisi`.
- [ ] **Step 2:** Jalankan. Harus FAIL.
- [ ] **Step 3:** Implementasikan controller dan view. Alpine.js dimuat dari CDN dengan versi tetap (`alpinejs@3.14.1`). CSS diambil dari `<style>` prototipe dan diletakkan di `resources/css/isi.css`, lalu di-inline di layout (tanpa build Vite).
- [ ] **Step 4:** Test harus PASS.
- [ ] **Step 5: Verifikasi manual.** Jalankan `php artisan migrate:fresh && php artisan pemetaan:impor && php artisan pemetaan:buat-akun`, lalu `php artisan serve`. Login sebagai `bapenda` dan cek di browser:
  - ada 11 tab
  - klik jawaban tersimpan dan tetap ada setelah reload
  - tampilan HP (375px) tidak menggulir ke samping
  - setelah kirim, form terkunci
  - login sebagai Setda menampilkan tab Asisten dengan Bagian dan satu Subbagian level 3, tanpa tab Staf Ahli
- [ ] **Step 6:** Commit `feat: form pengisian opd`.

### Task 6: Panel admin (Filament)

**Files:**
- Create: `app/Providers/Filament/AdminPanelProvider.php`
- Create: `app/Filament/Resources/OpdResource.php` (+ pages `ListOpds`, `ViewOpd`), `app/Filament/Resources/UnitResource.php` (+ pages), `app/Filament/Widgets/RingkasanStatus.php`, `app/Filament/Pages/Pengaturan.php`
- Modify: `app/Models/User.php` (implements `FilamentUser`, `canAccessPanel` = role admin)
- Test: `tests/Feature/AdminTest.php`

**Interfaces:**
- Consumes: `Penilaian` (Task 4), `AkunOpd` (Task 3), `Setting`.
- Produces: panel di `/admin` yang memakai login aplikasi sendiri (`->login()` dimatikan, user yang belum login diarahkan ke `/login`).
  - **Widget RingkasanStatus**: jumlah OPD Terkirim / Mengisi / Belum mulai / Perlu dilengkapi.
  - **OpdResource list**: kolom no (urutan), nama, username, status (badge), progres "terjawab/total", diubah terakhir (max `answers.updated_at`), dan tanggal kirim. Filter status ditambah opsi "Perlu dilengkapi". Header action **Unduh rekap Excel** dan **Unduh backup** (keduanya dari Task 7).
  - **Aksi per OPD**:
    - Lihat jawaban: halaman View berisi tabel unit dan 3 jawaban, dengan indentasi sesuai kedalaman, hanya baca.
    - **Buka kunci**: hanya tampil bila terkirim, dengan konfirmasi.
    - **Reset kata sandi**: dengan konfirmasi. Kata sandi baru ditampilkan satu kali di notifikasi persisten.
    - **Buat akun**: hanya tampil bila OPD belum punya user. Menampilkan username dan kata sandi sekali.
  - **Bulk/header action Buat semua akun**: mengunduh Excel hasil `AkunOpd::ekspor`.
  - **UnitResource**: filter per OPD. Form berisi nama_unit, nama_jabatan, kategori, eselon, level, parent (select unit di OPD yang sama), urutan, dinilai, aktif. **Tidak ada aksi hapus** untuk unit yang punya Answer (aksi hapus disembunyikan). Unit tanpa Answer boleh dihapus.
  - **Pengaturan**: field `kontak_admin` (teks, maksimal 200 karakter).

- [ ] **Step 1:** `composer require filament/filament:"^3.3" -W` dan `php artisan filament:install --panels` (id `admin`).
- [ ] **Step 2: Tulis test yang gagal** (Livewire testing Filament):
  - `opd tidak bisa membuka panel admin` → 403.
  - `daftar opd menampilkan status dan progres`.
  - `aksi buka kunci mengubah status ke mengisi`.
  - `aksi reset kata sandi mengganti hash`.
  - `filter perlu dilengkapi hanya menampilkan opd terkirim yang kurang`.
  - `unit dengan jawaban tidak bisa dihapus`: aksi `delete` tersembunyi.
  - `ubah induk unit hanya bisa ke unit di opd yang sama`: validasi gagal untuk parent dari OPD lain.
  - `pengaturan menyimpan kontak admin`.
- [ ] **Step 3:** Jalankan. Harus FAIL.
- [ ] **Step 4:** Implementasikan.
- [ ] **Step 5:** Test harus PASS. Cek manual di browser: `/admin` bisa dipakai, kata sandi muncul sekali saja.
- [ ] **Step 6:** Commit `feat: panel admin filament`.

### Task 7: Rekap Excel dan backup

**Files:**
- Create: `app/Exports/RekapExport.php`, `app/Exports/RingkasanOpdSheet.php`, `app/Exports/JawabanUnitSheet.php`, `app/Pemetaan/Backup.php`, `app/Console/Commands/BackupCommand.php`
- Create: `app/Http/Controllers/Admin/UnduhController.php`, route `GET /admin/unduh/rekap` dan `GET /admin/unduh/backup` (hanya admin)
- Modify: `routes/console.php` (jadwal `pemetaan:backup` harian 02:00)
- Test: `tests/Feature/RekapTest.php`, `tests/Feature/BackupTest.php`

**Interfaces:**
- Consumes: `Penilaian::tabs`, `Penilaian::progres` (Task 4), `Indikator`.
- Produces:
  - `new RekapExport()`, diunduh sebagai `rekap-pemetaan-YYYYMMDD-HHmm.xlsx`.
    - Sheet `Ringkasan OPD`: No, OPD, Status (label), Unit Dinilai, Unit Lengkap, Tanggal Kirim, Ya Mandat Formal, Ya Keluaran Utama, Ya Sumber Daya Khusus.
    - Sheet `Jawaban per Unit`, berurutan per OPD lalu per tab: OPD, Level, Unit Organisasi (diawali 4 spasi × kedalaman), Jabatan, Kategori, Eselon, Induk, Mandat Formal, Keluaran Utama, Sumber Daya Khusus (`Ya`/`Tidak`/kosong), Jumlah Ya (0–3). Header tebal, freeze baris 1, autofilter.
  - `Backup::buat(?string $tujuan = null): string` menjalankan `VACUUM INTO` ke `storage/app/backup/pemetaan-YYYYMMDD-HHmmss.sqlite` lalu mengembalikan path-nya. `Backup::bersihkan(int $simpanHari = 7): int` menghapus file backup yang lebih lama dari batas.
  - Perintah `php artisan pemetaan:backup` = `buat()` + `bersihkan()`.
  - `GET /admin/unduh/backup` membuat backup baru dan mengunduhnya sebagai `pemetaan-backup-YYYYMMDD-HHmm.sqlite`.

- [ ] **Step 1: Tulis test yang gagal:**
  - `rekap memuat semua opd termasuk belum mulai`: 2 OPD, salah satunya tanpa jawaban → sheet Ringkasan punya 2 baris data, dan OPD kosong berstatus "Belum mulai" dengan Unit Lengkap 0.
  - `rekap jawaban per unit menulis Ya/Tidak/kosong dan jumlah ya`.
  - `rekap mengecualikan unit nonaktif dan tidak dinilai`.
  - `hanya admin bisa unduh rekap dan backup` (OPD → 403).
  - `backup menghasilkan file sqlite yang bisa dibuka dan berisi data`: buka file hasil dengan koneksi PDO baru, lalu `count(*)` dari `opds` sama dengan DB asli. Untuk test ini, pakai database SQLite file sementara, bukan `:memory:`.
  - `bersihkan menghapus backup lebih lama dari 7 hari`.
- [ ] **Step 2:** Jalankan. Harus FAIL.
- [ ] **Step 3:** Implementasikan.
- [ ] **Step 4:** Test harus PASS. Buka file rekap hasil data asli dan cek isinya: 49 baris ringkasan, dan jumlah baris unit sama dengan total unit dinilai.
- [ ] **Step 5:** Commit `feat: rekap excel dan backup`.

### Task 8: Docker

**Files:**
- Create: `aplikasi/Dockerfile`, `aplikasi/.dockerignore`, `aplikasi/docker/entrypoint.d/50-pemetaan.sh`, `aplikasi/compose.yaml`, `aplikasi/.env.production.example`
- Create: `docs/PANDUAN-PASANG.md` (root proyek)

**Interfaces:**
- Produces:
  - Image dari `serversideup/php:8.3-fpm-nginx` dengan ekstensi `pdo_sqlite`, `intl`, `zip`, `gd` untuk Excel. Isinya `composer install --no-dev --optimize-autoloader`. `AUTORUN_ENABLED=true` (menjalankan migrate dan cache). Port 8080 di dalam container.
  - Entrypoint `50-pemetaan.sh`: membuat `storage/database.sqlite` bila belum ada, lalu menjalankan `php artisan pemetaan:impor` **hanya** bila tabel `opds` kosong.
  - `compose.yaml`, proyek `pemetaan`, dengan service:
    - `app`: container_name `pemetaan_app`, port `127.0.0.1:8090:8080`, volume bernama `pemetaan_storage` → `/var/www/html/storage`, `mem_limit: 256m`, `restart: unless-stopped`, `env_file: .env`
    - `scheduler`: image sama, perintah `php artisan schedule:work`, volume sama, `mem_limit: 128m`
  - `DB_DATABASE=/var/www/html/storage/database.sqlite` (file database tinggal di volume).
  - `PANDUAN-PASANG.md` berisi:
    1. salin kode ke `/srv/pemetaan`
    2. `cp .env.production.example .env` lalu isi `APP_KEY` dengan `php artisan key:generate --show` dari container
    3. `docker compose up -d --build`
    4. `docker compose exec app php artisan pemetaan:buat-admin <nama>`
    5. buat akun OPD dan unduh Excel-nya
    6. akses lewat SSH tunnel `ssh -L 8090:localhost:8090 ubuntu@43.173.8.198`
    7. update: `git pull` lalu `docker compose up -d --build`
    8. backup: unduh dari panel admin
    9. (nanti) langkah domain: menyambungkan ke `akupeta_nginx`, ditandai "jangan dijalankan tanpa persetujuan pemilik"

- [ ] **Step 1:** Tulis file-file di atas.
- [ ] **Step 2: Verifikasi build di VPS tanpa menjalankan aplikasi.** Salin ke `/srv/pemetaan` (rsync, tanpa `vendor/`, `node_modules/`, `.env`, `database/*.sqlite`), lalu jalankan `cd /srv/pemetaan && sudo docker compose build`. Build harus sukses. Lokal tidak punya Docker. **Sebelum langkah ini, minta izin pemilik**, karena ini menulis ke VPS bersama.
- [ ] **Step 3:** Commit `chore: docker dan panduan pasang`.

### Task 9: Pasang ke VPS (tahap uji)

**Prasyarat:** pemilik sudah menyetujui deploy. Ingat bahwa VPS ini dipakai bersama situs lain (lihat spec 7a).

- [ ] **Step 1:** Ikuti `docs/PANDUAN-PASANG.md` langkah 1–5. Simpan Excel akun OPD ke root proyek lokal di `output/akun-opd.xlsx` (masukkan ke `.gitignore`), dan jangan tampilkan isinya di chat.
- [ ] **Step 2: Verifikasi.**
  - `sudo docker ps` menampilkan `pemetaan_app` dan `pemetaan-scheduler-1` dalam keadaan Up, dan container `akupeta_*` tetap Up.
  - `curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1:8090/login` di VPS mengembalikan `200`.
  - `sudo ss -tlnp | grep 8090` hanya menunjukkan `127.0.0.1`.
  - `curl -sI https://akupeta.nekomade.com` masih mengembalikan 200 atau 302 (situs lain tidak terganggu).
- [ ] **Step 3:** Lewat SSH tunnel, login sebagai admin dan sebagai satu OPD di browser. Coba isi satu jawaban, unduh rekap, dan unduh backup.
- [ ] **Step 4:** Commit penyesuaian bila ada.
