{{-- Token desain dari config/tema.php → CSS custom properties. Dipakai halaman OPD dan panel admin. --}}
<style>
:root {
@foreach (config('tema.warna') as $nama => $nilai)
  --{{ $nama }}: {{ $nilai }};
@endforeach
@foreach (config('tema.huruf') as $nama => $nilai)
  --{{ $nama }}: {!! $nilai !!};
@endforeach
@foreach (array_merge(config('tema.ukuran'), config('tema.bentuk')) as $nama => $nilai)
  --{{ $nama }}: {{ $nilai }};
@endforeach
  --bayang-angkat: 0 24px 60px -12px color-mix(in srgb, var(--sampul) 45%, transparent);
  --tirai: color-mix(in srgb, var(--sampul) 45%, transparent);
  --keluar: cubic-bezier(.16, 1, .3, 1);
}
</style>
