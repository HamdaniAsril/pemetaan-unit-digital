<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="{{ config('tema.warna.sampul') }}">
<title>@yield('title', 'Pemetaan Unit Digital')</title>
<link rel="preload" href="/fonts/atkinson-hyperlegible-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/fonts/source-serif-4-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/fonts/fonts.css">
@include('partials.token')
<style>{!! file_get_contents(resource_path('css/app.css')) !!}</style>
@stack('head')
</head>
<body>
@include('partials.ikon')
@yield('content')
@stack('scripts')
</body>
</html>
