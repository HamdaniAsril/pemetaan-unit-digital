# Panduan Pemasangan di Server Docker (root) — Pemetaan Unit Digital

> **Server Pemda memakai CloudPanel: pakai [PANDUAN-CLOUDPANEL.md](PANDUAN-CLOUDPANEL.md).** Panduan ini untuk server lain yang memakai Docker dengan akses root.

Untuk: **tim IT Pemda** yang memasang aplikasi di server instansi.
Hasil akhir: aplikasi berjalan di domain Pemda (misalnya `https://pemetaan.contoh.go.id`). Setiap pembaruan kode di GitHub **terpasang otomatis** di server dalam ±2 menit, setelah seluruh test lulus.

Ada dua pihak di panduan ini:
- **Tim IT Pemda**, yang memegang server
- **Pemilik repo**, yang memegang kode di GitHub

---

## 1. Yang perlu disiapkan tim IT Pemda

| Kebutuhan | Keterangan |
|---|---|
| Server Linux dengan systemd | Ubuntu 22.04/24.04 atau Debian 12 disarankan. Minimal 2 CPU, RAM 2 GB, dan ruang disk kosong 10 GB. |
| Docker Engine + plugin `docker compose` | https://docs.docker.com/engine/install/ |
| Node.js 18 atau lebih baru | Dipakai layanan kecil penerima webhook. |
| git, openssl, curl | Biasanya sudah terpasang. |
| Nginx | Sebagai pintu masuk dari domain ke aplikasi. |
| Domain atau subdomain | Mengarah ke IP publik server, misalnya `pemetaan.contoh.go.id`. |
| Sertifikat HTTPS | Let's Encrypt (`certbot`) atau sertifikat resmi instansi. |
| Akses keluar ke `github.com` | HTTPS (port 443) sudah cukup. Repo-nya publik, jadi kode diambil tanpa kunci. |
| Akses masuk port 443 dari internet | Supaya GitHub bisa mengirim pemberitahuan pembaruan (webhook). |

Aplikasi dan webhook **hanya mendengar di `127.0.0.1`** (bawaan port 8090 dan 9010). Tidak ada port baru yang perlu dibuka di firewall selain 80/443 untuk Nginx.

## 2. Langkah pemasangan

Berkas `pasang.sh` dikirim oleh pemilik repo. Salin ke server, lalu:

### Langkah 1: periksa prasyarat (tidak mengubah apa pun)
```bash
sudo bash pasang.sh --cek
```
Semua baris harus bertanda ✔. Kalau port 8090 atau 9010 sudah dipakai program lain, tambahkan `--port` atau `--port-webhook` dengan nomor lain di semua perintah berikutnya.

### Langkah 2: jalankan pemasangan
```bash
sudo bash pasang.sh --domain pemetaan.contoh.go.id
```
Skrip akan:
- mengambil kode lewat HTTPS ke `/srv/pemetaan`, tanpa kunci karena repo-nya publik
- membuat pengaturan dan rahasia
- memasang layanan `pemetaan-webhook` dan `pemetaan-deploy`
- menjalankan **deploy pertama**, yaitu build dan seluruh test. Ini butuh beberapa menit. Database dibuat dan data 49 OPD diimpor otomatis.

Skrip ini aman dijalankan berulang. Yang sudah ada tidak ditimpa.

> **Hanya bila repo suatu saat dijadikan privat:** skrip beralih ke SSH dan berhenti sambil menampilkan kunci publik. Kirim kunci itu ke pemilik repo, lalu jalankan ulang perintah yang sama.

### Langkah 3: Nginx dan HTTPS
Skrip menyiapkan konfigurasi siap pakai di `/etc/pemetaan/nginx-pemetaan.conf`:
```bash
sudo cp /etc/pemetaan/nginx-pemetaan.conf /etc/nginx/sites-available/pemetaan
sudo ln -s /etc/nginx/sites-available/pemetaan /etc/nginx/sites-enabled/pemetaan
sudo certbot --nginx -d pemetaan.contoh.go.id      # atau pasang sertifikat instansi
sudo nginx -t && sudo systemctl reload nginx
```
Kalau memakai sertifikat instansi, sesuaikan dua baris `ssl_certificate` di berkas itu.

### Langkah 4: kirim data webhook ke pemilik repo (lewat jalur PRIBADI)
```bash
sudo grep WEBHOOK_SECRET /etc/pemetaan/webhook.env
```
Kirim **URL** `https://pemetaan.contoh.go.id/hooks/github` dan **rahasia** di atas ke pemilik repo, misalnya lewat pesan pribadi. Rahasia ini **jangan** dikirim ke grup atau ditulis di dokumen bersama.

### Langkah 5: buat akun admin
```bash
cd /srv/pemetaan/aplikasi && sudo docker compose exec pemetaan php artisan pemetaan:buat-admin admin
```
Kata sandinya ditanyakan dua kali. Setelah itu, admin bisa masuk di `https://pemetaan.contoh.go.id/login`.

### Langkah 6: siapkan akun OPD (oleh admin, di panel)
1. Buka **Pengaturan**: isi kontak admin, lalu periksa dan simpan teks petunjuk menjawab.
2. Buka menu **OPD**, lalu klik **Buat semua akun**. Excel berisi username dan kata sandi awal ke-49 OPD akan terunduh, dan hanya bisa diunduh sekali. **Excel inilah yang dibagikan ke OPD.** Akun dari server lain tidak berlaku di sini.

## 3. Yang dikerjakan pemilik repo

1. *(Hanya bila repo privat)* **mendaftarkan kunci publik** dari tim Pemda: Settings → Deploy keys → Add deploy key, dengan akses tulis tidak dicentang.
2. **Mendaftarkan webhook** dari Langkah 4: Settings → Webhooks → Add webhook.
   - Payload URL: `https://<domain>/hooks/github`
   - Content type: `application/json`
   - Secret: rahasia dari tim Pemda
   - Event: *Just the push event*
3. Mengecek tab **Recent Deliveries**. Ping pertama harus menjawab **200**.

## 4. Memeriksa bahwa semuanya bekerja

| Pemeriksaan | Perintah / cara | Hasil yang benar |
|---|---|---|
| Aplikasi | buka `https://<domain>/login` | Halaman masuk tampil |
| Layanan webhook | `systemctl status pemetaan-webhook` | `active (running)` |
| Deploy otomatis | pemilik repo melakukan push ke `main`, lalu `journalctl -u pemetaan-deploy -n 30` | Muncul `commit: …`, `Tests: … passed`, dan `== selesai ==` |
| Container | `cd /srv/pemetaan/aplikasi && sudo docker compose ps` | `pemetaan_app` (healthy) dan `pemetaan_scheduler` |

Kalau ada test yang gagal saat deploy, deploy **berhenti**, dan versi yang sedang berjalan **tidak tersentuh**.

## 5. Operasional sehari-hari

- **Backup otomatis:** setiap hari pukul 02.00 dan sebelum setiap deploy, disimpan 7 hari di dalam volume Docker `pemetaan_pemetaan_storage`.
- **Backup manual:** panel admin → menu OPD → **Unduh backup**. Hasilnya satu file `.sqlite`. Sebaiknya disimpan juga di luar server secara rutin.
- **Deploy manual** (tanpa menunggu push): `sudo systemctl start pemetaan-deploy`.
- **Lokasi penting:**

| Isi | Lokasi |
|---|---|
| Kode | `/srv/pemetaan` |
| Pengaturan aplikasi | `/srv/pemetaan/aplikasi/.env` |
| Pengaturan deploy | `/etc/pemetaan/deploy.env` |
| Rahasia webhook | `/etc/pemetaan/webhook.env` |
| Deploy key | `/etc/pemetaan/deploy_key` |

## 6. Pindah data dari server lama (hanya bila suatu saat diperlukan)

Lakukan saat tidak ada OPD yang sedang mengisi.

1. Di aplikasi lama: panel admin → **Unduh backup**.
2. Di server Pemda:
```bash
cd /srv/pemetaan/aplikasi
sudo docker compose stop pemetaan scheduler
sudo docker compose cp pemetaan-backup-XXXX.sqlite pemetaan:/var/www/html/storage/database.sqlite
sudo docker compose run --rm --no-deps -e PEMETAAN_PERAN=setup pemetaan rm -f /var/www/html/storage/database.sqlite-wal /var/www/html/storage/database.sqlite-shm
sudo docker compose start pemetaan scheduler
```
3. Umumkan alamat baru ke OPD. Akun dan kata sandi OPD ikut pindah bersama database.

## 7. Bila ada masalah

| Gejala | Penyebab umum | Langkah |
|---|---|---|
| Skrip berhenti di "GitHub tidak terjangkau" | Jaringan memblokir HTTPS keluar ke GitHub | Buka akses keluar ke `github.com:443` |
| Skrip berhenti menampilkan kunci publik | Repo sedang privat, dan kunci belum didaftarkan | Kirim kunci ke pemilik repo, tunggu, lalu jalankan ulang |
| Webhook di GitHub tidak 200 | Nginx belum meneruskan `/hooks/github`, atau rahasia beda | `journalctl -u pemetaan-webhook -n 30`; cocokkan rahasia di GitHub dengan `webhook.env` |
| Push tidak ter-deploy | Test gagal | `journalctl -u pemetaan-deploy -n 80`; cari baris `FAILED` |
| Halaman 502 | Container belum siap atau berhenti | `cd /srv/pemetaan/aplikasi && sudo docker compose ps` dan `docker compose logs pemetaan` |
