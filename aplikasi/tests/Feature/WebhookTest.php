<?php

// Endpoint webhook GitHub untuk server CloudPanel (deploy tanpa root/Docker).
// Aplikasi hanya menaruh tanda "deploy diminta"; cron site menjalankan deploy-cloudpanel.sh.

use Illuminate\Support\Facades\File;

beforeEach(function () {
    config(['services.github.webhook_secret' => 'rahasia-uji', 'services.github.deploy_branch' => 'main']);
    File::delete(storage_path('app/deploy-diminta'));
});
afterEach(fn () => File::delete(storage_path('app/deploy-diminta')));

function kirimWebhook($test, array $payload, string $event = 'push', ?string $rahasia = 'rahasia-uji')
{
    $body = json_encode($payload);
    $tanda = $rahasia === null ? 'sha256=salah' : 'sha256='.hash_hmac('sha256', $body, $rahasia);

    return $test->call('POST', '/hooks/github', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_GITHUB_EVENT' => $event,
        'HTTP_X_HUB_SIGNATURE_256' => $tanda,
    ], $body);
}

test('push ke main dengan tanda tangan benar menaruh tanda deploy', function () {
    kirimWebhook($this, ['ref' => 'refs/heads/main', 'after' => 'abc123'])->assertStatus(202);

    expect(File::exists(storage_path('app/deploy-diminta')))->toBeTrue()
        ->and(File::get(storage_path('app/deploy-diminta')))->toContain('abc123');
});

test('tanda tangan salah ditolak dan tidak menaruh tanda', function () {
    kirimWebhook($this, ['ref' => 'refs/heads/main'], 'push', null)->assertStatus(401);
    expect(File::exists(storage_path('app/deploy-diminta')))->toBeFalse();
});

test('push ke cabang lain diabaikan', function () {
    kirimWebhook($this, ['ref' => 'refs/heads/coba'])->assertStatus(202)->assertSee('diabaikan');
    expect(File::exists(storage_path('app/deploy-diminta')))->toBeFalse();
});

test('ping dari GitHub dijawab pong', function () {
    kirimWebhook($this, ['zen' => 'x'], 'ping')->assertOk()->assertSee('pong');
});

test('endpoint mati bila rahasia tidak diatur (misalnya di VPS Docker)', function () {
    config(['services.github.webhook_secret' => null]);
    kirimWebhook($this, ['ref' => 'refs/heads/main'])->assertNotFound();
    expect(File::exists(storage_path('app/deploy-diminta')))->toBeFalse();
});
