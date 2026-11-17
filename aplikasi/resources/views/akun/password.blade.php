@extends('layouts.app')
@section('title', 'Ganti kata sandi')
@section('content')
<div class="page">
  <form class="card" method="post" action="{{ route('akun.password') }}">
    @csrf
    <h1 class="tab-title">Ganti kata sandi</h1>
    @if (session('status'))<div class="flash">{{ session('status') }}</div>@endif
    <label for="password_lama">Kata sandi lama</label>
    <input id="password_lama" name="password_lama" type="password" autocomplete="current-password" required>
    @error('password_lama')<div class="error">{{ $message }}</div>@enderror
    <label for="password">Kata sandi baru (minimal 8 karakter)</label>
    <input id="password" name="password" type="password" autocomplete="new-password" required>
    @error('password')<div class="error">{{ $message }}</div>@enderror
    <label for="password_confirmation">Ulangi kata sandi baru</label>
    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
    <button class="btn" type="submit" style="margin-top:20px">Simpan</button>
  </form>
</div>
@endsection
