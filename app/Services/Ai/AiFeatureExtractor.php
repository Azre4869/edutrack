<?php

namespace App\Services\Ai;

use App\Models\KriteriaAnalisis;
use App\Models\Semester;
use App\Models\Siswa;

class AiFeatureExtractor
{
    /**
     * Ekstraksi fitur untuk kohor (seluruh siswa di semester, atau kelas tertentu).
     */
    public function extractForCohort(int $semesterId, ?int $kelasId = null): array
    {
        // 1. Dapatkan KKM default dari database kriteria_analisis (fallback ke 75.0)
        $kkmKriteria = KriteriaAnalisis::where('kode', 'NILAI_DIBAWAH_KKM')
            ->orWhere('id_kriteria', 3)
            ->first();
        $kkmDefault = $kkmKriteria ? (float) $kkmKriteria->nilai_ambang : 75.0;

        // 2. Dapatkan semester sebelumnya
        $prevSemester = Semester::where('id_semester', '<', $semesterId)
            ->orderBy('id_semester', 'desc')
            ->first();
        $prevSemesterId = $prevSemester?->id_semester;

        // 3. Eager load semua relasi yang diperlukan
        $siswaQuery = Siswa::with([
            'kelas',
            'absensi' => fn($q) => $q->where('id_semester', $semesterId),
            'nilai' => fn($q) => $q->whereIn('id_semester', array_filter([$semesterId, $prevSemesterId])),
            'nilaiHarian' => fn($q) => $q->where('id_semester', $semesterId),
        ]);

        if ($kelasId) {
            $siswaQuery->where('id_kelas', $kelasId);
        }

        $siswaCollection = $siswaQuery->get();

        // Cari tanggal referensi kohor untuk membagi 3 minggu terakhir vs 6 minggu sebelumnya
        $cohortAttendanceDates = collect();
        foreach ($siswaCollection as $s) {
            $cohortAttendanceDates = $cohortAttendanceDates->merge(
                $s->absensi->pluck('tanggal')
            );
        }
        $uniqueDates = $cohortAttendanceDates->unique()->sort()->values();
        $totalDates = $uniqueDates->count();

        $last3WeeksDates = [];
        $prev6WeeksDates = [];
        if ($totalDates >= 10) {
            $last3WeeksDates = $uniqueDates->slice(max(0, $totalDates - 15))->values()->all();
            $prev6WeeksDates = $uniqueDates->slice(max(0, $totalDates - 45), min(30, max(0, $totalDates - 15)))->values()->all();
        }

        $siswaInputList = [];
        foreach ($siswaCollection as $siswa) {
            $siswaInputList[] = $this->extractForStudent(
                $siswa,
                $semesterId,
                $prevSemesterId,
                $kkmDefault,
                $last3WeeksDates,
                $prev6WeeksDates
            );
        }

        return [
            'semester_id' => $semesterId,
            'kkm' => $kkmDefault,
            'contamination' => 0.08,
            'siswa' => $siswaInputList,
        ];
    }

    /**
     * Ekstraksi fitur untuk 1 siswa sesuai skema SiswaInput.
     */
    public function extractForStudent(
        Siswa $siswa,
        int $semesterId,
        ?int $prevSemesterId = null,
        float $kkmDefault = 75.0,
        array $last3WeeksDates = [],
        array $prev6WeeksDates = []
    ): array {
        // --- 1. NILAI SEMESTER INI ---
        $nilaiCurrent = $siswa->nilai->where('id_semester', $semesterId);
        $nilaiAkhirList = $nilaiCurrent->pluck('nilai_akhir')->filter(fn($v) => !is_null($v))->values();

        $rataNilaiAkhir = $nilaiAkhirList->isNotEmpty() ? round($nilaiAkhirList->avg(), 2) : null;
        $jumlahDiBawahKkm = $nilaiAkhirList->filter(fn($v) => (float)$v < $kkmDefault)->count();

        // Simpangan baku antar mapel
        $simpanganAntarMapel = null;
        if ($nilaiAkhirList->count() >= 2) {
            $mean = $nilaiAkhirList->avg();
            $variance = $nilaiAkhirList->reduce(fn($carry, $item) => $carry + pow($item - $mean, 2), 0) / $nilaiAkhirList->count();
            $simpanganAntarMapel = round(sqrt($variance), 2);
        }

        // --- 2. NILAI SEMESTER LALU & SELISIH ---
        $selisihNilaiVsSemesterLalu = null;
        if ($prevSemesterId && $rataNilaiAkhir !== null) {
            $nilaiPrev = $siswa->nilai->where('id_semester', $prevSemesterId);
            $prevAkhirList = $nilaiPrev->pluck('nilai_akhir')->filter(fn($v) => !is_null($v))->values();
            if ($prevAkhirList->isNotEmpty()) {
                $prevAvg = $prevAkhirList->avg();
                $selisihNilaiVsSemesterLalu = round($rataNilaiAkhir - $prevAvg, 2);
            }
        }

        // --- 3. TP DERET & SLOPE TP ---
        $slopesTpMapel = [];
        $tpSumPerIndex = array_fill(1, 8, ['total' => 0.0, 'count' => 0]);

        foreach ($nilaiCurrent as $row) {
            $tpPoints = [];
            for ($i = 1; $i <= 8; $i++) {
                $col = "tp{$i}";
                $val = $row->$col;
                if (!is_null($val)) {
                    $numVal = (float) $val;
                    $tpPoints[] = $numVal;
                    $tpSumPerIndex[$i]['total'] += $numVal;
                    $tpSumPerIndex[$i]['count']++;
                }
            }
            if (count($tpPoints) >= 2) {
                $slopesTpMapel[] = self::calculateLinearSlope($tpPoints);
            }
        }

        $slopeTp = !empty($slopesTpMapel) ? round(collect($slopesTpMapel)->avg(), 3) : 0.0;

        $deretTp = [];
        for ($i = 1; $i <= 8; $i++) {
            if ($tpSumPerIndex[$i]['count'] > 0) {
                $deretTp[] = round($tpSumPerIndex[$i]['total'] / $tpSumPerIndex[$i]['count'], 2);
            }
        }

        // --- 4. ABSENSI ---
        $absensi = $siswa->absensi->where('id_semester', $semesterId)->sortBy('tanggal')->values();
        $totalAbsen = $absensi->count();
        $hadirCount = $absensi->where('status', 'Hadir')->count();
        $persenHadir = $totalAbsen > 0 ? round(($hadirCount / $totalAbsen) * 100, 2) : null;
        $jumlahAlpha = $absensi->where('status', 'Alpha')->count();

        // Alpha beruntun maksimum
        $maxAlphaRun = 0;
        $currentAlphaRun = 0;
        foreach ($absensi as $absen) {
            if ($absen->status === 'Alpha') {
                $currentAlphaRun++;
                if ($currentAlphaRun > $maxAlphaRun) {
                    $maxAlphaRun = $currentAlphaRun;
                }
            } else {
                $currentAlphaRun = 0;
            }
        }

        // Hadir mingguan deret & slope
        $weeklyAttendance = [];
        foreach ($absensi as $absen) {
            $weekKey = date('Y-W', strtotime($absen->tanggal));
            if (!isset($weeklyAttendance[$weekKey])) {
                $weeklyAttendance[$weekKey] = ['total' => 0, 'hadir' => 0];
            }
            $weeklyAttendance[$weekKey]['total']++;
            if ($absen->status === 'Hadir') {
                $weeklyAttendance[$weekKey]['hadir']++;
            }
        }

        $hadirMingguan = [];
        foreach ($weeklyAttendance as $data) {
            $rate = $data['total'] > 0 ? round(($data['hadir'] / $data['total']) * 100, 2) : 0.0;
            $hadirMingguan[] = $rate;
        }

        $slopeKehadiranMingguan = self::calculateLinearSlope($hadirMingguan);

        // Perubahan kehadiran 3 minggu terakhir vs 6 minggu sebelumnya
        $perubahanKehadiran3 = 0.0;
        if (!empty($last3WeeksDates) && !empty($prev6WeeksDates)) {
            $last3Absen = $absensi->whereIn('tanggal', $last3WeeksDates);
            $prev6Absen = $absensi->whereIn('tanggal', $prev6WeeksDates);

            $last3Rate = $last3Absen->count() > 0
                ? ($last3Absen->where('status', 'Hadir')->count() / $last3Absen->count()) * 100
                : null;
            $prev6Rate = $prev6Absen->count() > 0
                ? ($prev6Absen->where('status', 'Hadir')->count() / $prev6Absen->count()) * 100
                : null;

            if ($last3Rate !== null && $prev6Rate !== null) {
                $perubahanKehadiran3 = round($last3Rate - $prev6Rate, 2);
            }
        }

        // --- 5. NILAI HARIAN ---
        $nilaiHarian = $siswa->nilaiHarian->where('id_semester', $semesterId)->sortBy('tanggal')->values();
        $deretHarian = [];
        foreach ($nilaiHarian as $nh) {
            $deretHarian[] = [
                't' => (string) $nh->tanggal,
                'v' => (float) $nh->nilai,
            ];
        }

        $perubahanNilaiHarian3 = 0.0;
        if (!empty($last3WeeksDates) && !empty($prev6WeeksDates)) {
            $last3NH = $nilaiHarian->whereIn('tanggal', $last3WeeksDates);
            $prev6NH = $nilaiHarian->whereIn('tanggal', $prev6WeeksDates);

            $avg3 = $last3NH->isNotEmpty() ? $last3NH->avg('nilai') : null;
            $avg6 = $prev6NH->isNotEmpty() ? $prev6NH->avg('nilai') : null;

            if ($avg3 !== null && $avg6 !== null) {
                $perubahanNilaiHarian3 = round($avg3 - $avg6, 2);
            }
        }

        return [
            'id_siswa' => $siswa->id_siswa,
            'kelas_id' => (int) $siswa->id_kelas,
            'fitur' => [
                'rata_nilai_akhir' => $rataNilaiAkhir,
                'simpangan_antar_mapel' => $simpanganAntarMapel,
                'jumlah_mapel_di_bawah_kkm' => (int) $jumlahDiBawahKkm,
                'persen_hadir' => $persenHadir,
                'jumlah_alpha' => (int) $jumlahAlpha,
                'alpha_beruntun_maks' => (int) $maxAlphaRun,
                'slope_tp' => $slopeTp,
                'selisih_nilai_vs_semester_lalu' => $selisihNilaiVsSemesterLalu,
                'slope_kehadiran_mingguan' => $slopeKehadiranMingguan,
                'perubahan_kehadiran_3_minggu' => $perubahanKehadiran3,
                'perubahan_nilai_harian_3_minggu' => $perubahanNilaiHarian3,
            ],
            'deret' => [
                'tp' => $deretTp,
                'harian' => $deretHarian,
                'hadir_mingguan' => $hadirMingguan,
            ],
        ];
    }

    /**
     * Hitung kemiringan linier (Ordinary Least Squares slope) dari deret data y.
     */
    public static function calculateLinearSlope(array $values): float
    {
        $n = count($values);
        if ($n < 2) {
            return 0.0;
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumX2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $x = (float) ($i + 1);
            $y = (float) $values[$i];
            $sumX += $x;
            $sumY += $y;
            $sumXY += ($x * $y);
            $sumX2 += ($x * $x);
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if (abs($denominator) < 1e-9) {
            return 0.0;
        }

        return round((($n * $sumXY) - ($sumX * $sumY)) / $denominator, 3);
    }
}
