// Penerima webhook GitHub untuk deploy otomatis (pola yang sama dengan sopku).
// Hanya menerima push ke cabang main yang tanda tangan HMAC-nya cocok, lalu memicu pemetaan-deploy.service.
// Konfigurasi di /etc/pemetaan/webhook.env: WEBHOOK_SECRET, WEBHOOK_PORT (bawaan 9010), WEBHOOK_HOST.
import { createServer } from 'node:http'
import { createHmac, timingSafeEqual } from 'node:crypto'
import { spawn } from 'node:child_process'

const secret = process.env.WEBHOOK_SECRET || ''
const port = Number(process.env.WEBHOOK_PORT || 9010)
// 0.0.0.0 agar Nginx di jaringan Docker bisa menjangkau; ufw hanya membuka port ini untuk 172.16.0.0/12.
const host = process.env.WEBHOOK_HOST || '127.0.0.1'
const branch = process.env.DEPLOY_BRANCH || 'main'

if (!secret) {
  console.error('WEBHOOK_SECRET belum diisi; webhook tidak dijalankan.')
  process.exit(1)
}

function tandaTanganCocok(body, header) {
  if (!header) return false
  const diharapkan = Buffer.from(`sha256=${createHmac('sha256', secret).update(body).digest('hex')}`)
  const diterima = Buffer.from(header)
  return diterima.length === diharapkan.length && timingSafeEqual(diterima, diharapkan)
}

const server = createServer((req, res) => {
  const balas = (kode, pesan) => { res.writeHead(kode, { 'Content-Type': 'text/plain; charset=utf-8' }); res.end(pesan + '\n') }
  if (req.method !== 'POST' || req.url !== '/hooks/github') return balas(404, 'tidak ditemukan')

  const potongan = []
  let ukuran = 0
  req.on('data', (c) => {
    ukuran += c.length
    if (ukuran > 5 * 1024 * 1024) { balas(413, 'terlalu besar'); req.destroy() } else potongan.push(c)
  })
  req.on('end', () => {
    const body = Buffer.concat(potongan)
    if (!tandaTanganCocok(body, req.headers['x-hub-signature-256'])) return balas(401, 'tanda tangan tidak cocok')

    const event = req.headers['x-github-event']
    if (event === 'ping') return balas(200, 'pong')
    if (event !== 'push') return balas(202, `event ${event} diabaikan`)

    let ref = ''
    try { ref = JSON.parse(body.toString('utf8')).ref } catch { return balas(400, 'payload tidak valid') }
    if (ref !== `refs/heads/${branch}`) return balas(202, `push ke ${ref} diabaikan`)

    spawn('sudo', ['-n', '/usr/bin/systemctl', 'start', '--no-block', 'pemetaan-deploy.service'], { stdio: 'inherit' })
    console.log(`[${new Date().toISOString()}] push ke ${branch}: deploy dimulai`)
    balas(202, 'deploy dimulai')
  })
})

server.listen(port, host, () => console.log(`webhook menunggu di http://${host}:${port}/hooks/github (cabang ${branch})`))
