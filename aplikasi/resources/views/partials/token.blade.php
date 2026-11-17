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
  --bayang: 0 1px 2px color-mix(in srgb, var(--tinta) 6%, transparent), 0 8px 24px -12px color-mix(in srgb, var(--tinta) 14%, transparent);
  --bayang-angkat: 0 24px 60px -12px color-mix(in srgb, var(--tinta) 30%, transparent);
  --tirai: color-mix(in srgb, var(--tinta) 40%, transparent);
  --keluar: cubic-bezier(.16, 1, .3, 1);
}
</style>
