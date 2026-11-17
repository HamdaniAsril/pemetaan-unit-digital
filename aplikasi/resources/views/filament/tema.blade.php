{{-- Panel admin memakai token yang sama dengan form OPD (config/tema.php). --}}
@include('partials.token')
<style>
  body.fi-body { background: var(--latar); }
  .fi-logo { font-weight: 800; letter-spacing: -.02em; color: var(--tinta); font-size: 1.05rem !important; white-space: nowrap; }
  .fi-sidebar .fi-sidebar-item-active .fi-sidebar-item-button { background: var(--aksen-muda); }
  .fi-header-heading { font-weight: 800; letter-spacing: -.02em; }
  .fi-wi-stats-overview-stat-value { font-weight: 800; font-variant-numeric: tabular-nums; }
  @media (max-width: 767px) {
    .fi-wi-stats-overview-stats-ctn { grid-template-columns: repeat(2, minmax(0, 1fr)) !important; gap: .75rem !important; }
    .fi-wi-stats-overview-stat { padding: .875rem 1rem !important; }
    .fi-wi-stats-overview-stat-value { font-size: 1.5rem !important; }
  }
  /* Target sentuh minimal 44px untuk tombol ikon di tabel (R-03). */
  .fi-ta-actions .fi-icon-btn, .fi-ta-actions .fi-dropdown-trigger .fi-icon-btn { width: 2.75rem; height: 2.75rem; }
  /* Motif identitas: satu butir per unit, terisi biru bila lengkap (sama dengan rel tab form OPD). */
  .butir-progres { display: flex; flex-wrap: wrap; gap: 3px; max-width: 220px; margin-bottom: 4px; }
  .butir-progres i { width: 14px; height: 5px; border-radius: 3px; background: var(--garis); }
  .butir-progres i.isi { background: var(--aksen); }
  .butir-angka { color: var(--tinta-2); font-size: .875rem; }
  ::selection { background: var(--aksen-garis); }
</style>
