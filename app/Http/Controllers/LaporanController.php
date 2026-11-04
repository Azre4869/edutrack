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

        // Kehadiran riil (B1: status enum 'Hadir')
        $totalAbsen = Absensi::count();
        $hadirAbsen = Absensi::where('status', 'Hadir')->count();
        $kehadiranPct = $totalAbsen > 0 ? round(($hadirAbsen / $totalAbsen) * 100) : 0;

        // Rata-rata nilai riil
        $avgNilai = Nilai::avg('nilai_akhir');
        $rataRataNilai = $avgNilai ? round($avgNilai, 1) : 0.0;

        // 10 Siswa dengan nilai terendah dari database
        $siswaTerendahQuery = Nilai::select('id_siswa', DB::raw('ROUND(AVG(nilai_akhir), 1) as rata'))
            ->whereNotNull('nilai_akhir')
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

        // Rekapitulasi siswa per tingkat (B2: tingkat integer 7, 8, 9)
        $countVII = Siswa::whereHas('kelas', fn($q) => $q->where('tingkat', 7))->count();
        $countVIII = Siswa::whereHas('kelas', fn($q) => $q->where('tingkat', 8))->count();
        $countIX = Siswa::whereHas('kelas', fn($q) => $q->where('tingkat', 9))->count();

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
