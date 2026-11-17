{{-- Panel admin memakai token yang sama dengan form OPD (config/tema.php). --}}
@include('partials.token')
<style>
  body.fi-body { background: var(--latar); }
  .fi-sidebar .fi-logo { font-weight: 800; letter-spacing: -.02em; color: var(--tinta); }
  .fi-sidebar .fi-sidebar-item-active .fi-sidebar-item-button { background: var(--aksen-muda); }
  .fi-header-heading { font-weight: 800; letter-spacing: -.02em; }
  .fi-wi-stats-overview-stat-value { font-weight: 800; font-variant-numeric: tabular-nums; }
  ::selection { background: var(--aksen-garis); }
</style>
