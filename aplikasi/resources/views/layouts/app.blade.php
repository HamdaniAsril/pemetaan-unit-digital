<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'Pemetaan Unit Digital')</title>
<style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
@stack('head')
</head>
<body>
@auth
<header><div class="wrap wide">
  <div>
    <div class="opd">{{ auth()->user()->opd ? \App\Pemetaan\Format::namaUnit(auth()->user()->opd->nama) : auth()->user()->name }}</div>
    <div class="sub">Pemetaan Unit Digital · Indikator Evaluasi</div>
  </div>
  <nav>
    @if (! request()->routeIs('akun.password'))
      <a href="{{ route('akun.password') }}">Kata sandi</a>
    @else
      <a href="/">Kembali</a>
    @endif
    <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>
  </nav>
</div></header>
@endauth
@yield('content')
@stack('scripts')
</body>
</html>
