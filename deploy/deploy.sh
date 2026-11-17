#!/usr/bin/env bash
# Deploy otomatis: ambil kode terbaru dari GitHub, jalankan seluruh test, lalu ganti container.
# Dijalankan oleh pemetaan-deploy.service (dipicu webhook) atau manual: sudo systemctl start pemetaan-deploy
#
# Isi dibungkus fungsi main: bash membaca skrip sedikit demi sedikit, jadi tanpa ini `git reset`
# yang mengganti berkas ini di tengah jalan bisa membuat bash membaca baris yang salah.
set -euo pipefail

main() {
  local repo=/srv/pemetaan branch=main before
  cd "$repo"

  exec 9>/run/pemetaan-deploy.lock
  if ! flock -n 9; then
    echo "Deploy lain sedang berjalan; dilewati."
    exit 0
  fi

  echo "== $(date -Is) deploy cabang $branch =="
  before="$(git rev-parse HEAD)"
  git fetch --prune origin "$branch"
  git reset --hard "origin/$branch"
  echo "commit: $(git log -1 --format='%h %s')"

  cd aplikasi
  # Test dijalankan di tahap build "uji". Gagal di sini = berhenti, versi berjalan tidak tersentuh.
  docker build --target uji -t pemetaan-app:uji .

  # Cadangan database sebelum container diganti (migrasi berjalan otomatis saat start).
  if [ -n "$(docker compose ps --status running -q pemetaan 2>/dev/null)" ]; then
    docker compose exec -T pemetaan php artisan pemetaan:backup || echo "Peringatan: backup sebelum deploy gagal."
  fi
  docker compose up -d --build --remove-orphans

  # Layanan webhook di-restart terakhir dan hanya bila kodenya berubah.
  if ! git -C "$repo" diff --quiet "$before" HEAD -- deploy/webhook.mjs; then
    systemctl restart pemetaan-webhook.service
  fi
  echo "== selesai $(date -Is) =="
}

main "$@"
