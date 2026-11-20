#!/usr/bin/env bash
# Deploy Pemetaan Unit Digital di site CloudPanel (berjalan sebagai site user, tanpa root/Docker).
#
#   deploy-cloudpanel.sh               deploy sekarang
#   deploy-cloudpanel.sh --bila-diminta deploy hanya bila webhook menaruh tanda (dipanggil cron tiap menit)
#
# Urutan: ambil kode → jalankan SELURUH test di folder sementara → (gagal = berhenti, situs tidak tersentuh)
#         → backup database → mode pemeliharaan → perbarui kode → composer → migrasi → cache → situs dibuka lagi.
# Pengaturan: ~/.pemetaan/deploy.env (dibuat pasang-cloudpanel.sh). Log: ~/.pemetaan/deploy.log
#
# Isi dibungkus fungsi main: bash membaca skrip sedikit demi sedikit, jadi tanpa ini `git reset`
# yang mengganti berkas ini di tengah jalan bisa membuat bash membaca baris yang salah.
set -euo pipefail

main() {
  local konfig="$HOME/.pemetaan/deploy.env"
  [ -f "$konfig" ] || { echo "Belum dipasang: $konfig tidak ada. Jalankan pasang-cloudpanel.sh dulu."; exit 1; }
  set -a; . "$konfig"; set +a
  : "${REPO_DIR:?REPO_DIR belum diisi}" "${PHP_BIN:=php}" "${DEPLOY_BRANCH:=main}"
  local app="$REPO_DIR/aplikasi" tanda="$REPO_DIR/aplikasi/storage/app/deploy-diminta"
  # Path database live. Sengaja TIDAK di-export: proses test tidak boleh melihatnya.
  local db_live="${DB_BERKAS:-$app/storage/database.sqlite}"
  local composer; composer="$(command -v composer)"

  if [ "${1:-}" = "--bila-diminta" ]; then
    [ -f "$tanda" ] || exit 0
  fi

  # Kunci sederhana dan portabel: satu deploy pada satu waktu.
  # Global (bukan local): trap EXIT berjalan setelah main selesai.
  KUNCI_DEPLOY="$HOME/.pemetaan/deploy.kunci"; UJI_DIR=""; DIBUKA=1
  local kunci="$KUNCI_DEPLOY"
  if ! mkdir "$kunci" 2>/dev/null; then
    if [ -f "$kunci/pid" ] && kill -0 "$(cat "$kunci/pid")" 2>/dev/null; then
      echo "Deploy lain sedang berjalan; dilewati."; exit 0
    fi
    rm -rf "$kunci"; mkdir "$kunci"
  fi
  echo $$ > "$kunci/pid"
  trap 'rm -rf "$KUNCI_DEPLOY"; [ -n "$UJI_DIR" ] && git -C "$REPO_DIR" worktree remove --force "$UJI_DIR" 2>/dev/null; [ "$DIBUKA" = 1 ] || "$PHP_BIN" "$REPO_DIR/aplikasi/artisan" up >/dev/null 2>&1; true' EXIT
  rm -f "$tanda"

  # Jalan ulang (setelah skrip berubah) sudah tercatat lewat tee milik proses pertama.
  [ -n "${PEMETAAN_JALAN_ULANG:-}" ] || exec > >(tee -a "$HOME/.pemetaan/deploy.log") 2>&1
  echo "== $(date -Is) deploy cabang $DEPLOY_BRANCH =="
  cd "$REPO_DIR"
  local sebelum target
  sebelum="${PEMETAAN_SEBELUM:-$(git rev-parse HEAD)}"
  git fetch --prune origin "$DEPLOY_BRANCH"
  target="$(git rev-parse "origin/$DEPLOY_BRANCH")"
  echo "target: $(git log -1 --format='%h %s' "$target")"

  # 1. Gerbang test, di salinan terpisah. Situs live belum disentuh.
  #    (Dilewati pada jalan ulang: commit yang sama sudah lulus di proses pertama.)
  if [ -z "${PEMETAAN_LEWATI_UJI:-}" ]; then
    UJI_DIR="$(mktemp -d "$HOME/.pemetaan/uji.XXXXXX")"; rmdir "$UJI_DIR"
    git worktree add --detach --quiet "$UJI_DIR" "$target"
    # Environment bersih: tidak ada DB_*, APP_* atau rahasia dari server live yang ikut terbawa ke test.
    (
      cd "$UJI_DIR/aplikasi"
      bersih() { env -i HOME="$HOME" PATH="$PATH" COMPOSER_HOME="${COMPOSER_HOME:-$HOME/.config/composer}" "$@"; }
      bersih "$PHP_BIN" "$composer" install --no-interaction --prefer-dist --no-progress --quiet
      cp .env.example .env
      bersih "$PHP_BIN" artisan key:generate --ansi --no-interaction >/dev/null
      bersih "$PHP_BIN" vendor/bin/pest --colors=never
    ) || { echo "== GAGAL: test tidak lulus, deploy dibatalkan. Situs tetap memakai versi $(git rev-parse --short "$sebelum"). =="; exit 1; }
    git worktree remove --force "$UJI_DIR"; UJI_DIR=""
  fi

  # 2. Perbarui situs live.
  cd "$app"
  if [ -f "$db_live" ] && [ -f vendor/autoload.php ]; then
    "$PHP_BIN" artisan pemetaan:backup || echo "Peringatan: backup sebelum deploy gagal."
  fi
  if [ -f vendor/autoload.php ]; then
    DIBUKA=0
    "$PHP_BIN" artisan down --retry=15 || true
  fi

  git -C "$REPO_DIR" reset --hard --quiet "$target"
  # Bila skrip ini ikut berubah, jalankan versi barunya agar aturan terbaru yang dipakai.
  if [ -z "${PEMETAAN_JALAN_ULANG:-}" ] && ! git -C "$REPO_DIR" diff --quiet "$sebelum" "$target" -- deploy/deploy-cloudpanel.sh; then
    echo "deploy-cloudpanel.sh berubah; menjalankan versi terbaru."
    rm -rf "$kunci"
    PEMETAAN_JALAN_ULANG=1 PEMETAAN_LEWATI_UJI=1 PEMETAAN_SEBELUM="$sebelum" exec "$REPO_DIR/deploy/deploy-cloudpanel.sh"
  fi

  "$PHP_BIN" "$composer" install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader --quiet
  touch "$db_live"
  "$PHP_BIN" artisan migrate --force --no-interaction
  if [ "$("$PHP_BIN" artisan tinker --execute 'echo App\Models\Opd::count();' 2>/dev/null | tail -n1)" = "0" ]; then
    echo "Database kosong, mengimpor struktur awal..."
    "$PHP_BIN" artisan pemetaan:impor
  fi
  "$PHP_BIN" artisan optimize
  "$PHP_BIN" artisan up
  DIBUKA=1
  echo "== selesai $(date -Is): $(git -C "$REPO_DIR" log -1 --format='%h %s') =="
}

main "$@"
