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
        $totalSiswa = Siswa::count();

        // Kehadiran riil
        $totalAbsen = Absensi::count();
        $hadirAbsen = Absensi::where('status', 'Hadir')->count();
        $kehadiranHariIni = $totalAbsen > 0 ? round(($hadirAbsen / $totalAbsen) * 100) : 0;

        // Rata-rata nilai riil
        $avgNilai = Nilai::avg('nilai_akhir');
        $rataRataNilai = $avgNilai ? round($avgNilai, 1) : 0;

        // Siswa Perlu Perhatian
        $perluPerhatian = AnalisisSiswa::where('status', 'Perlu Perhatian')->count();

        $siswaPerhatian = AnalisisSiswa::with('siswa.kelas')
            ->where('status', 'Perlu Perhatian')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'nama' => $item->siswa->nama ?? 'Siswa',
                    'kelas' => $item->siswa->kelas->nama_kelas ?? 'VII A',
                    'avatar' => 'orange',
                ];
            })->toArray();

        if (empty($siswaPerhatian)) {
            $siswaPerhatian = [
                ['nama' => 'Belum ada data perhatian', 'kelas' => '-', 'avatar' => 'orange'],
            ];
        }

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
        $user = auth()->user();
        $kelasBinaan = $user?->requireKelasBinaan();

        $totalSiswa = Siswa::where('id_kelas', $kelasBinaan->id_kelas)->count();
        $siswaIds = Siswa::where('id_kelas', $kelasBinaan->id_kelas)->pluck('id_siswa');

        $totalAbsen = Absensi::whereIn('id_siswa', $siswaIds)->count();
        $hadirAbsen = Absensi::whereIn('id_siswa', $siswaIds)->where('status', 'Hadir')->count();
        $kehadiranHariIni = $totalAbsen > 0 ? round(($hadirAbsen / $totalAbsen) * 100) : 0;

        $avgNilai = Nilai::whereIn('id_siswa', $siswaIds)->avg('nilai_akhir');
        $rataRataNilai = $avgNilai ? round($avgNilai, 1) : 0;

        $perluPerhatian = AnalisisSiswa::whereIn('id_siswa', $siswaIds)->where('status', 'Perlu Perhatian')->count();

        $siswaPerhatian = AnalisisSiswa::with('siswa.kelas')
            ->whereIn('id_siswa', $siswaIds)
            ->where('status', 'Perlu Perhatian')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'nama' => $item->siswa->nama ?? 'Siswa',
                    'kelas' => $item->siswa->kelas->nama_kelas ?? '-',
                    'avatar' => 'orange',
                ];
            })->toArray();

        if (empty($siswaPerhatian)) {
            $siswaPerhatian = [
                ['nama' => 'Semua siswa terpantau baik', 'kelas' => $kelasBinaan->nama_kelas ?? '-', 'avatar' => 'orange'],
            ];
        }

        return view('wali.dashboard', compact(
            'totalSiswa',
            'kehadiranHariIni',
            'rataRataNilai',
            'perluPerhatian',
            'siswaPerhatian',
            'kelasBinaan'
        ));
    }

    public function guru()
    {
        $user = auth()->user();
        $guru = $user?->guru;

        // Ambil mapel yang diampu guru
        $mapelIds = $guru ? $guru->mataPelajaran->pluck('id_mapel') : collect();

        $totalSiswa = Siswa::count();

        $totalAbsen = Absensi::count();
        $hadirAbsen = Absensi::where('status', 'Hadir')->count();
        $kehadiranHariIni = $totalAbsen > 0 ? round(($hadirAbsen / $totalAbsen) * 100) : 0;

        if ($mapelIds->isNotEmpty()) {
            $avgNilai = Nilai::whereIn('id_mapel', $mapelIds)->avg('nilai_akhir');
        } else {
            $avgNilai = Nilai::avg('nilai_akhir');
        }
        $rataRataNilai = $avgNilai ? round($avgNilai, 1) : 0;

        $perluPerhatian = AnalisisSiswa::where('status', 'Perlu Perhatian')->count();

        $siswaPerhatian = AnalisisSiswa::with('siswa.kelas')
            ->where('status', 'Perlu Perhatian')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'nama' => $item->siswa->nama ?? 'Siswa',
                    'kelas' => $item->siswa->kelas->nama_kelas ?? '-',
                    'avatar' => 'orange',
                ];
            })->toArray();

        if (empty($siswaPerhatian)) {
            $siswaPerhatian = [
                ['nama' => 'Semua siswa terpantau baik', 'kelas' => '-', 'avatar' => 'orange'],
            ];
        }

        return view('guru.dashboard', compact(
            'totalSiswa',
            'kehadiranHariIni',
            'rataRataNilai',
            'perluPerhatian',
            'siswaPerhatian'
        ));
    }
}
