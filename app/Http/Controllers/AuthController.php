<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string|max:50',
            'password' => 'required|string|max:255',
        ]);

        $user = User::where('username', trim($credentials['username']))->first();

        if ($user && Auth::attempt(['username' => $user->username, 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'username' => 'Kombinasi username atau password tidak valid.',
        ])->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil logout.');
    }

    private function redirectBasedOnRole($user)
    {
        if ($user->role === 'Operator') {
            return redirect()->route('operator.dashboard');
        } elseif ($user->role === 'Wali Kelas') {
            return redirect()->route('wali.dashboard');
        } elseif ($user->role === 'Guru Mapel') {
            return redirect()->route('guru.dashboard');
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login')->withErrors([
            'username' => 'Role akun Anda tidak valid atau tidak dikenal.',
        ]);
    }
}
