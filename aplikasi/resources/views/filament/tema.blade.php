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
  ::selection { background: var(--aksen-garis); }
</style>
