<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AnalisisSiswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\KriteriaAnalisis;
use App\Models\Nilai;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

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

        // Perkembangan Nilai & Grafik Kehadiran riil (Semua Siswa Sekolah)
        $perkembangan = $this->getPerkembanganNilai();
        $grafikKehadiran = $this->getGrafikKehadiran();

        return view('operator.dashboard', array_merge(
            compact(
                'totalSiswa',
                'kehadiranHariIni',
                'rataRataNilai',
                'perluPerhatian',
                'siswaPerhatian'
            ),
            $perkembangan,
            $grafikKehadiran
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

        // Perkembangan Nilai & Grafik Kehadiran riil (Siswa Kelas Binaan)
        $perkembangan = $this->getPerkembanganNilai($siswaIds);
        $grafikKehadiran = $this->getGrafikKehadiran($siswaIds);

        return view('wali.dashboard', array_merge(
            compact(
                'totalSiswa',
                'kehadiranHariIni',
                'rataRataNilai',
                'perluPerhatian',
                'siswaPerhatian',
                'kelasBinaan'
            ),
            $perkembangan,
            $grafikKehadiran
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

        // Perkembangan Nilai & Grafik Kehadiran riil (Mapel Guru)
        $perkembangan = $this->getPerkembanganNilai(null, $mapelIds);
        $grafikKehadiran = $this->getGrafikKehadiran();

        return view('guru.dashboard', array_merge(
            compact(
                'totalSiswa',
                'kehadiranHariIni',
                'rataRataNilai',
                'perluPerhatian',
                'siswaPerhatian'
            ),
            $perkembangan,
            $grafikKehadiran
        ));
    }

    /**
     * Hitung metrik perkembangan nilai secara riil dari database.
     */
    private function getPerkembanganNilai(?Collection $siswaIds = null, ?Collection $mapelIds = null): array
    {
        $kkm = KriteriaAnalisis::where('kode', 'KKM')->value('bobot') ?? 75;

        $nilaiQuery = Nilai::query();
        if ($siswaIds !== null && $siswaIds->isNotEmpty()) {
            $nilaiQuery->whereIn('id_siswa', $siswaIds);
        }
        if ($mapelIds !== null && $mapelIds->isNotEmpty()) {
            $nilaiQuery->whereIn('id_mapel', $mapelIds);
        }

        $siswaAvgQuery = (clone $nilaiQuery)
            ->selectRaw('id_siswa, AVG(nilai_akhir) as rata')
            ->groupBy('id_siswa')
            ->get();

        $tuntasCount = $siswaAvgQuery->filter(fn($s) => $s->rata >= $kkm)->count();
        $belumTuntasCount = $siswaAvgQuery->filter(fn($s) => $s->rata < $kkm)->count();
        $totalBernilai = $tuntasCount + $belumTuntasCount;
        $persentaseKetuntasan = $totalBernilai > 0 ? round(($tuntasCount / $totalBernilai) * 100) : 0;

        $avgUh1 = round((clone $nilaiQuery)->avg('tp1') ?? 0, 1);
        $avgUh2 = round((clone $nilaiQuery)->avg('tp2') ?? 0, 1);
        $avgPts = round((clone $nilaiQuery)->avg('sas') ?? 0, 1);

        return [
            'tuntasCount' => $tuntasCount,
            'belumTuntasCount' => $belumTuntasCount,
            'totalBernilai' => $totalBernilai,
            'persentaseKetuntasan' => $persentaseKetuntasan,
            'avgUh1' => $avgUh1,
            'avgUh2' => $avgUh2,
            'avgPts' => $avgPts,
        ];
    }

    /**
     * Hitung tren grafik kehadiran harian secara riil dari database.
     */
    private function getGrafikKehadiran(?Collection $siswaIds = null): array
    {
        $absensiQuery = Absensi::query();
        if ($siswaIds !== null && $siswaIds->isNotEmpty()) {
            $absensiQuery->whereIn('id_siswa', $siswaIds);
        }

        $recentDates = (clone $absensiQuery)
            ->select('tanggal')
            ->distinct()
            ->orderBy('tanggal', 'desc')
            ->take(5)
            ->pluck('tanggal')
            ->reverse()
            ->values();

        $labels = [];
        $hadirData = [];
        $sakitIzinData = [];
        $alpaData = [];
        $totalHadirPct = 0;

        foreach ($recentDates as $tgl) {
            $dateObj = \Carbon\Carbon::parse($tgl)->locale('id');
            $dayName = $dateObj->isoFormat('dddd');
            $dayDate = $dateObj->isoFormat('D MMM');
            $labels[] = [$dayName, $dayDate];

            $dayQuery = (clone $absensiQuery)->where('tanggal', $tgl);
            $totalHarian = (clone $dayQuery)->count();
            $hadirCount = (clone $dayQuery)->where('status', 'Hadir')->count();
            $sakitIzinCount = (clone $dayQuery)->whereIn('status', ['Sakit', 'Izin'])->count();
            $alpaCount = (clone $dayQuery)->where('status', 'Alpha')->count();

            $hPct = $totalHarian > 0 ? round(($hadirCount / $totalHarian) * 100) : 0;
            $siPct = $totalHarian > 0 ? round(($sakitIzinCount / $totalHarian) * 100) : 0;
            $aPct = $totalHarian > 0 ? round(($alpaCount / $totalHarian) * 100) : 0;

            $hadirData[] = $hPct;
            $sakitIzinData[] = $siPct;
            $alpaData[] = $aPct;
            $totalHadirPct += $hPct;
        }

        $countDays = count($recentDates);
        $rataRataPekan = $countDays > 0 ? round($totalHadirPct / $countDays, 1) : 0;

        if (empty($labels)) {
            $labels = [['Senin', '-'], ['Selasa', '-'], ['Rabu', '-'], ['Kamis', '-'], ['Jumat', '-']];
            $hadirData = [0, 0, 0, 0, 0];
            $sakitIzinData = [0, 0, 0, 0, 0];
            $alpaData = [0, 0, 0, 0, 0];
            $rataRataPekan = 0;
        }

        return [
            'kehadiranLabels' => $labels,
            'kehadiranHadir' => $hadirData,
            'kehadiranSakitIzin' => $sakitIzinData,
            'kehadiranAlpa' => $alpaData,
            'kehadiranRataRataPekan' => $rataRataPekan,
        ];
    }
}
