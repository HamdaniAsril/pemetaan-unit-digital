@extends('layouts.app')
@section('title', 'Masuk · Pemetaan Unit Digital')
@section('content')
<section id="login">
  <form class="login-card" method="post" action="/login">
    @csrf
    <h1>Pemetaan Unit Digital</h1>
    <p>Kabupaten Rokan Hilir · Masuk dengan akun OPD</p>
    <label for="username">Username</label>
    <input id="username" name="username" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" required autofocus>
    <label for="password">Kata sandi</label>
    <input id="password" name="password" type="password" autocomplete="current-password" required>
    @error('username')<div class="error" role="alert">{{ $message }}</div>@enderror
    <button class="btn" type="submit">Masuk</button>
    <div class="note">Belum punya akun atau lupa kata sandi? Hubungi admin pemetaan.</div>
  </form>
</section>
@endsection
