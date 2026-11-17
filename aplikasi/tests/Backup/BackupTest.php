<?php

use App\Models\Opd;
use App\Models\User;
use App\Pemetaan\Backup;
use Illuminate\Support\Facades\File;

afterEach(fn () => File::deleteDirectory(storage_path('app/backup-test')));

test('backup menghasilkan file sqlite yang bisa dibuka dan berisi data', function () {
    Opd::factory()->count(3)->create();

    $path = app(Backup::class)->buat(storage_path('app/backup-test'));

    expect(file_exists($path))->toBeTrue()
        ->and(basename($path))->toMatch('/^pemetaan-\d{8}-\d{6}\.sqlite$/');
    $pdo = new PDO('sqlite:'.$path);
    expect((int) $pdo->query('select count(*) from opds')->fetchColumn())->toBe(3);
});

test('bersihkan menghapus backup lebih lama dari 7 hari', function () {
    $dir = storage_path('app/backup-test');
    File::ensureDirectoryExists($dir);
    touch("{$dir}/pemetaan-lama.sqlite", now()->subDays(8)->timestamp);
    touch("{$dir}/pemetaan-baru.sqlite", now()->subDays(2)->timestamp);

    $dihapus = app(Backup::class)->bersihkan(7, $dir);

    expect($dihapus)->toBe(1)
        ->and(file_exists("{$dir}/pemetaan-lama.sqlite"))->toBeFalse()
        ->and(file_exists("{$dir}/pemetaan-baru.sqlite"))->toBeTrue();
});

test('admin bisa mengunduh backup', function () {
    Opd::factory()->create();
    $this->actingAs(User::factory()->admin()->create())->get('/admin/unduh/backup')
        ->assertOk()->assertDownload();
});
