<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index()
    {
        $totalSiswa = 990;
        $kehadiranPct = 93;
        $rataRataNilai = 90.5;
        $jumlahGuru = 78;

        $siswaTerendah = [
            ['no' => 1, 'nama' => 'Ahmad Fauzi', 'kelas' => 'VII A', 'nilai' => '65,2'],
            ['no' => 2, 'nama' => 'Siti Aisyah', 'kelas' => 'VII B', 'nilai' => '68,7'],
            ['no' => 3, 'nama' => 'Rizky Pratama', 'kelas' => 'VIII A', 'nilai' => '69,1'],
            ['no' => 4, 'nama' => 'Dewi Lestari', 'kelas' => 'VIII B', 'nilai' => '70,3'],
            ['no' => 5, 'nama' => 'Bima Sakti', 'kelas' => 'IX A', 'nilai' => '72,6'],
            ['no' => 6, 'nama' => 'Nabila Putri', 'kelas' => 'IX B', 'nilai' => '73,1'],
            ['no' => 7, 'nama' => 'Fahri Ramadhan', 'kelas' => 'VII A', 'nilai' => '74,5'],
            ['no' => 8, 'nama' => 'Putri Handayani', 'kelas' => 'VIII C', 'nilai' => '75,8'],
            ['no' => 9, 'nama' => 'Agus Setiawan', 'kelas' => 'IX A', 'nilai' => '76,2'],
            ['no' => 10, 'nama' => 'Laila Sari', 'kelas' => 'IX B', 'nilai' => '78,7'],
        ];

        return view('laporan.index', compact(
            'totalSiswa',
            'kehadiranPct',
            'rataRataNilai',
            'jumlahGuru',
            'siswaTerendah'
        ));
    }
}
