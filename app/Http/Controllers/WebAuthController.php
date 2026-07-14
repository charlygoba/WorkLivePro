<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class WebAuthController extends Controller
{
    public function showLogin() { return view('auth.login'); }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $admin = DB::table('authorized_admins')->where('company_id', config('worklive.company_id'))->where('email', strtolower(trim($data['email'])))->first();
        $hash = (string) ($admin->password_hash ?? '');
        $compatibleHash = str_starts_with($hash, '$2b$') ? '$2y$'.substr($hash, 4) : $hash;
        $validPassword = $compatibleHash !== '' && password_verify($data['password'], $compatibleHash);
        if (!$admin || !$validPassword) return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])->withInput($request->only('email'));
        DB::table('authorized_admins')->where('company_id', config('worklive.company_id'))->where('email', $admin->email)->update(['last_login_at' => now()]);
        $request->session()->regenerate();
        $request->session()->put('worklive_admin', ['email' => $admin->email, 'displayName' => $admin->email]);
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request) { $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login'); }

    public function apiLogin(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        $admin = DB::table('authorized_admins')->where('company_id', config('worklive.company_id'))->where('email', strtolower(trim($data['email'])))->first();
        $hash = (string) ($admin->password_hash ?? '');
        $compatibleHash = str_starts_with($hash, '$2b$') ? '$2y$'.substr($hash, 4) : $hash;
        if (!$admin || !$hash || !password_verify($data['password'], $compatibleHash)) return response()->json(['ok' => false, 'error' => 'Correo o contraseña incorrectos.'], 403);
        DB::table('authorized_admins')->where('company_id', config('worklive.company_id'))->where('email', $admin->email)->update(['last_login_at' => now()]);
        $request->session()->regenerate();
        $request->session()->put('worklive_admin', ['email' => $admin->email, 'displayName' => $admin->email]);
        return response()->json(['ok' => true, 'user' => ['uid' => $admin->email, 'email' => $admin->email, 'displayName' => $admin->email, 'photoURL' => null, 'isAdmin' => true]]);
    }
}
