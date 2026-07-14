<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (!$token && $request->session()->has('worklive_admin')) return $next($request);
        if (!$token) return response()->json(['ok' => false, 'error' => 'Token del agente requerido.'], 401);
        $agent = Agent::where('company_id', config('worklive.company_id'))->where('token_hash', hash('sha256', $token))->first();
        if (!$agent) return response()->json(['ok' => false, 'error' => 'Token del agente invalido.'], 401);
        $agent->forceFill(['last_seen_at' => now()])->save();
        $request->attributes->set('agent', $agent);
        return $next($request);
    }
}
