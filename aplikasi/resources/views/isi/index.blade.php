@extends('layouts.app')
@section('title', 'Isi Pemetaan · '.\App\Pemetaan\Format::namaUnit($opd->nama))
@section('content')
<div class="wrap wide layout">
  <nav class="side" aria-label="Daftar unit">
    <div class="side-head">
      <div class="bar"><i id="barFill" style="width: {{ $progres['total'] ? round($progres['terjawab'] / $progres['total'] * 100) : 0 }}%"></i></div>
      <div class="progress-text" id="progressText">{{ $progres['terjawab'] }} dari {{ $progres['total'] }} unit sudah lengkap</div>
    </div>
    <ul class="tabs" id="tabs"></ul>
  </nav>
  <main>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    @error('kirim')<div class="warn" role="alert">{{ $message }}</div>@enderror
    @if ($terkirim)
      <div class="sent">Jawaban sudah dikirim pada {{ $opd->submitted_at?->locale('id')->translatedFormat('j F Y, H:i') }}. Hubungi admin bila perlu mengubah.</div>
    @endif

    <h2 class="tab-title" id="tabTitle"></h2>
    <p class="tab-sub" id="tabSub"></p>
    @if ($kontak)
      <p class="kontak">Unit tidak sesuai? Hubungi admin: {{ $kontak }}</p>
    @endif

    <div class="legend" id="legend">
      <b>Jawab Ya atau Tidak untuk setiap unit:</b>
      <ol>
        @foreach ($indikator as $q)
          <li><strong>{{ $q['label'] }}</strong>: {{ $q['pertanyaan'] }}</li>
        @endforeach
      </ol>
    </div>

    <div id="content"></div>
    <div class="nav" id="nav"></div>
    <form id="formKirim" method="post" action="/isi/kirim" class="hidden">@csrf</form>
  </main>
</div>
@endsection

@push('scripts')
<script>
const TABS = @json($tabs);
const Q = @json($indikator);
let terkirim = @json($terkirim);
let progres = @json($progres);
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

const STEPS = [...TABS.map(t => ({ ...t, review: false })), { judul: "Periksa & Kirim", review: true, units: TABS.flatMap(t => t.units) }];
const ALL = STEPS[STEPS.length - 1].units;
const errors = {};          // unitId -> pesan
const antrean = {};         // "unit:indikator" -> Promise (permintaan berurutan per tombol)
let step = Math.min(Math.max(parseInt((location.hash.match(/^#tab-(\d+)$/) || [])[1] || "1", 10) - 1, 0), STEPS.length - 1);

const esc = s => String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
const isComplete = u => Q.every(q => u.jawaban[q.id]);
const doneCount = list => list.filter(isComplete).length;

function setAns(uid, qid, val) {
  if (terkirim) return;
  const unit = ALL.find(u => u.id === uid);
  const sebelum = unit.jawaban[qid];
  if (sebelum === val) return;
  unit.jawaban[qid] = val;
  delete errors[uid];
  render();

  const key = uid + ":" + qid;
  antrean[key] = (antrean[key] || Promise.resolve()).then(() =>
    fetch("/isi/jawaban", {
      method: "POST",
      headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": CSRF },
      body: JSON.stringify({ unit_id: uid, indikator: qid, nilai: val }),
    }).then(async r => {
      if (r.ok) { const d = await r.json(); progres = d.progres; return; }
      if (unit.jawaban[qid] === val) unit.jawaban[qid] = sebelum;
      if (r.status === 401 || r.status === 419) errors[uid] = 'Sesi berakhir, silakan <a href="/login">masuk lagi</a>.';
      else if (r.status === 423) { errors[uid] = "Jawaban sudah dikirim dan terkunci."; terkirim = true; }
      else errors[uid] = "Gagal menyimpan, coba lagi.";
    }).catch(() => {
      if (unit.jawaban[qid] === val) unit.jawaban[qid] = sebelum;
      errors[uid] = "Gagal menyimpan, periksa koneksi lalu coba lagi.";
    }).finally(render)
  );
}

function unitCard(u) {
  const child = u.kedalaman > 0;
  return `<div class="unit ${child ? "child" : ""} ${isComplete(u) ? "complete" : ""}" style="${u.kedalaman > 1 ? "margin-left:" + (24 * u.kedalaman) + "px" : ""}">
    <div class="unit-head">
      <div><div class="unit-name">${esc(u.nama)}</div><div class="unit-meta">${esc(u.jabatan)}</div></div>
      ${u.kategori ? `<span class="tag">${esc(u.kategori)}</span>` : ""}
    </div>
    ${Q.map((q, i) => `<div class="q">
      <div class="q-label"><b>${i + 1}. ${esc(q.label)}</b><span>${esc(q.ringkas)}</span></div>
      <div class="yn" role="group" aria-label="${esc(q.label)}">
        ${["ya", "tidak"].map(v => `<button type="button" class="${v} ${u.jawaban[q.id] === v ? "on" : ""}" aria-pressed="${u.jawaban[q.id] === v}" ${terkirim ? "disabled" : ""}
          onclick="setAns(${u.id},'${q.id}','${v}')">${v === "ya" ? "Ya" : "Tidak"}</button>`).join("")}
      </div></div>`).join("")}
    ${errors[u.id] ? `<div class="unit-err" role="alert">${errors[u.id]}</div>` : ""}
  </div>`;
}

function review() {
  const sisa = ALL.length - doneCount(ALL);
  const cell = v => v ? `<span class="${v}-t">${v === "ya" ? "Ya" : "Tidak"}</span>` : "–";
  const rows = ALL.map(u => `<tr><td style="padding-left:${10 + u.kedalaman * 18}px">${esc(u.nama)}</td>${Q.map(q => `<td class="c">${cell(u.jawaban[q.id])}</td>`).join("")}</tr>`).join("");
  return (!terkirim && sisa > 0 ? `<div class="warn">Masih ada ${sisa} unit yang belum lengkap. Lengkapi dulu sebelum mengirim.</div>` : "")
    + `<div class="table-scroll" style="margin-top:16px"><table><thead><tr><th>Unit</th>${Q.map(q => `<th class="c">${esc(q.label)}</th>`).join("")}</tr></thead><tbody>${rows}</tbody></table></div>`;
}

function render() {
  const done = doneCount(ALL), total = ALL.length;
  document.getElementById("barFill").style.width = (total ? done / total * 100 : 0) + "%";
  document.getElementById("progressText").textContent = terkirim ? "Sudah dikirim." : `${done} dari ${total} unit sudah lengkap · tersimpan otomatis`;

  document.getElementById("tabs").innerHTML = STEPS.map((s, i) => {
    const d = doneCount(s.units), all = d === s.units.length;
    const c = s.review ? (terkirim ? "Terkirim" : all ? "Siap" : "") : `${d}/${s.units.length}`;
    const subs = s.review ? "" : s.units.slice(1).map(k => `<li class="${isComplete(k) ? "ok" : ""}">${esc(k.nama)}</li>`).join("");
    return `<li><button class="tab ${i === step ? "active" : ""} ${all ? "done" : ""}" onclick="go(${i})" ${i === step ? 'aria-current="page"' : ""}>
      <span class="n">${i + 1}.</span><span class="t">${esc(s.judul)}</span><span class="c">${c}</span></button>
      ${subs ? `<ul class="subs">${subs}</ul>` : ""}</li>`;
  }).join("");
  const tabsEl = document.getElementById("tabs"), act = tabsEl.querySelector(".tab.active");
  if (act && tabsEl.scrollWidth > tabsEl.clientWidth) tabsEl.scrollLeft = act.offsetLeft - tabsEl.offsetLeft - 16;

  const s = STEPS[step];
  document.getElementById("tabTitle").textContent = `${step + 1}. ${s.judul}`;
  document.getElementById("tabSub").textContent = s.review ? "Periksa kembali semua jawaban sebelum dikirim."
    : s.units.length > 1 ? `${s.label} beserta ${s.units.length - 1} unit di bawahnya` : s.label;
  document.getElementById("legend").classList.toggle("hidden", s.review);
  document.getElementById("content").innerHTML = s.review ? review()
    : unitCard(s.units[0]) + (s.units.length > 1 ? `<div class="group-title">Unit di bawah ${esc(s.judul)}</div>` + s.units.slice(1).map(unitCard).join("") : "");

  const prev = step > 0 ? `<button class="btn ghost" onclick="go(${step - 1})">← Kembali</button>` : "<span></span>";
  let next;
  if (step < STEPS.length - 1) next = `<button class="btn" onclick="go(${step + 1})">Lanjut: ${esc(STEPS[step + 1].judul)} →</button>`;
  else next = terkirim ? "<span></span>" : `<button class="btn" ${done < total ? "disabled" : ""} onclick="kirim()">Kirim jawaban</button>`;
  document.getElementById("nav").innerHTML = prev + next;
}

function go(i) { step = i; history.replaceState(null, "", "#tab-" + (i + 1)); render(); window.scrollTo(0, 0); }
async function kirim() {
  if (!confirm("Kirim jawaban? Setelah dikirim, jawaban terkunci dan hanya admin yang bisa membukanya.")) return;
  await Promise.all(Object.values(antrean));
  document.getElementById("formKirim").submit();
}
window.addEventListener("hashchange", () => { const n = parseInt((location.hash.match(/^#tab-(\d+)$/) || [])[1], 10); if (n && n - 1 !== step && n <= STEPS.length) { step = n - 1; render(); } });
render();
</script>
@endpush
