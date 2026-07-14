<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkLiveAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->session()->has('worklive_admin')) return redirect()->route('login');
        return $next($request);
    }
}
