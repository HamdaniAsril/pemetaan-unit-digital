# Panduan Pemasangan di Server Pemda (CloudPanel) — Pemetaan Unit Digital

Untuk: **tim IT Pemda** yang mengelola server dengan **CloudPanel**.
Hasil akhir: aplikasi berjalan sebagai **site PHP 8.4** di CloudPanel (misalnya `https://pemetaan.contoh.go.id`). Setiap pembaruan kode di GitHub **terpasang otomatis** dalam ±1–3 menit, setelah seluruh test lulus.

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
- kalau semua lulus: memasang dependensi, membuat **database baru**, dan mengimpor **49 OPD dan 603 unit**

Skrip ini aman dijalankan berulang. Kunci aplikasi, rahasia, dan database yang sudah ada tidak ditimpa.

> **Hanya bila repo suatu saat dijadikan privat:** skrip otomatis beralih ke SSH dan berhenti sambil menampilkan **kunci publik** (`ssh-ed25519 …`). Kirim kunci itu ke pemilik repo untuk didaftarkan, lalu jalankan ulang perintah yang sama.

> **Data awal:** server Pemda memulai dengan database baru. Struktur OPD dan unit sudah terisi, sedangkan akun, jawaban, dan pengaturan masih kosong.

## 4. Cron job (di CloudPanel)

Buka site, lalu tab **Cron Jobs → Add Cron Job**. Tambahkan **dua** cron, keduanya dengan jadwal **setiap menit** (`* * * * *`). Perintah tepatnya dicetak di akhir skrip, kira-kira seperti ini:

| Untuk | Perintah |
|---|---|
| Backup harian dan tugas terjadwal | `php8.4 /home/pemetaan/htdocs/pemetaan.contoh.go.id/aplikasi/artisan schedule:run >> /dev/null 2>&1` |
| Deploy otomatis | `/home/pemetaan/htdocs/pemetaan.contoh.go.id/deploy/deploy-cloudpanel.sh --bila-diminta >> /dev/null 2>&1` |

Cron kedua hanya bekerja kalau ada tanda dari webhook. Selain itu, ia langsung selesai tanpa melakukan apa pun.

## 5. Kirim data webhook ke pemilik repo (lewat jalur PRIBADI)

```bash
grep GITHUB_WEBHOOK_SECRET ~/htdocs/pemetaan.contoh.go.id/aplikasi/.env
```
Kirim **URL** `https://pemetaan.contoh.go.id/hooks/github` dan **rahasia** di atas ke pemilik repo lewat pesan pribadi. Rahasia ini **jangan** dikirim ke grup atau ditulis di dokumen bersama.

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

1. **Mendaftarkan webhook** dari bagian 5: Settings → Webhooks → Add webhook.
   - Payload URL: `https://<domain>/hooks/github`
   - Content type: `application/json`
   - Secret: rahasia dari tim Pemda
   - Event: *Just the push event*
2. Mengecek **Recent Deliveries**. Ping pertama harus menjawab **200** (`pong`).
3. *(Hanya bila repo privat)* mendaftarkan kunci publik dari tim Pemda: Settings → Deploy keys → Add deploy key. Biarkan centang "Allow write access" kosong.

## 8. Cara kerja deploy otomatis

```
git push ke main
  → GitHub memanggil https://<domain>/hooks/github (tanda tangan HMAC dicek oleh aplikasi)
  → aplikasi menaruh tanda "deploy diminta" (aplikasi tidak menjalankan perintah apa pun)
  → cron (tiap menit) melihat tanda → deploy-cloudpanel.sh:
       1. ambil kode terbaru
       2. jalankan SELURUH test di folder terpisah  ← gagal = berhenti, situs tidak tersentuh
       3. backup database
       4. mode pemeliharaan (±20 detik) → perbarui kode, dependensi, migrasi, cache → situs dibuka lagi
```

**Log deploy:**

```bash
tail -50 ~/.pemetaan/deploy.log
```

**Deploy manual:**

```bash
~/htdocs/<domain>/deploy/deploy-cloudpanel.sh
```

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
| Webhook di GitHub tidak 200 | Rahasia beda, atau situs sedang dalam mode pemeliharaan | Cocokkan rahasia di GitHub dengan `.env`; coba Redeliver |
| Push tidak ter-deploy | Cron deploy belum dibuat, atau test gagal | Cek tab Cron Jobs; `tail -80 ~/.pemetaan/deploy.log`, cari `GAGAL` |
| Situs tertahan di "Service Unavailable" | Deploy terhenti di tengah | `php8.4 ~/htdocs/<domain>/aplikasi/artisan up`, lalu periksa log deploy |
