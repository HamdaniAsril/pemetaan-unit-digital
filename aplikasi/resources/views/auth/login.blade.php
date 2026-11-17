@extends('layouts.app')
@section('title', 'Masuk · Pemetaan Unit Digital')
@section('content')
<main class="masuk">
  <section class="sampul" aria-labelledby="judul-aplikasi">
    <div class="sampul-instansi">Pemerintah Kabupaten Rokan Hilir</div>
    <h1 class="sampul-judul" id="judul-aplikasi">Pemetaan<span>Unit Digital</span></h1>
    <ol class="sampul-langkah">
      <li><svg class="ikon"><use href="#i-buku"/></svg><div><b>Satu tab untuk setiap bidang</b>Isi dari OPD, lalu bagian, bidang, dan unit di bawahnya.</div></li>
      <li><svg class="ikon"><use href="#i-centang-daftar"/></svg><div><b>Tiga pertanyaan Ya atau Tidak</b>Pertanyaannya sama untuk setiap unit, lengkap dengan petunjuk.</div></li>
      <li><svg class="ikon"><use href="#i-awan"/></svg><div><b>Tersimpan otomatis</b>Boleh dicicil. Kirim setelah semua unit terisi.</div></li>
    </ol>
  </section>

  <section class="masuk-form">
    <form method="post" action="/login" novalidate>
      @csrf
      <h2>Masuk dengan akun OPD</h2>
      <p class="lead">Username dan kata sandi dibagikan oleh admin pemetaan.</p>

      @error('username')
        <div class="pesan pesan-galat" role="alert"><svg class="ikon"><use href="#i-awas"/></svg><span>{{ $message }}</span></div>
      @enderror

      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" spellcheck="false" required autofocus placeholder="contoh: dinsos">
      </div>
      <div class="field">
        <label for="password">Kata sandi</label>
        <div class="field-sandi">
          <input id="password" name="password" type="password" autocomplete="current-password" required>
          <button type="button" data-lihat-sandi="password" aria-label="Tampilkan kata sandi" aria-pressed="false"><svg class="ikon"><use href="#i-mata"/></svg></button>
        </div>
      </div>
      <button class="btn btn-utama" type="submit">Masuk <svg class="ikon"><use href="#i-kanan"/></svg></button>
      <p class="masuk-catatan">Lupa kata sandi atau belum punya akun? Hubungi admin pemetaan untuk mengatur ulang.</p>
    </form>
  </section>
</main>
@endsection
@push('scripts')
@include('partials.lihat-sandi')
@endpush
