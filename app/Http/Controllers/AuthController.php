<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


class AuthController extends Controller
{
    
    public function tampilFormLogin()
    {
       
        return view('auth.login');
    }

   
    public function prosesLogin(Request $request)
    {
        
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        
        $username = $request->input('username');
        $password = $request->input('password');

       
        $akunValid = [
            ['username' => 'operator',  'password' => 'password', 'role' => 'Operator'],
            ['username' => 'walikelas', 'password' => 'password', 'role' => 'Wali Kelas'],
            ['username' => 'gurumapel', 'password' => 'password', 'role' => 'Guru Mapel'],
        ];

        $userDitemukan = null;
        foreach ($akunValid as $akun) {
            if ($akun['username'] === $username && $akun['password'] === $password) {
                $userDitemukan = $akun;
                break;
            }
        }

     
        if ($userDitemukan) {
            session(['user_login' => $userDitemukan]);

            return redirect()->route('dashboard');
        }

        return back()
            ->withErrors(['username' => 'Username atau password salah.'])
            ->withInput($request->only('username'));
    }

    public function logout(Request $request)
    {
        // Hapus semua data sesi
        $request->session()->flush();

        // Arahkan ke halaman login dengan pesan sukses
        return redirect()->route('login')->with('pesan', 'Anda berhasil logout.');
    }
}
