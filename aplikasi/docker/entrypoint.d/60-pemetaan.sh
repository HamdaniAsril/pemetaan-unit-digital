#!/bin/sh
# Siapkan database SQLite, jalankan migrasi, dan impor struktur bila database masih kosong.
# Hanya dijalankan oleh container web (bukan scheduler) supaya migrasi tidak berjalan dua kali.
set -e
[ "${PEMETAAN_PERAN:-web}" = "web" ] || exit 0

cd /var/www/html
DB="${DB_DATABASE:-/var/www/html/storage/database.sqlite}"
mkdir -p "$(dirname "$DB")" storage/app/backup storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
[ -f "$DB" ] || touch "$DB"

php artisan migrate --force --no-interaction

if [ "$(php artisan tinker --execute 'echo App\Models\Opd::count();' 2>/dev/null | tail -n1)" = "0" ]; then
  echo "[pemetaan] Database kosong, mengimpor struktur awal..."
  php artisan pemetaan:impor
fi
