<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWorkLiveAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('worklive.admin_api_key');
        $provided = (string) $request->header('X-WorkLive-Admin-Key');
        if ($configured === '' || !hash_equals($configured, $provided)) {
            return response()->json(['ok' => false, 'error' => 'Credenciales administrativas invalidas.'], 401);
        }
        return $next($request);
    }
}
