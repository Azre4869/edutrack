<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index()
    {
        $totalSiswa = Siswa::count();
        $jumlahGuru = Guru::count();

        // Kehadiran riil
        $totalAbsen = Absensi::count();
        $hadirAbsen = Absensi::where('status', 'H')->count();
        $kehadiranPct = $totalAbsen > 0 ? round(($hadirAbsen / $totalAbsen) * 100) : 95;

        // Rata-rata nilai riil
        $avgNilai = Nilai::avg('nilai_akhir');
        $rataRataNilai = $avgNilai ? round($avgNilai, 1) : 0.0;

        // 10 Siswa dengan nilai terendah dari database
        $siswaTerendahQuery = Nilai::select('id_siswa', DB::raw('ROUND(AVG(nilai_akhir), 1) as rata'))
            ->groupBy('id_siswa')
            ->orderBy('rata', 'asc')
            ->with(['siswa.kelas'])
            ->take(10)
            ->get();

        $siswaTerendah = $siswaTerendahQuery->map(function ($item, $index) {
            return [
                'no' => $index + 1,
                'nama' => $item->siswa->nama ?? 'Siswa',
                'kelas' => $item->siswa->kelas->nama_kelas ?? '-',
                'nilai' => number_format($item->rata, 1, ',', '.'),
            ];
        })->toArray();

        // Rekapitulasi siswa per tingkat (VII, VIII, IX)
        $countVII = Siswa::whereHas('kelas', fn($q) => $q->where('tingkat', 'VII'))->count();
        $countVIII = Siswa::whereHas('kelas', fn($q) => $q->where('tingkat', 'VIII'))->count();
        $countIX = Siswa::whereHas('kelas', fn($q) => $q->where('tingkat', 'IX'))->count();

        return view('laporan.index', compact(
            'totalSiswa',
            'kehadiranPct',
            'rataRataNilai',
            'jumlahGuru',
            'siswaTerendah',
            'countVII',
            'countVIII',
            'countIX'
        ));
    }
}
