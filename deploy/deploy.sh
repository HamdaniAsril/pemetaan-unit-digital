#!/usr/bin/env bash
# Deploy otomatis: ambil kode terbaru dari GitHub, jalankan seluruh test, lalu ganti container.
# Dijalankan oleh pemetaan-deploy.service (dipicu webhook) atau manual: sudo systemctl start pemetaan-deploy
#
# Isi dibungkus fungsi main: bash membaca skrip sedikit demi sedikit, jadi tanpa ini `git reset`
# yang mengganti berkas ini di tengah jalan bisa membuat bash membaca baris yang salah.
set -euo pipefail

main() {
  # Pengaturan per server. Lewat systemd sudah dimuat (EnvironmentFile); dimuat ulang untuk jalan manual.
  if [ -f /etc/pemetaan/deploy.env ]; then set -a; . /etc/pemetaan/deploy.env; set +a; fi
  local repo="${PEMETAAN_DIR:-/srv/pemetaan}" branch="${DEPLOY_BRANCH:-main}" before
  cd "$repo"

  exec 9>/run/pemetaan-deploy.lock
  if ! flock -n 9; then
    echo "Deploy lain sedang berjalan; dilewati."
    exit 0
  fi

  echo "== $(date -Is) deploy cabang $branch =="
  before="${PEMETAAN_SEBELUM:-$(git rev-parse HEAD)}"
  git fetch --prune origin "$branch"
  git reset --hard "origin/$branch"
  echo "commit: $(git log -1 --format='%h %s')"

  # Bila deploy.sh sendiri ikut berubah, jalankan versi barunya (sekali) agar aturan terbaru yang dipakai.
  if [ -z "${PEMETAAN_JALAN_ULANG:-}" ] && ! git diff --quiet "$before" HEAD -- deploy/deploy.sh; then
    echo "deploy.sh berubah; menjalankan versi terbaru."
    PEMETAAN_JALAN_ULANG=1 PEMETAAN_SEBELUM="$before" exec "$repo/deploy/deploy.sh"
  fi

  cd aplikasi
  # Versi yang berjalan (ikut masuk image), tampil di panel admin.
  printf '%s %s\n' "$(git -C "$repo" rev-parse --short HEAD)" "$(date -Is)" > versi.txt
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

  # Push yang datang SELAMA deploy ini berjalan tidak memicu deploy baru (systemd mengabaikan start
  # untuk layanan yang masih aktif). Jadi cek sekali lagi, dan deploy ulang bila main sudah maju.
  git -C "$repo" fetch --quiet origin "$branch" || true
  if [ "$(git -C "$repo" rev-parse HEAD)" != "$(git -C "$repo" rev-parse "origin/$branch")" ]; then
    echo "Ada commit baru selama deploy; deploy lagi."
    unset PEMETAAN_JALAN_ULANG PEMETAAN_SEBELUM
    exec "$repo/deploy/deploy.sh"
  fi
}

main "$@"
