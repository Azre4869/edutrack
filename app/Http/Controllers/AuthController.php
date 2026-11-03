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
            'username' => 'nullable|string',
            'password' => 'required|string',
            'role' => 'nullable|string',
        ]);

        $usernameInput = trim($credentials['username'] ?? '');
        $roleInput = trim($credentials['role'] ?? '');

        // Pemetaan alias peran & username
        $roleMap = [
            'operator-web' => 'Operator',
            'operator' => 'Operator',
            'admin' => 'Operator',
            'guru-wali' => 'Wali Kelas',
            'wali' => 'Wali Kelas',
            'walikelas' => 'Wali Kelas',
            'guru-mapel' => 'Guru Mapel',
            'guru' => 'Guru Mapel',
            'gurumapel' => 'Guru Mapel',
        ];

        $user = null;

        // 1. Cari berdasarkan username
        if (!empty($usernameInput)) {
            $user = User::where('username', $usernameInput)->first();
            if (!$user) {
                // Cek jika username yang dimasukkan berupa alias role (misal 'wali' atau 'guru')
                $targetRole = $roleMap[strtolower($usernameInput)] ?? null;
                if ($targetRole) {
                    $user = User::where('role', $targetRole)->first();
                }
            }
        }

        // 2. Jika belum ketemu, cari dari opsi dropdown peran
        if (!$user && !empty($roleInput)) {
            $targetRole = $roleMap[strtolower($roleInput)] ?? $roleInput;
            $user = User::where('role', $targetRole)->first();
        }

        // 3. Verifikasi password (mendukung password database maupun password testing mudah)
        $inputPassword = $credentials['password'];
        $validTestPasswords = ['password', '123456', 'operator123', 'wali123', 'guru123', 'admin123'];

        if ($user && (Hash::check($inputPassword, $user->password) || in_array($inputPassword, $validTestPasswords))) {
            Auth::login($user, $request->has('remember'));
            $request->session()->regenerate();
            return $this->redirectBasedOnRole($user);
        }

        return back()->withErrors([
            'username' => 'Kombinasi username atau password tidak valid.',
        ])->withInput($request->only('username', 'role'));
    }

    public function quickLogin($role)
    {
        $roleMap = [
            'operator' => 'Operator',
            'wali' => 'Wali Kelas',
            'guru' => 'Guru Mapel',
        ];

        $targetRole = $roleMap[strtolower($role)] ?? $role;
        $user = User::where('role', $targetRole)->first();

        if ($user) {
            Auth::login($user);
            request()->session()->regenerate();
            return $this->redirectBasedOnRole($user);
        }

        return redirect()->route('login');
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
        return redirect()->route('operator.dashboard');
    }
}
