<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AnalisisSiswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function operator()
    {
        $totalSiswa = 990; // Canonical school total from mockup
        $kehadiranHariIni = 93;
        $rataRataNilai = 90.5;
        $perluPerhatian = 5;

        // Siswa Perlu Perhatian list matching Figma
        $siswaPerhatian = [
            ['nama' => 'Firdausi Anam', 'kelas' => 'VII A', 'avatar' => 'orange'],
            ['nama' => 'Iqbal Imam', 'kelas' => 'XI F', 'avatar' => 'orange'],
            ['nama' => 'Fahar Alif', 'kelas' => 'VII G', 'avatar' => 'orange'],
            ['nama' => 'Lukman Hilman', 'kelas' => 'VI H', 'avatar' => 'orange'],
        ];

        return view('operator.dashboard', compact(
            'totalSiswa',
            'kehadiranHariIni',
            'rataRataNilai',
            'perluPerhatian',
            'siswaPerhatian'
        ));
    }

    public function wali()
    {
        $totalSiswa = 34; // Total students in class VII A
        $kehadiranHariIni = 93;
        $rataRataNilai = 90.5;
        $perluPerhatian = 1;

        $siswaPerhatian = [
            ['nama' => 'Firdausi Anam', 'kelas' => 'VII A', 'avatar' => 'orange'],
        ];

        return view('wali.dashboard', compact(
            'totalSiswa',
            'kehadiranHariIni',
            'rataRataNilai',
            'perluPerhatian',
            'siswaPerhatian'
        ));
    }

    public function guru()
    {
        $totalSiswa = 34;
        $kehadiranHariIni = 93;
        $rataRataNilai = 90.5;
        $perluPerhatian = 5;

        $siswaPerhatian = [
            ['nama' => 'Iqbal Imam', 'kelas' => 'XI F', 'avatar' => 'orange'],
        ];

        return view('guru.dashboard', compact(
            'totalSiswa',
            'kehadiranHariIni',
            'rataRataNilai',
            'perluPerhatian',
            'siswaPerhatian'
        ));
    }
}
