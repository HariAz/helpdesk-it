<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Services\LdapAuthService;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (auth()->check()) return redirect()->route('dashboard');
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if ($user && !$user->is_active) {
            return back()->withErrors(['email' => 'Akun Anda telah dinonaktifkan.'])->withInput();
        }

        if (auth()->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            auth()->user()->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);
            ActivityLog::record('login');
            return redirect()->intended(route('dashboard'));
        }

        // LDAP fallback — only tried when local auth fails
        $ldap = app(LdapAuthService::class);
        if ($ldap->isEnabled()) {
            $ldapUser = $ldap->authenticate($credentials['email'], $credentials['password']);
            if ($ldapUser) {
                auth()->login($ldapUser, $request->boolean('remember'));
                $request->session()->regenerate();
                $ldapUser->update(['last_login_at' => now(), 'last_login_ip' => $request->ip()]);
                ActivityLog::record('login');
                return redirect()->intended(route('dashboard'));
            }
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->withInput();
    }

    public function logout(Request $request)
    {
        ActivityLog::record('logout');
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
