#!/usr/bin/env bash
# Pemasangan Pemetaan Unit Digital di server baru (misalnya server Pemda), termasuk deploy otomatis.
# Aman dijalankan berulang: langkah yang sudah selesai dilewati, data dan rahasia tidak ditimpa.
#
# Pakai (sebagai root):
#   sudo bash pasang.sh --cek                                  # hanya memeriksa prasyarat, tidak mengubah apa pun
#   sudo bash pasang.sh --domain pemetaan.contoh.go.id         # pasang
#
# Pilihan:
#   --domain NAMA        domain aplikasi (wajib, kecuali --cek)
#   --repo URL           repo git (bawaan: git@github.com:HamdaniAsril/pemetaan-unit-digital.git)
#   --dir PATH           folder kode (bawaan: /srv/pemetaan)
#   --cabang NAMA        cabang yang di-deploy (bawaan: main)
#   --port N             port aplikasi di 127.0.0.1 (bawaan: 8090)
#   --port-webhook N     port webhook di 127.0.0.1 (bawaan: 9010)
#   --cek                hanya periksa prasyarat
set -euo pipefail

REPO="git@github.com:HamdaniAsril/pemetaan-unit-digital.git"
DIR=/srv/pemetaan
CABANG=main
PORT=8090
PORT_WEBHOOK=9010
DOMAIN=""
HANYA_CEK=0
ETC=/etc/pemetaan
KUNCI="$ETC/deploy_key"

while [ $# -gt 0 ]; do
  case "$1" in
    --domain) DOMAIN="$2"; shift 2 ;;
    --repo) REPO="$2"; shift 2 ;;
    --dir) DIR="$2"; shift 2 ;;
    --cabang) CABANG="$2"; shift 2 ;;
    --port) PORT="$2"; shift 2 ;;
    --port-webhook) PORT_WEBHOOK="$2"; shift 2 ;;
    --cek) HANYA_CEK=1; shift ;;
    -h|--help) sed -n '2,18p' "$0"; exit 0 ;;
    *) echo "Pilihan tidak dikenal: $1 (lihat --help)"; exit 64 ;;
  esac
done

ok()    { printf '  \033[32m✔\033[0m %s\n' "$*"; }
gagal() { printf '  \033[31m✘\033[0m %s\n' "$*"; }
info()  { printf '\n\033[1m== %s\033[0m\n' "$*"; }
henti() { printf '\n\033[31mBERHENTI:\033[0m %s\n' "$*"; exit 1; }

[ "$(id -u)" -eq 0 ] || henti "jalankan sebagai root: sudo bash $0 ..."

# ---------------------------------------------------------------- prasyarat
info "Memeriksa prasyarat"
KURANG=0
for c in git openssl curl systemctl flock visudo ssh-keygen ssh ss sed; do
  if command -v "$c" >/dev/null 2>&1; then ok "$c"; else gagal "$c tidak ada"; KURANG=1; fi
done
if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
  ok "docker + compose ($(docker compose version --short 2>/dev/null))"
else
  gagal "Docker Engine dengan plugin 'docker compose' belum terpasang"; KURANG=1
fi
NODE="$(command -v node || true)"
if [ -n "$NODE" ] && [ "$("$NODE" -p 'process.versions.node.split(".")[0]')" -ge 18 ]; then
  ok "node $("$NODE" -v) ($NODE)"
else
  gagal "Node.js 18 atau lebih baru belum terpasang (dipakai layanan webhook)"; KURANG=1
fi
port_dipakai() { ss -ltnH "( sport = :$1 )" 2>/dev/null | grep -q .; }
if ! port_dipakai "$PORT"; then ok "port $PORT bebas"
elif docker ps --format '{{.Names}}' 2>/dev/null | grep -qx pemetaan_app; then ok "port $PORT dipakai pemetaan_app (sudah terpasang)"
else gagal "port $PORT sudah dipakai program lain (pilih lain dengan --port)"; KURANG=1; fi
if ! port_dipakai "$PORT_WEBHOOK"; then ok "port $PORT_WEBHOOK bebas"
elif systemctl is-active --quiet pemetaan-webhook 2>/dev/null; then ok "port $PORT_WEBHOOK dipakai pemetaan-webhook (sudah terpasang)"
else gagal "port $PORT_WEBHOOK sudah dipakai program lain (pilih lain dengan --port-webhook)"; KURANG=1; fi

# Akses keluar ke GitHub: port 22 biasa, atau 443 (ssh.github.com) bila 22 diblokir jaringan.
SSH_HOST_GITHUB=""
uji_github() { timeout 15 ssh -T -o BatchMode=yes -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10 "$@" 2>&1 | grep -qiE "permission denied|successfully authenticated"; }
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

# ---------------------------------------------------------------- deploy key
info "Kunci akses repo (deploy key, baca saja)"
mkdir -p "$ETC" && chmod 750 "$ETC"
if [ ! -f "$KUNCI" ]; then
  ssh-keygen -q -t ed25519 -N "" -C "pemetaan-deploy@$(hostname)" -f "$KUNCI"
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
  GIT_SSH_COMMAND="$GIT_SSH" git clone -q --branch "$CABANG" "$REPO" "$DIR"
  ok "di-clone"
fi
git -C "$DIR" config core.sshCommand "$GIT_SSH"
git -C "$DIR" remote set-url origin "$REPO"
git config --global --add safe.directory "$DIR" 2>/dev/null || true

# ---------------------------------------------------------------- pengaturan
info "Pengaturan"
ENV_APP="$DIR/aplikasi/.env"
if [ ! -f "$ENV_APP" ]; then
  cp "$DIR/aplikasi/.env.production.example" "$ENV_APP"
  sed -i "s|^APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|" "$ENV_APP"
  ok ".env aplikasi dibuat (APP_KEY acak)"
else
  ok ".env aplikasi sudah ada (APP_KEY tidak diubah)"
fi
sed -i "s|^APP_URL=.*|APP_URL=https://$DOMAIN|; s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" "$ENV_APP"
chmod 640 "$ENV_APP"

cat > "$ETC/deploy.env" <<EOF
# Pengaturan deploy server ini (dibaca pemetaan-deploy.service dan deploy.sh).
PEMETAAN_DIR=$DIR
DEPLOY_BRANCH=$CABANG
COMPOSE_FILE=compose.yaml
PEMETAAN_PORT=$PORT
EOF
ok "$ETC/deploy.env"

id pemetaan-hook >/dev/null 2>&1 || useradd --system --no-create-home --shell "$(command -v nologin || echo /usr/sbin/nologin)" pemetaan-hook
if [ ! -f "$ETC/webhook.env" ]; then
  printf 'WEBHOOK_SECRET=%s\n' "$(openssl rand -hex 32)" > "$ETC/webhook.env"
  ok "rahasia webhook baru dibuat"
fi
# Port, host, dan cabang selalu diselaraskan; rahasia yang sudah ada dipertahankan.
sed -i '/^WEBHOOK_PORT=/d; /^WEBHOOK_HOST=/d; /^DEPLOY_BRANCH=/d' "$ETC/webhook.env"
printf 'WEBHOOK_PORT=%s\nWEBHOOK_HOST=127.0.0.1\nDEPLOY_BRANCH=%s\n' "$PORT_WEBHOOK" "$CABANG" >> "$ETC/webhook.env"
chown root:pemetaan-hook "$ETC" "$ETC/webhook.env" && chmod 640 "$ETC/webhook.env"
ok "$ETC/webhook.env (127.0.0.1:$PORT_WEBHOOK)"

# ---------------------------------------------------------------- layanan
info "Layanan sistem"
SYSTEMCTL="$(command -v systemctl)"
sed "s|/usr/bin/systemctl|$SYSTEMCTL|" "$DIR/deploy/sudoers-pemetaan" > /tmp/sudoers-pemetaan
visudo -cf /tmp/sudoers-pemetaan >/dev/null
install -m 440 -o root -g root /tmp/sudoers-pemetaan /etc/sudoers.d/pemetaan && rm /tmp/sudoers-pemetaan
ok "sudoers: pemetaan-hook hanya boleh memicu pemetaan-deploy"
sed "s|/usr/bin/node|$NODE|; s|/srv/pemetaan|$DIR|g" "$DIR/deploy/systemd/pemetaan-webhook.service" > /etc/systemd/system/pemetaan-webhook.service
sed "s|/srv/pemetaan|$DIR|g" "$DIR/deploy/systemd/pemetaan-deploy.service" > /etc/systemd/system/pemetaan-deploy.service
systemctl daemon-reload
systemctl enable --now pemetaan-webhook.service >/dev/null 2>&1
systemctl restart pemetaan-webhook.service
sleep 1
if [ "$(curl -s -o /dev/null -w '%{http_code}' -X POST "http://127.0.0.1:$PORT_WEBHOOK/hooks/github" -d '{}')" = 401 ]; then
  ok "pemetaan-webhook aktif (menolak permintaan tanpa tanda tangan)"
else
  henti "pemetaan-webhook tidak merespons; lihat: journalctl -u pemetaan-webhook -n 30"
fi

# ---------------------------------------------------------------- nginx
sed "s|__DOMAIN__|$DOMAIN|g; s|__PORT_WEBHOOK__|$PORT_WEBHOOK|g; s|__PORT__|$PORT|g" \
  "$DIR/deploy/nginx/pemetaan-server.conf.template" > "$ETC/nginx-pemetaan.conf"

# ---------------------------------------------------------------- deploy pertama
info "Deploy pertama (build, seluruh test, jalankan). Beberapa menit."
set -a; . "$ETC/deploy.env"; set +a
"$DIR/deploy/deploy.sh"
for _ in $(seq 1 30); do
  [ "$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$PORT/login")" = 200 ] && break; sleep 2
done
if [ "$(curl -s -o /dev/null -w '%{http_code}' "http://127.0.0.1:$PORT/login")" = 200 ]; then
  ok "aplikasi menjawab di http://127.0.0.1:$PORT/login"
else
  henti "aplikasi belum menjawab; lihat: cd $DIR/aplikasi && docker compose logs pemetaan"
fi

# ---------------------------------------------------------------- ringkasan
info "SELESAI. Langkah berikutnya:"
cat <<EOF
  1. Nginx + HTTPS untuk $DOMAIN:
       konfigurasi siap pakai: $ETC/nginx-pemetaan.conf
       (sertifikat: certbot --nginx -d $DOMAIN, atau sertifikat instansi)
  2. KIRIM ke pemilik repo lewat jalur PRIBADI (bukan grup), untuk didaftarkan sebagai webhook GitHub:
       URL     : https://$DOMAIN/hooks/github
       Rahasia : lihat dengan  sudo grep WEBHOOK_SECRET $ETC/webhook.env
  3. Buat akun admin:
       cd $DIR/aplikasi && docker compose exec pemetaan php artisan pemetaan:buat-admin admin
  Log deploy otomatis : journalctl -u pemetaan-deploy -n 50
  Log webhook         : journalctl -u pemetaan-webhook -n 50
EOF
