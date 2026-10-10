<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;



class DashboardController extends Controller
{

    public function index(Request $request)
    {
        if (!session('user_login')) {
            return redirect()->route('login');
        }

        // --- Ambil info pengguna dari sesi ---
        $userLogin = session('user_login');

        $dataDashboard = [
            'total_siswa'     => 990,
            'kehadiran_hari'  => '93%',
            'rata_nilai'      => '90.5',
            'perlu_perhatian' => 5,
        ];

        $siswaPerhatian = [
            ['nama' => 'Firdausi Anam', 'kelas' => 'VII A'],
            ['nama' => 'Iqbal Imam',    'kelas' => 'VII B'],
            ['nama' => 'Fahar Alif',    'kelas' => 'VIII C'],
            ['nama' => 'Lukman Hilman', 'kelas' => 'VIII D'],
        ];

        return view('dashboard.index', compact(
            'userLogin',
            'dataDashboard',
            'siswaPerhatian'
        ));
    }
}
