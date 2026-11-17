@extends('layouts.app')
@section('title', 'Ganti kata sandi · Pemetaan Unit Digital')
@section('content')
<div class="halaman-kecil">
  <header class="pita-atas">
    <span class="nama">{{ auth()->user()->opd ? \App\Pemetaan\Format::namaUnit(auth()->user()->opd->nama) : 'Pemetaan Unit Digital' }}</span>
    <a href="/"><svg class="ikon"><use href="#i-kiri"/></svg> Kembali</a>
  </header>
  <main class="kartu-form">
    <form method="post" action="{{ route('akun.password') }}">
      @csrf
      <h1>Ganti kata sandi</h1>
      <p class="lead">Kata sandi ini dipakai bersama oleh pengisi di OPD Anda. Beritahu rekan setelah menggantinya.</p>
      @if (session('status'))<div class="pesan pesan-ok" role="status"><svg class="ikon"><use href="#i-cek"/></svg><span>{{ session('status') }}</span></div>@endif
      <div class="field">
        <label for="password_lama">Kata sandi saat ini</label>
        <div class="field-sandi">
          <input id="password_lama" name="password_lama" type="password" autocomplete="current-password" required>
          <button type="button" data-lihat-sandi="password_lama" aria-label="Tampilkan kata sandi" aria-pressed="false"><svg class="ikon"><use href="#i-mata"/></svg></button>
        </div>
        @error('password_lama')<div class="galat"><svg class="ikon"><use href="#i-awas"/></svg>{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label for="password">Kata sandi baru</label>
        <div class="field-sandi">
          <input id="password" name="password" type="password" autocomplete="new-password" required minlength="8" aria-describedby="bantu-sandi">
          <button type="button" data-lihat-sandi="password" aria-label="Tampilkan kata sandi" aria-pressed="false"><svg class="ikon"><use href="#i-mata"/></svg></button>
        </div>
        <span class="bantu" id="bantu-sandi">Minimal 8 karakter.</span>
        @error('password')<div class="galat"><svg class="ikon"><use href="#i-awas"/></svg>{{ $message }}</div>@enderror
      </div>
      <div class="field">
        <label for="password_confirmation">Ulangi kata sandi baru</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
      </div>
      <button class="btn btn-utama" type="submit" style="width:100%"><svg class="ikon"><use href="#i-simpan"/></svg> Simpan kata sandi</button>
    </form>
  </main>
</div>
@endsection
@push('scripts')
@include('partials.lihat-sandi')
@endpush
