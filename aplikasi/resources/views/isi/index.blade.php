@extends('layouts.app')
@section('title', 'Isi Pemetaan · '.\App\Pemetaan\Format::namaUnit($opd->nama))
@section('content')
@php($namaOpd = \App\Pemetaan\Format::namaUnit($opd->nama))
<div class="shell">
  <nav class="rel" aria-label="Daftar bagian form">
    <div class="rel-kepala">
      <div class="rel-opd">{{ $namaOpd }}</div>
      <button type="button" class="rel-menu" id="tombolMenu" aria-expanded="false" aria-controls="relKaki" aria-label="Menu akun">
        <svg class="ikon"><use href="#i-menu"/></svg>
      </button>
      <div class="rel-progres">
        <div class="angka"><span id="progresTeks"><b>{{ $progres['terjawab'] }}</b> dari {{ $progres['total'] }} unit lengkap</span></div>
        <div class="batang" role="progressbar" aria-label="Progres pengisian" aria-valuemin="0" aria-valuemax="{{ $progres['total'] }}" aria-valuenow="{{ $progres['terjawab'] }}" id="progresBatang">
          <i style="width: {{ $progres['total'] ? round($progres['terjawab'] / $progres['total'] * 100) : 0 }}%"></i>
        </div>
      </div>
      <div class="rel-simpan" id="statusRel" aria-hidden="true"></div>
    </div>
    <ul class="rel-daftar" id="daftarTab"></ul>
    <div class="rel-kaki" id="relKaki">
      <div class="pengguna">
        <span class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->username, 0, 1)) }}</span>
        <span class="pengguna-teks"><b title="{{ auth()->user()->username }}">{{ auth()->user()->username }}</b><small>Akun OPD</small></span>
      </div>
      <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="keluar" title="Keluar"><svg class="ikon"><use href="#i-keluar"/></svg><span>Keluar</span></button></form>
    </div>
  </nav>

  <main class="kerja" id="kerja">
    <div class="kerja-dalam">
      @if (session('status') && ! $terkirim)<div class="pesan pesan-ok" role="status"><svg class="ikon"><use href="#i-cek"/></svg><span>{{ session('status') }}</span></div>@endif
      @error('kirim')<div class="pesan pesan-galat" role="alert"><svg class="ikon"><use href="#i-awas"/></svg><span>{{ $message }}</span></div>@enderror

      @if ($terkirim)
        <div class="terkirim-pita">
          <div class="stempel {{ $baruTerkirim ? 'tekan' : '' }}"><span class="bulat"><svg class="ikon"><use href="#i-cek"/></svg></span><span>Terkirim<small>{{ $opd->submitted_at?->translatedFormat('j M Y') }}</small></span></div>
          <p><b>Jawaban sudah dikirim pada {{ $opd->submitted_at?->locale('id')->translatedFormat('j F Y, H:i') }}.</b> Terima kasih. Jawaban terkunci; hubungi admin bila ada yang perlu diubah.</p>
        </div>
      @endif

      <div class="kerja-kepala">
        <div>
          <h1 id="judul" tabindex="-1">{{ $tabs->isEmpty() ? 'Isian pemetaan' : '' }}</h1>
          <p id="subjudul"></p>
          @if ($kontak)
            <p class="kontak">Unit tidak sesuai? Hubungi admin: {{ $kontak }}</p>
          @endif
        </div>
        <div class="simpan ok" id="statusSimpan" role="status" aria-live="polite">
          <svg class="ikon"><use href="#i-awan"/></svg><span>Tersimpan otomatis</span>
        </div>
      </div>

      @if ($tabs->isEmpty())
        <div class="kosong" role="status">
          <h2>Belum ada unit yang perlu dinilai</h2>
          <p>Daftar unit untuk OPD ini masih kosong. Hubungi admin pemetaan untuk menambahkan unit{{ $kontak ? ' ('.$kontak.')' : '' }}.</p>
        </div>
      @else
      <details class="petunjuk" id="petunjuk" open>
        <summary>Cara menjawab tiga pertanyaan <svg class="ikon chev"><use href="#i-bawah"/></svg></summary>
        <div class="petunjuk-isi">
          @foreach ($indikator as $i => $q)
            <section class="indikator">
              <h3><span class="no">{{ $i + 1 }}</span> {{ $q['label'] }}</h3>
              <p class="resmi">{{ $q['pertanyaan'] }}</p>
              <p class="ya-bila">{{ $q['petunjuk'] }}</p>
            </section>
          @endforeach
        </div>
      </details>

      <div id="isi"></div>
      @endif
    </div>
  </main>
</div>

<form id="formKirim" method="post" action="/isi/kirim" hidden>@csrf</form>
<dialog class="konfirmasi" id="dialogKirim" aria-labelledby="dialogJudul">
  <div class="isi">
    <h2 id="dialogJudul">Kirim jawaban sekarang?</h2>
    <p>Semua <b id="dialogJumlah"></b> unit sudah terisi. Setelah dikirim, jawaban <b>terkunci</b> dan hanya admin yang bisa membukanya kembali.</p>
  </div>
  <div class="aksi">
    <button type="button" class="btn btn-garis" value="batal" id="dialogBatal">Periksa lagi</button>
    <button type="button" class="btn btn-utama" id="dialogKirimYa"><svg class="ikon"><use href="#i-kirim"/></svg> Ya, kirim</button>
  </div>
</dialog>
@endsection

@push('scripts')
@if ($tabs->isNotEmpty())
<script>
const TABS = @json($tabs);
const Q = @json($indikator->map(fn ($q) => ['id' => $q['id'], 'label' => $q['label']])->values());
const TERKIRIM = @json($terkirim);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const STEPS = [...TABS.map(t => ({ ...t, review: false })), { judul: "Periksa & Kirim", review: true, units: TABS.flatMap(t => t.units) }];
const ALL = STEPS[STEPS.length - 1].units;
const UNIT = new Map(ALL.map(u => [u.id, u]));
const TAB_DARI_UNIT = new Map();
TABS.forEach((t, i) => t.units.forEach(u => TAB_DARI_UNIT.set(u.id, i)));

const antrean = {}, konfirmasi = {}, galat = {};
ALL.forEach(u => Q.forEach(q => { konfirmasi[u.id + ":" + q.id] = u.jawaban[q.id]; }));
let sibuk = 0, adaGagal = false, unitBaru = null;
let step = Math.min(Math.max(parseInt((location.hash.match(/^#tab-(\d+)$/) || [])[1] || "1", 10) - 1, 0), STEPS.length - 1);

const $ = id => document.getElementById(id);
const esc = s => String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
const ikon = (n, k = "") => `<svg class="ikon ${k}" aria-hidden="true"><use href="#i-${n}"/></svg>`;
const lengkap = u => Q.every(q => u.jawaban[q.id]);
const jumlahLengkap = list => list.filter(lengkap).length;
const terisi = u => Q.filter(q => u.jawaban[q.id]).length;

/* ---------- Rel tab ---------- */
function renderRel() {
  const done = jumlahLengkap(ALL), total = ALL.length;
  $("progresTeks").innerHTML = TERKIRIM ? "<b>Terkirim</b> · jawaban terkunci" : `<b>${done}</b> dari ${total} unit lengkap`;
  $("progresBatang").setAttribute("aria-valuenow", done);
  $("progresBatang").firstElementChild.style.width = (total ? done / total * 100 : 0) + "%";

  const item = (s, i) => {
    const d = jumlahLengkap(s.units), semua = d === s.units.length;
    const butir = s.review ? "" : `<span class="tab-butir" aria-hidden="true">${s.units.map(u => `<i class="${lengkap(u) ? "isi" : ""}"></i>`).join("")}</span>`;
    const hitung = s.review ? "" : `<span class="tab-hitung">${semua ? ikon("cek") : `${d}/${s.units.length}`}</span>`;
    const label = s.review ? `${esc(s.judul)}` : `${esc(s.judul)}, ${d} dari ${s.units.length} unit lengkap`;
    return `<li><button type="button" class="tab ${semua && !s.review ? "selesai" : ""}" data-step="${i}" ${i === step ? 'aria-current="step"' : ""} aria-label="${label}">
      <span class="tab-no">${s.review ? ikon(TERKIRIM ? "gembok" : "kirim") : i + 1}</span><span class="tab-nama">${esc(s.judul)}</span>${hitung}${butir}</button></li>`;
  };
  const daftar = $("daftarTab");
  daftar.innerHTML = STEPS.slice(0, -1).map(item).join("")
    + `<li class="rel-kirim">${item(STEPS[STEPS.length - 1], STEPS.length - 1).replace(/^<li>|<\/li>$/g, "")}</li>`;
  const aktif = daftar.querySelector('[aria-current="step"]');
  if (aktif && daftar.scrollWidth > daftar.clientWidth) daftar.scrollLeft = aktif.offsetLeft - 16;
}
$("daftarTab").addEventListener("click", e => { const b = e.target.closest("[data-step]"); if (b) go(+b.dataset.step); });

/* ---------- Kartu unit (baris rapor) ---------- */
function statusUnit(u) {
  if (lengkap(u)) return `<span class="unit-status ok">${ikon("cek")} Lengkap</span>`;
  const n = terisi(u);
  return `<span class="unit-status">${n ? `${n} dari 3 terjawab` : "Belum diisi"}</span>`;
}
function kartu(u) {
  const kelas = ["unit", u.kedalaman > 0 ? "anak" : "", u.kedalaman > 1 ? "dalam-2" : "", lengkap(u) ? "lengkap" : "", unitBaru === u.id ? "baru" : ""].join(" ");
  return `<article class="${kelas}" id="unit-${u.id}" aria-labelledby="nama-${u.id}">
    <div class="unit-info">
      <div class="unit-nama" id="nama-${u.id}">${esc(u.nama)}</div>
      <div class="unit-meta">${u.jabatan ? `<span>${esc(u.jabatan)}</span>` : ""}<span data-status>${statusUnit(u)}</span></div>
    </div>
    ${Q.map(q => {
      const n = `j-${u.id}-${q.id}`, v = u.jawaban[q.id];
      const opsi = (nilai, teks, ik) => `<label class="${nilai} ${v === nilai ? "on" : ""}"><input type="radio" name="${n}" value="${nilai}" data-unit="${u.id}" data-q="${q.id}" ${v === nilai ? "checked" : ""} ${TERKIRIM ? "disabled" : ""}>${ikon(ik)}${teks}</label>`;
      return `<div class="sel" role="radiogroup" aria-labelledby="l-${n} nama-${u.id}">
        <span class="sel-label" id="l-${n}">${esc(q.label)}</span>
        <div class="pilih ${TERKIRIM ? "kunci" : ""}">${opsi("ya", "Ya", "cek")}${opsi("tidak", "Tidak", "silang")}</div></div>`;
    }).join("")}
    <div class="unit-galat" data-galat ${galat[u.id] ? "" : "hidden"}>${galat[u.id] ? `<div class="galat" role="alert">${ikon("awas")}<span>${galat[u.id]}</span></div>` : ""}</div>
  </article>`;
}
function perbaruiKartu(u) {
  const el = $("unit-" + u.id);
  if (!el) return;
  el.classList.toggle("lengkap", lengkap(u));
  el.classList.toggle("baru", unitBaru === u.id);
  el.querySelector("[data-status]").innerHTML = statusUnit(u);
  Q.forEach(q => { el.querySelectorAll(`input[data-q="${q.id}"]`).forEach(i => { i.checked = i.value === u.jawaban[q.id]; i.parentElement.classList.toggle("on", i.checked); }); });
  const g = el.querySelector("[data-galat]");
  g.hidden = !galat[u.id];
  g.innerHTML = galat[u.id] ? `<div class="galat" role="alert">${ikon("awas")}<span>${galat[u.id]}</span></div>` : "";
}

/* ---------- Simpan otomatis ---------- */
function statusSimpan() {
  const rel = $("statusRel");
  rel.className = "rel-simpan " + (adaGagal ? "gagal" : "");
  rel.innerHTML = sibuk ? `${ikon("putar")} Menyimpan…` : adaGagal ? `${ikon("awas")} Ada jawaban gagal tersimpan` : `${ikon("awan")} ${TERKIRIM ? "Jawaban terkunci" : "Semua jawaban tersimpan"}`;
  const el = $("statusSimpan");
  el.className = "simpan " + (sibuk ? "sibuk" : adaGagal ? "gagal" : "ok");
  el.innerHTML = sibuk ? `${ikon("putar")}<span>Menyimpan…</span>`
    : adaGagal ? `${ikon("awas")}<span>Ada jawaban yang gagal tersimpan</span>`
    : `${ikon("awan")}<span>${TERKIRIM ? "Jawaban terkunci" : "Semua jawaban tersimpan"}</span>`;
}
function simpan(uid, qid, val) {
  if (TERKIRIM) return;
  const u = UNIT.get(uid);
  if (u.jawaban[qid] === val) return;
  const sebelumnyaBaru = unitBaru;
  u.jawaban[qid] = val;
  delete galat[uid];
  unitBaru = uid;
  if (sebelumnyaBaru && sebelumnyaBaru !== uid) perbaruiKartu(UNIT.get(sebelumnyaBaru));
  perbaruiKartu(u); renderRel();
  sibuk++; statusSimpan();

  const key = uid + ":" + qid;
  antrean[key] = (antrean[key] || Promise.resolve()).then(() =>
    fetch("/isi/jawaban", {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": CSRF },
      body: JSON.stringify({ unit_id: uid, indikator: qid, nilai: val }),
    }).then(async r => {
      if (r.ok) { konfirmasi[key] = val; return; }
      if (u.jawaban[qid] === val) u.jawaban[qid] = konfirmasi[key];
      galat[uid] = r.status === 401 || r.status === 419 ? 'Sesi berakhir, silakan <a href="/login">masuk lagi</a>. Jawaban terakhir belum tersimpan.'
        : r.status === 423 ? "Jawaban sudah dikirim dan terkunci."
        : "Gagal menyimpan. Coba klik lagi.";
    }).catch(() => {
      if (u.jawaban[qid] === val) u.jawaban[qid] = konfirmasi[key];
      galat[uid] = "Gagal menyimpan. Periksa koneksi internet, lalu klik lagi.";
    }).finally(() => {
      sibuk--;
      adaGagal = Object.keys(galat).length > 0;
      perbaruiKartu(u); renderRel(); statusSimpan();
    })
  );
}
$("isi").addEventListener("focusin", e => { if (e.target.matches(".pilih input")) e.target.parentElement.classList.toggle("fokus", e.target.matches(":focus-visible")); });
$("isi").addEventListener("focusout", e => { if (e.target.matches(".pilih input")) e.target.parentElement.classList.remove("fokus"); });
$("isi").addEventListener("change", e => {
  const i = e.target;
  if (i.matches('input[type="radio"][data-unit]')) simpan(+i.dataset.unit, i.dataset.q, i.value);
});

/* ---------- Isi area kerja ---------- */
function belumLengkapPertama() {
  for (let i = 0; i < TABS.length; i++) { const u = TABS[i].units.find(x => !lengkap(x)); if (u) return { i, u }; }
  return null;
}
function renderKerja() {
  const s = STEPS[step];
  $("judul").textContent = s.review ? "Periksa & kirim" : s.judul;
  $("subjudul").textContent = s.review ? "Periksa kembali semua jawaban sebelum dikirim."
    : s.units.length > 1 ? `${s.label} · beserta ${s.units.length - 1} unit di bawahnya` : s.label;
  $("petunjuk").hidden = s.review;

  if (s.review) { $("isi").innerHTML = lembarRapor(); return; }

  const sisa = belumLengkapPertama();
  const berikut = STEPS[step + 1];
  $("isi").innerHTML = `
    <div class="lembar-unit">
      <div class="kolom-kepala" aria-hidden="true"><span>Unit</span>${Q.map(q => `<span>${esc(q.label)}</span>`).join("")}</div>
      ${s.units.map(kartu).join("")}
    </div>
    <div class="langkah">
      ${step > 0 ? `<button type="button" class="btn btn-garis" data-go="${step - 1}">${ikon("kiri")} Sebelumnya</button>` : ""}
      <div class="kanan">
        ${!TERKIRIM && sisa && sisa.i !== step ? `<button type="button" class="btn btn-teks" data-lompat>${ikon("lompat")} Ke unit yang belum diisi</button>` : ""}
        <button type="button" class="btn btn-utama" data-go="${step + 1}">${berikut.review ? "Periksa & kirim" : "Lanjut: " + esc(berikut.judul)} ${ikon("kanan")}</button>
      </div>
    </div>`;
}
function lembarRapor() {
  const done = jumlahLengkap(ALL), total = ALL.length, sisa = total - done;
  const nilai = v => v === "ya" ? `<span class="nilai nilai-ya">Ya</span>` : v === "tidak" ? `<span class="nilai nilai-tidak">Tidak</span>` : `<span class="nilai-kosong">Belum</span>`;
  const baris = TABS.map((t, i) => `<tr class="kelompok"><td colspan="${Q.length + 1}">${i + 1}. ${esc(t.judul)}</td></tr>`
    + t.units.map(u => `<tr><td style="padding-left:${14 + u.kedalaman * 18}px">${esc(u.nama)}${lengkap(u) || TERKIRIM ? "" : `<button type="button" class="lengkapi" data-buka="${u.id}">Lengkapi</button>`}</td>${Q.map(q => `<td class="c" data-label="${esc(q.label)}">${nilai(u.jawaban[q.id])}</td>`).join("")}</tr>`).join("")).join("");
  return `<div class="lembar">
    <div class="lembar-kepala">
      <div><h2>Lembar jawaban</h2><p>${esc(@json(\App\Pemetaan\Format::namaUnit($opd->nama)))}</p><span class="butir-progres" style="margin-top:10px" aria-hidden="true">${ALL.map(u => `<i class="${lengkap(u) ? "isi" : ""}"></i>`).join("")}</span></div>
      <div class="lembar-ringkas"><div><b>${done}/${total}</b>unit lengkap</div>${sisa && !TERKIRIM ? `<div><b>${sisa}</b>belum lengkap</div>` : ""}</div>
    </div>
    <div><table class="rapor"><thead><tr><th>Unit</th>${Q.map(q => `<th class="c">${esc(q.label)}</th>`).join("")}</tr></thead><tbody>${baris}</tbody></table></div>
    <div class="tanda">
      ${TERKIRIM ? `<p>Jawaban sudah dikirim dan terkunci. Hubungi admin bila ada yang perlu diubah.</p><div class="stempel"><span class="bulat">${ikon("cek")}</span><span>Terkirim</span></div>`
        : sisa ? `<p>Masih ada <b>${sisa} unit</b> yang belum lengkap. Tombol kirim aktif setelah semua unit terisi.</p><button type="button" class="btn btn-utama" disabled>${ikon("kirim")} Kirim jawaban</button>`
        : `<p>Dengan mengirim, Anda memastikan jawaban di atas sesuai kondisi setiap unit. Setelah dikirim, jawaban terkunci.</p><button type="button" class="btn btn-utama" data-kirim>${ikon("kirim")} Kirim jawaban</button>`}
    </div>
  </div>
  ${step > 0 ? `<div class="langkah"><button type="button" class="btn btn-garis" data-go="${step - 1}">${ikon("kiri")} Sebelumnya</button></div>` : ""}`;
}
$("isi").addEventListener("click", e => {
  const g = e.target.closest("[data-go]"); if (g) return go(+g.dataset.go);
  if (e.target.closest("[data-lompat]")) { const s = belumLengkapPertama(); if (s) bukaUnit(s.u.id); return; }
  const b = e.target.closest("[data-buka]"); if (b) return bukaUnit(+b.dataset.buka);
  if (e.target.closest("[data-kirim]")) { $("dialogJumlah").textContent = ALL.length; $("dialogKirim").showModal(); }
});
function bukaUnit(id) {
  go(TAB_DARI_UNIT.get(id), false);
  const el = $("unit-" + id);
  el.scrollIntoView({ behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth", block: "center" });
  const u = UNIT.get(id), q = Q.find(q => !u.jawaban[q.id]) || Q[0];
  el.querySelector(`input[data-q="${q.id}"]`)?.focus({ preventScroll: true });
}

/* ---------- Navigasi ---------- */
function go(i, fokus = true) {
  if (i < 0 || i >= STEPS.length) return;
  step = i;
  history.replaceState(null, "", "#tab-" + (i + 1));
  if (unitBaru && !STEPS[i].units.some(u => u.id === unitBaru)) unitBaru = null;
  renderRel(); renderKerja();
  if (fokus) { window.scrollTo(0, 0); $("judul").focus({ preventScroll: true }); }
}
window.addEventListener("hashchange", () => { const n = parseInt((location.hash.match(/^#tab-(\d+)$/) || [])[1], 10); if (n && n - 1 !== step && n <= STEPS.length) go(n - 1); });

/* ---------- Kirim ---------- */
$("dialogBatal").addEventListener("click", () => $("dialogKirim").close());
$("dialogKirimYa").addEventListener("click", async () => {
  const b = $("dialogKirimYa"); b.disabled = true; b.innerHTML = `${ikon("putar")} Mengirim…`;
  await Promise.all(Object.values(antrean));
  $("formKirim").submit();
});

/* ---------- Petunjuk & menu ---------- */
try { if (localStorage.getItem("petunjuk-tutup") === "1") $("petunjuk").open = false; } catch {}
$("petunjuk").addEventListener("toggle", () => { try { localStorage.setItem("petunjuk-tutup", $("petunjuk").open ? "0" : "1"); } catch {} });
$("tombolMenu").addEventListener("click", () => {
  const buka = $("relKaki").classList.toggle("buka");
  $("tombolMenu").setAttribute("aria-expanded", buka);
});
const tutupMenu = () => { $("relKaki").classList.remove("buka"); $("tombolMenu").setAttribute("aria-expanded", "false"); };
document.addEventListener("keydown", e => { if (e.key === "Escape") tutupMenu(); });
document.addEventListener("click", e => { if (!e.target.closest("#relKaki, #tombolMenu")) tutupMenu(); });
const aturRuangGulir = () => { document.documentElement.style.scrollPaddingTop = matchMedia("(max-width: 900px)").matches ? (document.querySelector(".rel").offsetHeight + 12) + "px" : ""; };
addEventListener("resize", aturRuangGulir);
window.addEventListener("beforeunload", e => { if (sibuk) { e.preventDefault(); e.returnValue = ""; } });

renderRel(); renderKerja(); statusSimpan(); aturRuangGulir();
</script>
@else
<script>
document.getElementById("tombolMenu").addEventListener("click", () => {
  const buka = document.getElementById("relKaki").classList.toggle("buka");
  document.getElementById("tombolMenu").setAttribute("aria-expanded", buka);
});
</script>
@endif
@endpush
