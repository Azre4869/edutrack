<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Wajib Login
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu untuk mengakses sistem.');
        }

        $user = Auth::user();
        $userRoleNormalized = strtolower(str_replace(['-', ' '], '', $user->role ?? ''));

        // 2. Periksa apakah peran user cocok dengan peran yang diizinkan untuk rute ini
        $isAllowed = false;
        foreach ($roles as $allowedRole) {
            $allowedRoleNormalized = strtolower(str_replace(['-', ' '], '', $allowedRole));
            if ($userRoleNormalized === $allowedRoleNormalized) {
                $isAllowed = true;
                break;
            }
        }

        // 3. Strict Role Separation: Jika mencoba mengakses halaman role lain, kembalikan ke dashboard role miliknya
        if (!$isAllowed) {
            $userRole = $user->role;
            if ($userRole === 'Operator') {
                return redirect()->route('operator.dashboard')->with('error', 'Akses dibatasi: Anda login sebagai Operator dan hanya dapat mengakses modul Operator.');
            } elseif ($userRole === 'Wali Kelas') {
                return redirect()->route('wali.dashboard')->with('error', 'Akses dibatasi: Anda login sebagai Wali Kelas dan hanya dapat mengakses modul Wali Kelas.');
            } elseif ($userRole === 'Guru Mapel') {
                return redirect()->route('guru.dashboard')->with('error', 'Akses dibatasi: Anda login sebagai Guru Mapel dan hanya dapat mengakses modul Guru Mapel.');
            }

            return redirect()->route('login')->with('error', 'Role Anda tidak memiliki izin untuk halaman tersebut.');
        }

        return $next($request);
    }
}
