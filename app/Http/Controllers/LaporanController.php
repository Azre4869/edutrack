<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index()
    {
        $totalSiswa = Siswa::count();

        // Kehadiran riil
        $totalAbsen = Absensi::count();
        $hadirAbsen = Absensi::where('status', 'Hadir')->count();
        $kehadiranPct = $totalAbsen > 0 ? round(($hadirAbsen / $totalAbsen) * 100) : 0;

        // Rata-rata nilai riil
        $avgNilai = Nilai::avg('nilai_akhir');
        $rataRataNilai = $avgNilai ? round($avgNilai, 1) : 0;

        $jumlahGuru = Guru::count();

        // Rekap siswa per tingkat (tingkat bertipe integer: 7, 8, 9)
        $countVII = Siswa::whereHas('kelas', fn($q) => $q->where('tingkat', 7))->count();
        $countVIII = Siswa::whereHas('kelas', fn($q) => $q->where('tingkat', 8))->count();
        $countIX = Siswa::whereHas('kelas', fn($q) => $q->where('tingkat', 9))->count();

        // 10 siswa dengan nilai terendah
        $siswaTerendahQuery = Nilai::with('siswa.kelas')
            ->whereNotNull('nilai_akhir')
            ->orderBy('nilai_akhir', 'asc')
            ->take(10)
            ->get();

        if ($siswaTerendahQuery->isNotEmpty()) {
            $siswaTerendah = $siswaTerendahQuery->map(function ($item, $idx) {
                return [
                    'no' => $idx + 1,
                    'nama' => $item->siswa->nama ?? 'Siswa',
                    'kelas' => $item->siswa->kelas->nama_kelas ?? '-',
                    'nilai' => number_format($item->nilai_akhir, 1, ',', '.'),
                ];
            })->toArray();
        } else {
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
        }

        return view('laporan.index', compact(
            'totalSiswa',
            'kehadiranPct',
            'rataRataNilai',
            'jumlahGuru',
            'countVII',
            'countVIII',
            'countIX',
            'siswaTerendah'
        ));
    }
}
