{{-- Kaki menu samping admin: versi yang berjalan, akun yang masuk, tombol keluar. --}}
@php($user = auth()->user())
<div class="kaki-admin">
  <div class="kaki-versi">{{ \App\Pemetaan\Versi::teks() }}</div>
  <div class="kaki-pengguna">
    <span class="kaki-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
    <span class="kaki-teks"><b title="{{ $user->name }}">{{ $user->name }}</b><small>Administrator</small></span>
    <form method="post" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="kaki-keluar" title="Keluar" aria-label="Keluar">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
      </button>
    </form>
  </div>
</div>
