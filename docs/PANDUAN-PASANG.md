# Panduan Pasang dan Deploy (VPS) — Pemetaan Unit Digital

> Server Pemda (CloudPanel): **[PANDUAN-CLOUDPANEL.md](PANDUAN-CLOUDPANEL.md)**. Server lain dengan Docker dan root: **[PANDUAN-SERVER-DOCKER.md](PANDUAN-SERVER-DOCKER.md)**.

**Alamat:** https://pemetaan.nekomade.com
**Server:** VPS `ubuntu@43.173.8.198`, dipakai bersama akupeta, bpkadpost, dan sopku
**Kode:** repo privat `HamdaniAsril/pemetaan-unit-digital`, di-clone ke `/srv/pemetaan`

## Deploy otomatis: cukup `git push` ke `main`

```
git push (main)
  → GitHub mengirim webhook ke https://pemetaan.nekomade.com/hooks/github
  → pemetaan-webhook.service  (memeriksa tanda tangan HMAC; hanya cabang main)
  → pemetaan-deploy.service   (deploy/deploy.sh)
       1. git fetch + reset ke origin/main
       2. docker build --target uji      ← seluruh test dijalankan; gagal = deploy berhenti
       3. backup database
       4. docker compose up -d --build   ← container diganti; migrasi berjalan otomatis
```

Bila satu test gagal, versi yang sedang berjalan **tidak tersentuh**.

**Melihat jalannya deploy:**

```bash
ssh ubuntu@43.173.8.198 "journalctl -u pemetaan-deploy -n 50 --no-pager"
```

Push yang datang **selama** deploy berjalan tidak hilang: di akhir deploy, skrip mengecek `main` sekali lagi dan langsung men-deploy ulang bila sudah maju.

**Deploy manual** (tanpa push):

```bash
ssh ubuntu@43.173.8.198 "sudo systemctl start pemetaan-deploy"
```

**Riwayat webhook di GitHub:** Settings → Webhooks → Recent Deliveries.

## Isi server

| Bagian | Lokasi / nama |
|---|---|
| Kode | `/srv/pemetaan` (git, deploy key baca saja di `/etc/pemetaan/deploy_key`) |
| Konfigurasi aplikasi | `/srv/pemetaan/aplikasi/.env` (APP_KEY, APP_URL, SESSION_SECURE_COOKIE) |
| Pengaturan deploy | `/etc/pemetaan/deploy.env`: `COMPOSE_FILE=compose.yaml:compose.vps.yaml` (overlay VPS menyambungkan aplikasi ke jaringan Nginx bersama), `PEMETAAN_PORT=8090` |
| Container | `pemetaan_app` (web, `127.0.0.1:8090` + jaringan Nginx bersama) dan `pemetaan_scheduler` (backup harian 02.00) |
| Data | volume Docker `pemetaan_pemetaan_storage` (database SQLite + backup) |
| Webhook | `pemetaan-webhook.service`, port 9010, dibuka di ufw hanya untuk jaringan Docker; rahasia di `/etc/pemetaan/webhook.env` |
| Deploy | `pemetaan-deploy.service`. Pengguna `pemetaan-hook` hanya boleh memicu layanan ini (`/etc/sudoers.d/pemetaan`). |
| Nginx | `/home/akupeta/akupeta/docker/prod/nginx/conf.d/pemetaan.conf` (salinan dari `deploy/nginx/pemetaan-vps.conf`) |
| Sertifikat | Diterbitkan oleh `akupeta_certbot` dan diperpanjang otomatis oleh container itu |

> ⚠️ **Nama layanan compose tidak boleh `app`.** Nginx akupeta mengirim PHP ke host `app` di jaringan yang sama. Kalau ada layanan lain bernama `app`, sebagian permintaan akupeta akan nyasar. Test `compose: tidak ada layanan bernama "app"` menjaga aturan ini.

> ⚠️ **Mengubah `pemetaan.conf` di Nginx bersama.** Selalu jalankan `sudo docker exec akupeta_nginx nginx -t` dulu, baru `nginx -s reload`. Konfigurasi ini sengaja meresolusi `pemetaan_app` saat ada permintaan (`resolver 127.0.0.11`), sehingga kalau aplikasi mati, Nginx bersama tetap bisa start.

## Akun

**Admin.** Kata sandi kamu tentukan sendiri:

```bash
ssh -t ubuntu@43.173.8.198 "cd /srv/pemetaan/aplikasi && sudo docker compose exec pemetaan php artisan pemetaan:buat-admin admin"
```

**Akun OPD.** Di panel admin, buka menu **OPD**, lalu klik **Buat semua akun**. File Excel berisi username dan kata sandi awal akan terunduh. File ini hanya bisa diunduh sekali, jadi simpan baik-baik.

Isi juga **Pengaturan**: kontak admin dan konfirmasi teks petunjuk menjawab.

## Backup dan pemulihan

- **Manual:** di menu OPD, klik **Unduh backup**. Hasilnya satu file `.sqlite`.
- **Otomatis:** dibuat setiap hari pukul 02.00 dan sebelum setiap deploy, disimpan 7 hari di dalam volume.

Memulihkan dari file backup:

```bash
cd /srv/pemetaan/aplikasi
sudo docker compose stop pemetaan scheduler
sudo docker compose cp pemetaan-backup-XXXX.sqlite pemetaan:/var/www/html/storage/database.sqlite
sudo docker compose run --rm --no-deps -e PEMETAAN_PERAN=setup pemetaan rm -f /var/www/html/storage/database.sqlite-wal /var/www/html/storage/database.sqlite-shm
sudo docker compose start pemetaan scheduler
```

## Memasang ulang dari nol (bila server diganti)

1. Buat deploy key baca-saja di server, lalu daftarkan di repo GitHub (Settings → Deploy keys).
2. `git clone` ke `/srv/pemetaan` dan set `core.sshCommand` ke deploy key itu.
3. `cp aplikasi/.env.production.example aplikasi/.env`, lalu isi `APP_KEY` (`base64:` diikuti `openssl rand -base64 32`), `APP_URL`, dan `SESSION_SECURE_COOKIE=true`.
4. Jalankan `sudo /srv/pemetaan/deploy/deploy.sh`. Saat pertama kali, database dibuat dan struktur 49 OPD diimpor otomatis.
5. Siapkan webhook:
   - buat pengguna `pemetaan-hook` dan berkas `/etc/pemetaan/webhook.env` (`WEBHOOK_SECRET`, `WEBHOOK_PORT=9010`, `WEBHOOK_HOST=0.0.0.0`)
   - pasang `deploy/sudoers-pemetaan` (cek dulu dengan `visudo -cf`) dan kedua unit di `deploy/systemd/`
   - buka port di ufw: `allow from 172.16.0.0/12 to any port 9010`
6. Nginx dan sertifikat:
   - pasang konfigurasi HTTP saja untuk acme-challenge
   - terbitkan sertifikat: `docker exec akupeta_certbot certbot certonly --webroot -w /var/www/acme -d <domain>`
   - ganti dengan `deploy/nginx/pemetaan-vps.conf`, lalu `nginx -t` dan reload
7. Daftarkan webhook GitHub: URL `https://<domain>/hooks/github`, tipe JSON, event push, rahasia sama dengan `WEBHOOK_SECRET`.
