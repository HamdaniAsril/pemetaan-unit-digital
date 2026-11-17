<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HanyaOpd
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->role === Role::Opd && $user->opd_id, 403, 'Halaman ini khusus akun OPD.');

        return $next($request);
    }
}
