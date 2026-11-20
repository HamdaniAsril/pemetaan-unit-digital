<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

/**
 * Penerima webhook GitHub untuk server CloudPanel (tanpa root/Docker).
 * Hanya menaruh tanda storage/app/deploy-diminta; cron site menjalankan deploy/deploy-cloudpanel.sh
 * yang mengambil tanda itu. Aplikasi sendiri tidak pernah menjalankan perintah shell.
 * Mati (404) bila GITHUB_WEBHOOK_SECRET kosong, misalnya di VPS yang memakai webhook Node terpisah.
 */
class WebhookGithubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $rahasia = (string) config('services.github.webhook_secret');
        abort_if($rahasia === '', 404);

        $harapan = 'sha256='.hash_hmac('sha256', $request->getContent(), $rahasia);
        if (! hash_equals($harapan, (string) $request->header('X-Hub-Signature-256'))) {
            return response('tanda tangan tidak cocok', 401);
        }

        $event = (string) $request->header('X-GitHub-Event');
        if ($event === 'ping') {
            return response('pong', 200);
        }
        if ($event !== 'push') {
            return response("event {$event} diabaikan", 202);
        }

        $cabang = (string) config('services.github.deploy_branch', 'main');
        $ref = (string) $request->json('ref');
        if ($ref !== "refs/heads/{$cabang}") {
            return response("push ke {$ref} diabaikan", 202);
        }

        File::put(storage_path('app/deploy-diminta'), now()->toIso8601String().' '.$request->json('after')."\n");

        return response('deploy diminta; dijalankan cron dalam 1 menit', 202);
    }
}
