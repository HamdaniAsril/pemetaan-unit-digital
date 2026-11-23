# Panduan Pemasangan di Server Pemda (CloudPanel) — Pemetaan Unit Digital

Untuk: **tim IT Pemda** yang mengelola server dengan **CloudPanel**.
Hasil akhir: aplikasi berjalan sebagai **site PHP 8.4** di CloudPanel (misalnya `https://pemetaan.contoh.go.id`). Setiap pembaruan kode di GitHub **terpasang otomatis** lewat cron dalam ±1–3 menit, setelah seluruh test lulus. Webhook tidak wajib.

Tidak perlu akses root dan tidak perlu Docker. Semua berjalan sebagai **site user** CloudPanel.

Ada dua pihak di panduan ini:
- **Tim IT Pemda**, yang memegang server dan CloudPanel
- **Pemilik repo**, yang memegang kode di GitHub

---

## 1. Yang perlu disiapkan

| Kebutuhan | Keterangan |
|---|---|
| CloudPanel dengan **PHP 8.4** | Ekstensi `pdo_sqlite`, `sqlite3`, `intl`, `gd`, `zip`, `mbstring` aktif. Skrip memeriksanya. |
| Domain atau subdomain | Mengarah ke IP server, misalnya `pemetaan.contoh.go.id`. |
| Akses SSH sebagai **site user** | Disediakan CloudPanel (menu site → SSH/FTP). |
| Akses keluar ke `github.com` | HTTPS (port 443) sudah cukup. Repo-nya publik, jadi kode diambil tanpa kunci. |
| Akses masuk HTTPS (443) dari internet | Supaya GitHub bisa mengirim pemberitahuan pembaruan. |

Database memakai **SQLite** (satu berkas). Tidak perlu membuat database MySQL di CloudPanel.

## 2. Membuat site di CloudPanel

1. **Sites → Add Site → Create a PHP Site**
   - Application: **Laravel** (atau Generic)
   - Domain Name: `pemetaan.contoh.go.id`
   - PHP Version: **8.4**
   - Site User: misalnya `pemetaan` (catat kata sandinya)
2. Buka site itu, lalu masuk ke tab **Settings**. Ubah **Root Directory** menjadi `aplikasi/public`, lalu simpan.
3. Di tab **SSL/TLS**, pilih **Actions → New Let's Encrypt Certificate**, lalu terbitkan sertifikat.

## 3. Menjalankan skrip pasang

**Masuk SSH sebagai site user** (bukan root), lalu unduh skrip pasang langsung dari GitHub:

```bash
ssh pemetaan@<ip-server>
curl -fsSLO https://raw.githubusercontent.com/HamdaniAsril/pemetaan-unit-digital/main/deploy/pasang-cloudpanel.sh
```

### Langkah 1: periksa prasyarat (tidak mengubah apa pun)
```bash
bash pasang-cloudpanel.sh --cek
```
Semua baris harus bertanda ✔.

### Langkah 2: pasang
```bash
bash pasang-cloudpanel.sh --domain pemetaan.contoh.go.id
```
Skrip akan:
- mengambil kode lewat HTTPS ke `~/htdocs/pemetaan.contoh.go.id`. Repo-nya publik, jadi tidak perlu kunci. Isi contoh dari CloudPanel di folder itu dipindah ke `~/.pemetaan/`, tidak dihapus.
- membuat `.env` dengan kunci aplikasi dan rahasia webhook acak
- menjalankan **seluruh test** di folder terpisah
- kalau semua lulus: memasang dependensi, membuat **database baru**, dan mengimpor **49 OPD dan 605 unit**

Skrip ini aman dijalankan berulang. Kunci aplikasi, rahasia, dan database yang sudah ada tidak ditimpa.

> **Hanya bila repo suatu saat dijadikan privat:** skrip otomatis beralih ke SSH dan berhenti sambil menampilkan **kunci publik** (`ssh-ed25519 …`). Kirim kunci itu ke pemilik repo untuk didaftarkan, lalu jalankan ulang perintah yang sama.

> **Data awal:** server Pemda memulai dengan database baru. Struktur OPD dan unit sudah terisi, sedangkan akun, jawaban, dan pengaturan masih kosong.

## 4. Cron job (di CloudPanel)

Buka site, lalu tab **Cron Jobs → Add Cron Job**. Tambahkan **dua** cron, keduanya dengan jadwal **setiap menit** (`* * * * *`). Perintah tepatnya dicetak di akhir skrip, kira-kira seperti ini:

| Untuk | Perintah |
|---|---|
| Backup harian dan tugas terjadwal | `php8.4 /home/pemetaan/htdocs/pemetaan.contoh.go.id/aplikasi/artisan schedule:run >> /dev/null 2>&1` |
| Deploy otomatis | `/home/pemetaan/htdocs/pemetaan.contoh.go.id/deploy/deploy-cloudpanel.sh >> /dev/null 2>&1` |

Cron boleh dipasang lewat tab Cron Jobs CloudPanel maupun `/etc/cron.d` (dengan kolom user = site user). Skrip tidak boleh dijalankan sebagai root.

Cron kedua mengecek GitHub setiap menit dan **hanya men-deploy kalau ada commit baru**. Kalau tidak ada perubahan, ia selesai dalam hitungan detik tanpa test, tanpa mode pemeliharaan, dan tanpa backup. Jadi cron ini saja **sudah cukup untuk deploy otomatis**, tanpa webhook.

> **Server yang dipasang sebelum 22 November 2026** (cron `… --bila-diminta`): skrip lama hanya men-deploy bila ada tanda dari webhook. Jalankan **sekali** secara manual sebagai site user `~/htdocs/<domain>/deploy/deploy-cloudpanel.sh --paksa` (dari root: `sudo -u <site-user> -i /home/<site-user>/htdocs/<domain>/deploy/deploy-cloudpanel.sh --paksa`). Setelah itu cron lama boleh dibiarkan: skrip baru memperlakukan `--bila-diminta` sama dengan tanpa flag.

**Cara memastikan deploy otomatis bekerja:** masuk ke panel admin. Di bawah menu samping tertulis **"Versi <kode commit> · <waktu deploy>"**. Setelah ada push ke `main`, dalam 1–3 menit kodenya harus berubah mengikuti commit terbaru di GitHub.

## 5. (Opsional) Webhook GitHub

Tidak wajib. Webhook hanya membuat deploy dimulai beberapa detik setelah push, tanpa menunggu giliran cron.
- Kalau situs berada di belakang **Cloudflare** dengan tantangan anti-bot, buat dulu aturan WAF **Skip** untuk `POST /hooks/github` di host ini. Tanpa aturan itu, GitHub akan diblokir.
- Kirim **URL** `https://pemetaan.contoh.go.id/hooks/github` dan **rahasia** dari `grep GITHUB_WEBHOOK_SECRET ~/htdocs/pemetaan.contoh.go.id/aplikasi/.env` ke pemilik repo **lewat pesan pribadi**.

## 6. Akun admin dan akun OPD

```bash
php8.4 ~/htdocs/pemetaan.contoh.go.id/aplikasi/artisan pemetaan:buat-admin admin
```
Kata sandinya ditanyakan dua kali. Setelah itu, admin masuk di `https://pemetaan.contoh.go.id/login`, lalu:
1. Buka **Pengaturan**: isi kontak admin, lalu periksa dan simpan teks petunjuk menjawab.
2. Buka menu **OPD**, lalu klik **Impor akun dari Excel**, dan unggah berkas **USER PENDATAAN.xlsx** (kolom OPD, Username, Password) dari Pemda. Ke-49 akun OPD langsung memakai username dan kata sandi dari berkas itu. Berkasnya tidak disimpan di server.
   - Ringkasan hasil impor tampil di layar: berapa akun dibuat, diperbarui, dan dilewati beserta alasannya.
   - Tombol **Buat semua akun** (username dan kata sandi acak) hanya dipakai kalau tidak ada berkas akun dari Pemda.

## 7. Yang dikerjakan pemilik repo

1. *(Opsional)* **mendaftarkan webhook** dari bagian 5: Settings → Webhooks → Add webhook.
   - Payload URL: `https://<domain>/hooks/github`
   - Content type: `application/json`
   - Secret: rahasia dari tim Pemda
   - Event: *Just the push event*
2. Mengecek **Recent Deliveries**. Ping pertama harus menjawab **200** (`pong`).
3. *(Hanya bila repo privat)* mendaftarkan kunci publik dari tim Pemda: Settings → Deploy keys → Add deploy key. Biarkan centang "Allow write access" kosong.

## 8. Cara kerja deploy otomatis

```
git push ke main
  → cron (tiap menit) menjalankan deploy-cloudpanel.sh, yang mengecek GitHub:
       tidak ada commit baru → selesai (beberapa detik, tidak mengubah apa pun)
       ada commit baru →
         1. ambil kode terbaru
         2. jalankan SELURUH test di folder terpisah  ← gagal = berhenti, situs tidak tersentuh
         3. backup database
         4. mode pemeliharaan (±20 detik) → perbarui kode, dependensi, migrasi, cache → situs dibuka lagi
         5. catat versi baru (tampil di panel admin)
  (opsional) webhook GitHub → aplikasi menaruh tanda → cron berikutnya langsung men-deploy
```

**Log deploy:**

```bash
tail -50 ~/.pemetaan/deploy.log
```

**Deploy manual:**

```bash
~/htdocs/<domain>/deploy/deploy-cloudpanel.sh --paksa
```
Tanpa `--paksa`, skrip hanya men-deploy kalau ada commit baru.

## 9. Operasional

- **Backup otomatis:** setiap hari pukul 02.00 (lewat cron `schedule:run`) dan sebelum setiap deploy, di `aplikasi/storage/app/backup/` dengan masa simpan 7 hari.
- **Backup manual:** panel admin → menu OPD → **Unduh backup** (berkas `.sqlite`). Sebaiknya disimpan juga di luar server secara rutin.
- **Lokasi penting:**

| Isi | Lokasi |
|---|---|
| Kode | `~/htdocs/<domain>` (root web: `aplikasi/public`) |
| Pengaturan aplikasi | `aplikasi/.env` (setelah mengubahnya, jalankan `php8.4 artisan optimize` agar terbaca) |
| Database | `aplikasi/storage/database.sqlite` |
| Pengaturan dan log deploy | `~/.pemetaan/` |

## 10. Bila ada masalah

| Gejala | Penyebab umum | Langkah |
|---|---|---|
| `--cek` gagal di ekstensi PHP | Ekstensi belum aktif di PHP 8.4 site | Aktifkan lewat CloudPanel (PHP settings) atau minta admin server |
| Skrip berhenti menampilkan kunci publik | Repo sedang privat, dan kunci belum didaftarkan | Kirim kunci ke pemilik repo, tunggu, lalu jalankan ulang |
| "GitHub tidak terjangkau" | HTTPS keluar ke github.com diblokir | Minta jaringan membuka akses keluar ke `github.com:443` |
| Halaman 404 di semua alamat | Root Directory belum `aplikasi/public` | Ubah di Settings site |
| Webhook di GitHub tidak 200 (opsional) | Diblokir Cloudflare, rahasia beda, atau situs sedang dalam mode pemeliharaan | Aturan WAF Skip untuk `/hooks/github`; cocokkan rahasia; coba Redeliver |
| Versi di panel admin tidak berubah setelah push | Cron deploy belum ada, skrip masih versi sebelum 22 November 2026 (lihat bagian 4), atau test gagal | Cek tab Cron Jobs; `tail -80 ~/.pemetaan/deploy.log`, cari `GAGAL` |
| Situs tertahan di "Service Unavailable" | Deploy terhenti di tengah | `php8.4 ~/htdocs/<domain>/aplikasi/artisan up`, lalu periksa log deploy |
