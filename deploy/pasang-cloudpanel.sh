#!/usr/bin/env bash
# Pemasangan Pemetaan Unit Digital di site PHP CloudPanel (sebagai SITE USER, tanpa root/Docker).
# Aman dijalankan berulang: yang sudah ada tidak ditimpa (APP_KEY, rahasia webhook, database).
#
# Persiapan di CloudPanel: buat site PHP 8.4 untuk domainnya (lihat docs/PANDUAN-CLOUDPANEL.md),
# lalu masuk SSH sebagai site user dan jalankan:
#   bash pasang-cloudpanel.sh --cek
#   bash pasang-cloudpanel.sh --domain pemetaan.contoh.go.id
#
# Pilihan:
#   --domain NAMA   domain site (wajib, kecuali --cek)
#   --dir PATH      folder site (bawaan: ~/htdocs/<domain>)
#   --php BIN       PHP yang dipakai (bawaan: php8.4 bila ada, selain itu php)
#   --repo URL      repo git (bawaan: git@github.com:HamdaniAsril/pemetaan-unit-digital.git)
#   --cabang NAMA   cabang yang di-deploy (bawaan: main)
#   --cek           hanya periksa prasyarat, tidak mengubah apa pun
set -euo pipefail

REPO="git@github.com:HamdaniAsril/pemetaan-unit-digital.git"
CABANG=main
DOMAIN=""
DIR=""
PHP_BIN=""
HANYA_CEK=0
KONFIG_DIR="$HOME/.pemetaan"
KUNCI="$KONFIG_DIR/deploy_key"

while [ $# -gt 0 ]; do
  case "$1" in
    --domain) DOMAIN="$2"; shift 2 ;;
    --dir) DIR="$2"; shift 2 ;;
    --php) PHP_BIN="$2"; shift 2 ;;
    --repo) REPO="$2"; shift 2 ;;
    --cabang) CABANG="$2"; shift 2 ;;
    --cek) HANYA_CEK=1; shift ;;
    -h|--help) sed -n '2,19p' "$0"; exit 0 ;;
    *) echo "Pilihan tidak dikenal: $1 (lihat --help)"; exit 64 ;;
  esac
done

ok()    { printf '  \033[32m✔\033[0m %s\n' "$*"; }
gagal() { printf '  \033[31m✘\033[0m %s\n' "$*"; }
info()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
henti() { printf '\n\033[31mBERHENTI:\033[0m %s\n' "$*"; exit 1; }

[ "$(id -u)" -ne 0 ] || henti "jangan jalankan sebagai root. Masuk sebagai site user CloudPanel, lalu jalankan ulang."

# ---------------------------------------------------------------- prasyarat
info "Memeriksa prasyarat"
KURANG=0
if [ -z "$PHP_BIN" ]; then PHP_BIN="$(command -v php8.4 || command -v php || true)"; fi
if [ -n "$PHP_BIN" ] && "$PHP_BIN" -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);'; then
  ok "PHP $("$PHP_BIN" -r 'echo PHP_VERSION;') ($PHP_BIN)"
  # Daftar modul ditampung dulu: `php -m | grep -q` bisa gagal palsu (SIGPIPE + pipefail).
  MODUL="$("$PHP_BIN" -m)"
  for ext in pdo_sqlite sqlite3 intl gd zip mbstring dom xml fileinfo tokenizer ctype openssl curl; do
    if grep -qix "$ext" <<<"$MODUL"; then ok "ekstensi $ext"; else gagal "ekstensi PHP $ext belum aktif"; KURANG=1; fi
  done
  if "$PHP_BIN" -r 'exit(function_exists("proc_open") ? 0 : 1);'; then ok "proc_open aktif (dibutuhkan composer)"; else gagal "proc_open dinonaktifkan di PHP CLI"; KURANG=1; fi
else
  gagal "PHP 8.4 tidak ditemukan (buat site dengan PHP 8.4, atau isi --php)"; KURANG=1
fi
for c in git composer ssh ssh-keygen curl openssl; do
  if command -v "$c" >/dev/null 2>&1; then ok "$c"; else gagal "$c tidak ada"; KURANG=1; fi
done

SSH_HOST_GITHUB=""
# GitHub selalu menolak shell (kode keluar bukan 0), jadi yang dinilai adalah isi jawabannya.
uji_github() {
  local jawab
  jawab="$(timeout 15 ssh -T -o BatchMode=yes -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10 "$@" 2>&1 || true)"
  grep -qiE "permission denied|successfully authenticated" <<<"$jawab"
}
if uji_github git@github.com; then
  SSH_HOST_GITHUB=github.com; ok "GitHub terjangkau lewat SSH port 22"
elif uji_github -p 443 git@ssh.github.com; then
  SSH_HOST_GITHUB=ssh.github.com; ok "GitHub terjangkau lewat SSH port 443 (port 22 diblokir)"
else
  gagal "GitHub tidak terjangkau lewat SSH (port 22 maupun 443). Minta jaringan membuka akses keluar ke github.com"; KURANG=1
fi

[ "$KURANG" -eq 0 ] || henti "lengkapi prasyarat di atas, lalu jalankan ulang."
if [ "$HANYA_CEK" -eq 1 ]; then echo; echo "Semua prasyarat terpenuhi. Tidak ada yang diubah (--cek)."; exit 0; fi
[ -n "$DOMAIN" ] || henti "isi --domain, misalnya --domain pemetaan.contoh.go.id"
DIR="${DIR:-$HOME/htdocs/$DOMAIN}"

# ---------------------------------------------------------------- deploy key
info "Kunci akses repo (deploy key, baca saja)"
mkdir -p "$KONFIG_DIR" && chmod 700 "$KONFIG_DIR"
if [ ! -f "$KUNCI" ]; then
  ssh-keygen -q -t ed25519 -N "" -C "pemetaan-deploy@$(hostname)-$(whoami)" -f "$KUNCI"
  ok "kunci baru dibuat: $KUNCI"
else
  ok "kunci sudah ada: $KUNCI"
fi
chmod 600 "$KUNCI"
if [ "$SSH_HOST_GITHUB" = ssh.github.com ]; then
  REPO="$(printf '%s' "$REPO" | sed -E 's#^git@github\.com:#ssh://git@ssh.github.com:443/#')"
fi
GIT_SSH="ssh -i $KUNCI -o IdentitiesOnly=yes -o StrictHostKeyChecking=accept-new"
if ! GIT_SSH_COMMAND="$GIT_SSH" git ls-remote --heads "$REPO" "$CABANG" >/dev/null 2>&1; then
  echo
  echo "  Repo belum bisa dibaca dengan kunci ini. KIRIM kunci publik di bawah ke pemilik repo"
  echo "  (aman dibagikan) untuk didaftarkan sebagai Deploy key (baca saja), lalu jalankan ulang skrip ini:"
  echo
  sed 's/^/    /' "$KUNCI.pub"
  echo
  exit 2
fi
ok "repo bisa dibaca"

# ---------------------------------------------------------------- kode
info "Kode aplikasi di $DIR"
if [ -d "$DIR/.git" ]; then
  ok "sudah ada (diperbarui saat deploy)"
else
  mkdir -p "$DIR"
  # CloudPanel kadang membuat berkas contoh (index.php/index.html). Disimpan, bukan dihapus.
  if [ -n "$(ls -A "$DIR" 2>/dev/null)" ]; then
    SIMPAN="$KONFIG_DIR/isi-awal-site-$(date +%Y%m%d%H%M%S)"
    mkdir -p "$SIMPAN" && find "$DIR" -mindepth 1 -maxdepth 1 -exec mv {} "$SIMPAN"/ \;
    ok "isi awal folder site dipindah ke $SIMPAN"
  fi
  GIT_SSH_COMMAND="$GIT_SSH" git clone -q --branch "$CABANG" "$REPO" "$DIR"
  ok "di-clone"
fi
git -C "$DIR" config core.sshCommand "$GIT_SSH"
git -C "$DIR" remote set-url origin "$REPO"

# ---------------------------------------------------------------- pengaturan
info "Pengaturan"
APP="$DIR/aplikasi"
DB="$APP/storage/database.sqlite"
ENV_APP="$APP/.env"
atur() { # atur KUNCI NILAI: ganti baris KUNCI= atau tambahkan
  if grep -q "^$1=" "$ENV_APP"; then sed -i "s|^$1=.*|$1=$2|" "$ENV_APP"; else printf '%s=%s\n' "$1" "$2" >> "$ENV_APP"; fi
}
if [ ! -f "$ENV_APP" ]; then
  cp "$APP/.env.production.example" "$ENV_APP"
  atur APP_KEY "base64:$(openssl rand -base64 32)"
  atur GITHUB_WEBHOOK_SECRET "$(openssl rand -hex 32)"
  ok ".env dibuat (APP_KEY dan rahasia webhook acak)"
else
  ok ".env sudah ada (APP_KEY dan rahasia webhook tidak diubah)"
  grep -q '^GITHUB_WEBHOOK_SECRET=.\+' "$ENV_APP" || atur GITHUB_WEBHOOK_SECRET "$(openssl rand -hex 32)"
fi
atur APP_URL "https://$DOMAIN"
atur SESSION_SECURE_COOKIE true
atur DB_DATABASE "$DB"
atur LOG_CHANNEL daily
atur DEPLOY_BRANCH "$CABANG"
chmod 640 "$ENV_APP"

cat > "$KONFIG_DIR/deploy.env" <<EOF
# Pengaturan deploy site ini (dibaca deploy/deploy-cloudpanel.sh).
REPO_DIR=$DIR
DEPLOY_BRANCH=$CABANG
PHP_BIN=$PHP_BIN
# Path database live untuk backup (BUKAN DB_DATABASE, agar tidak terbawa ke proses test).
DB_BERKAS=$DB
EOF
ok "$KONFIG_DIR/deploy.env"

# ---------------------------------------------------------------- deploy pertama
info "Deploy pertama (seluruh test, lalu pasang). Beberapa menit."
"$DIR/deploy/deploy-cloudpanel.sh"
"$PHP_BIN" "$APP/artisan" tinker --execute 'echo "Data: ", App\Models\Opd::count(), " OPD, ", App\Models\Unit::count(), " unit", PHP_EOL;' 2>/dev/null | tail -n1

# ---------------------------------------------------------------- ringkasan
info "SELESAI. Langkah berikutnya di CloudPanel (lihat docs/PANDUAN-CLOUDPANEL.md):"
cat <<EOF
  1. Settings site → Root Directory:  aplikasi/public
  2. SSL/TLS → Let's Encrypt untuk $DOMAIN
  3. Cron Jobs → tambahkan DUA baris (jalankan tiap menit, * * * * *):
       $PHP_BIN $APP/artisan schedule:run >> /dev/null 2>&1
       $DIR/deploy/deploy-cloudpanel.sh --bila-diminta >> /dev/null 2>&1
  4. KIRIM ke pemilik repo lewat jalur PRIBADI (bukan grup), untuk didaftarkan sebagai webhook GitHub:
       URL     : https://$DOMAIN/hooks/github
       Rahasia : lihat dengan  grep GITHUB_WEBHOOK_SECRET $ENV_APP
  5. Buat akun admin:
       $PHP_BIN $APP/artisan pemetaan:buat-admin admin
  Log deploy: $KONFIG_DIR/deploy.log
EOF
