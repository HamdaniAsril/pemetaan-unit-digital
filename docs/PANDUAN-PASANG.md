# Panduan Pasang — Pemetaan Unit Digital

Aplikasi berjalan sebagai proyek Docker Compose **terpisah** bernama `pemetaan` di VPS `ubuntu@43.173.8.198`. Isinya dua container:

| Container | Fungsi |
|---|---|
| `pemetaan_app` | Web (PHP + Nginx internal). Hanya mendengar di `127.0.0.1:8090`, jadi tidak terbuka ke internet. |
| `pemetaan_scheduler` | Menjalankan backup otomatis setiap hari pukul 02:00 dan menyimpannya 7 hari. |

Database berupa satu file SQLite di volume `pemetaan_storage`.

Situs lain di VPS ini (`akupeta`, `bpkadpost`, `sopku`, dan container Nginx bersama `akupeta_nginx`) **tidak disentuh** oleh langkah 1–7.

## 1. Kirim kode ke VPS

Dari laptop, jalankan di folder proyek:

```bash
rsync -az --delete --exclude vendor --exclude node_modules --exclude .env --exclude 'storage/logs/*' --exclude 'storage/framework/*/*' --exclude 'storage/app/backup' --exclude 'database/*.sqlite' --exclude 'storage/*.sqlite' --rsync-path="sudo rsync" aplikasi/ ubuntu@43.173.8.198:/srv/pemetaan/
```

## 2. Siapkan `.env` (sekali saja)

```bash
ssh ubuntu@43.173.8.198
cd /srv/pemetaan
sudo cp .env.production.example .env
sudo docker compose build
sudo docker compose run --rm --no-deps -e PEMETAAN_PERAN=setup app php artisan key:generate --show
```

Salin hasil perintah terakhir (diawali `base64:`) ke baris `APP_KEY=` di `.env`, misalnya dengan `sudo nano .env`.

## 3. Jalankan

```bash
sudo docker compose up -d
sudo docker compose logs app | tail -20
```

Saat pertama kali dijalankan, aplikasi otomatis membuat database dan mengimpor struktur 49 OPD. Di log akan muncul `OPD baru: 49`.

## 4. Buat akun admin

```bash
sudo docker compose exec app php artisan pemetaan:buat-admin admin
```

Perintah ini akan menanyakan kata sandi, minimal 8 karakter.

## 5. Buka aplikasi lewat SSH tunnel

Selama domain belum ada, aplikasi dibuka dari laptop lewat tunnel:

```bash
ssh -N -L 8090:127.0.0.1:8090 ubuntu@43.173.8.198
```

Biarkan terminal itu tetap terbuka, lalu buka **http://localhost:8090** di browser dan login sebagai admin.

## 6. Buat akun OPD

Di panel admin, buka menu **OPD**, lalu klik **Buat semua akun**. File `akun-opd.xlsx` akan terunduh, berisi username dan kata sandi awal setiap OPD.

- File ini **hanya bisa diunduh sekali**. Kata sandi tidak disimpan dalam bentuk yang bisa dibaca.
- Kalau ada OPD yang lupa kata sandi, gunakan aksi **Reset kata sandi** di baris OPD tersebut.

Isi juga **Pengaturan → Kontak admin**, misalnya nomor WhatsApp. Kontak ini tampil di form OPD.

## 7. Backup

- **Manual**: di menu OPD, klik **Unduh backup**. Hasilnya satu file `.sqlite`, sebaiknya disimpan di laptop atau Drive secara rutin.
- **Otomatis**: tersimpan di volume, di `storage/app/backup/`, dengan masa simpan 7 hari.

Memulihkan dari file backup (aplikasi berhenti sebentar):

```bash
sudo docker compose stop app scheduler
sudo docker compose cp pemetaan-backup-XXXX.sqlite app:/var/www/html/storage/database.sqlite
# Hapus file WAL lama supaya tidak ditimpakan ke database hasil pemulihan:
sudo docker compose run --rm --no-deps -e PEMETAAN_PERAN=setup app rm -f /var/www/html/storage/database.sqlite-wal /var/www/html/storage/database.sqlite-shm
sudo docker compose start app scheduler
```

## Update aplikasi

Ulangi langkah 1 (rsync), lalu jalankan:

```bash
cd /srv/pemetaan && sudo docker compose up -d --build
```

Migrasi database berjalan otomatis, dan data OPD tidak hilang.

## (Nanti) Menyambungkan ke domain

> ⚠️ **Jangan jalankan tanpa persetujuan pemilik.** Langkah ini mengubah Nginx bersama (`akupeta_nginx`) yang juga melayani situs lain.

1. Arahkan DNS domain atau subdomain ke `43.173.8.198`.
2. Sambungkan container ke jaringan Nginx bersama:
   ```bash
   sudo docker network connect akupeta_akupeta_net pemetaan_app
   ```
   Agar sambungan ini permanen, tambahkan network eksternal tersebut di `compose.yaml`.
3. Tambahkan file `pemetaan.conf` di `/home/akupeta/akupeta/docker/prod/nginx/conf.d/`, mengikuti pola `sopku.conf`. Pakai `proxy_pass http://pemetaan_app:8080;`, `proxy_set_header Host $host;`, `proxy_set_header X-Forwarded-Proto $scheme;`, dan **`proxy_set_header X-Forwarded-For $remote_addr;`**. Baris terakhir ini wajib: kalau tidak ada, pembatasan percobaan login bisa diakali dengan memalsukan IP.
4. Terbitkan sertifikat lewat `akupeta_certbot` (webroot `/var/www/acme`).
5. Uji konfigurasi dulu, lalu reload:
   ```bash
   sudo docker exec akupeta_nginx nginx -t && sudo docker exec akupeta_nginx nginx -s reload
   ```
6. Ubah `.env`: `APP_URL=https://<domain>` dan `SESSION_SECURE_COOKIE=true`. Setelah itu jalankan `sudo docker compose up -d`.
