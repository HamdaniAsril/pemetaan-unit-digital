{{-- Panel admin memakai token yang sama dengan form OPD (config/tema.php). --}}
@include('partials.token')
<style>
  body.fi-body { background: var(--kertas); }
  .fi-sidebar, .fi-sidebar-header { background: var(--sampul) !important; box-shadow: none !important; }
  .fi-sidebar-header { border-bottom: 1px solid var(--sampul-garis); }
  .fi-sidebar .fi-logo { font-family: var(--serif); font-weight: 700; color: var(--sampul-tinta); letter-spacing: -.01em; }
  .fi-sidebar .fi-sidebar-item-label { color: var(--sampul-tinta); }
  .fi-sidebar .fi-sidebar-item-icon { color: var(--sampul-tinta-2); }
  .fi-sidebar .fi-sidebar-item-button:hover { background: var(--sampul-2); }
  .fi-sidebar .fi-sidebar-item-active .fi-sidebar-item-button { background: var(--sampul-3); box-shadow: inset 3px 0 0 var(--posisi); }
  .fi-sidebar .fi-sidebar-item-active .fi-sidebar-item-icon { color: var(--posisi); }
  .fi-sidebar .fi-sidebar-group-label { color: var(--sampul-tinta-2); }
  .fi-topbar > nav { background: var(--halaman); }
  .fi-header-heading { font-family: var(--serif); letter-spacing: -.01em; }
  .fi-section, .fi-ta-ctn, .fi-wi-stats-overview-stat { background: var(--halaman) !important; }
  .fi-wi-stats-overview-stat-value { font-family: var(--serif); font-variant-numeric: tabular-nums; }
  ::selection { background: color-mix(in srgb, var(--posisi) 45%, transparent); }
</style>
