<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AnalisisKriteria;
use App\Models\AnalisisSiswa;
use App\Models\Kelas;
use App\Models\KriteriaAnalisis;
use App\Models\Nilai;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;

class EarlyWarningController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $semesterList = Semester::orderBy('id_semester')->get();

        // --- Data-Level Access Control ---
        if ($user && $user->isWaliKelas()) {
            $kelasBinaan = $user->requireKelasBinaan();
            $kelasList = collect([$kelasBinaan]);
            $defaultKelasId = $kelasBinaan->id_kelas;
            $selectedKelasId = $kelasBinaan->id_kelas; // Wali hanya bisa melihat kelasnya sendiri
        } else {
            // Operator: akses penuh
            $kelasList = Kelas::orderBy('nama_kelas')->get();
            $defaultKelasId = $kelasList->first()->id_kelas ?? 1;
            $selectedKelasId = (int) $request->query('kelas_id', $defaultKelasId);
        }

        $selectedSemesterId = (int) $request->query('semester_id', 2);
        $search = $request->query('search');

        // Ambil kriteria analisis aktif dari database
        $kriteriaList = KriteriaAnalisis::where('aktif', 1)->get()->keyBy('id_kriteria');

        // Ambil seluruh siswa di kelas terpilih beserta nilai, absensi, dan ekskul
        $allSiswaKelas = Siswa::where('id_kelas', $selectedKelasId)
            ->with([
                'kelas',
                'absensi' => fn($q) => $q->where('id_semester', $selectedSemesterId),
                'nilai' => fn($q) => $q->where('id_semester', $selectedSemesterId)->with('mataPelajaran'),
                'nilaiEkstrakurikuler' => fn($q) => $q->where('id_semester', $selectedSemesterId)->with('ekstrakurikuler'),
                'analisisSiswa' => fn($q) => $q->where('id_semester', $selectedSemesterId)->with('kriteria'),
            ])
            ->get();

        $processedStudents = [];
        $counts = [
            'Tinggi' => 0,
            'Sedang' => 0,
            'Kecil' => 0,
            'Normal' => 0,
        ];

        foreach ($allSiswaKelas as $siswa) {
            $absensi = $siswa->absensi;
            $nilai = $siswa->nilai;

            // 1. Kehadiran
            $totalAbsen = $absensi->count();
            $totalAlpha = $absensi->where('status', 'Alpha')->count();
            $totalHadir = $absensi->where('status', 'Hadir')->count();
            $kehadiranPct = $totalAbsen > 0 ? round(($totalHadir / $totalAbsen) * 100) : 100;

            // 2. Nilai & KKM
            $avgNilai = $nilai->isNotEmpty() ? round($nilai->avg('nilai_akhir'), 1) : 0.0;
            $kkmAmbang = $kriteriaList[3]->nilai_ambang ?? 75.0;
            $mapelUnderKkm = $nilai->where('nilai_akhir', '<', $kkmAmbang);
            $countUnderKkm = $mapelUnderKkm->count();

            // 3. Cek Kriteria Analisis yang Terpicu
            $terpicuKriteriaIds = [];
            $alasan = [];

            // Kriteria 2: Kehadiran Alpha Berlebih (ambang default 3 hari / kehadiran < 85%)
            $ambangAlpha = $kriteriaList[2]->nilai_ambang ?? 3.0;
            if ($totalAlpha >= $ambangAlpha || ($totalAbsen > 0 && $kehadiranPct < 85)) {
                $terpicuKriteriaIds[] = 2;
                $alasan[] = "Alpha {$totalAlpha} hari (Kehadiran {$kehadiranPct}%)";
            }

            // Kriteria 3: Di Bawah KKM Minimal
            if ($countUnderKkm >= 1) {
                $terpicuKriteriaIds[] = 3;
                $mapelNames = $mapelUnderKkm->pluck('mataPelajaran.nama_mapel')->take(2)->implode(', ');
                $alasan[] = "Di bawah KKM ({$countUnderKkm} mapel: {$mapelNames})";
            }

            // Kriteria 1: Nilai Turun Signifikan (cek jika ada record di analisis_siswa sebelumnya)
            $existingAnalisis = $siswa->analisisSiswa->first();
            if ($existingAnalisis && $existingAnalisis->kriteria->contains('id_kriteria', 1)) {
                $terpicuKriteriaIds[] = 1;
                $alasan[] = "Rata-rata nilai turun > 10%";
            }

            // 4. Tingkat Risiko
            $tingkatRisiko = 'Normal';
            $jenisPeringatan = 'Status Baik';
            $indikator = 'Semua parameter terpantau baik';

            $kriteriaCount = count($terpicuKriteriaIds);
            if ($kriteriaCount >= 2 || $totalAlpha >= 5 || ($avgNilai > 0 && $avgNilai < 70)) {
                $tingkatRisiko = 'Tinggi';
                $jenisPeringatan = count($alasan) > 1 ? 'Rendahnya Nilai & Kehadiran' : (in_array(2, $terpicuKriteriaIds) ? 'Ketidakhadiran Berat' : 'Rendahnya Nilai');
                $indikator = implode(' & ', $alasan);
            } elseif ($kriteriaCount === 1 || $totalAlpha >= 3 || ($avgNilai > 0 && $avgNilai < 75)) {
                $tingkatRisiko = 'Sedang';
                $jenisPeringatan = in_array(2, $terpicuKriteriaIds) ? 'Ketidakhadiran' : 'Rendahnya Nilai';
                $indikator = implode('; ', $alasan);
            } elseif ($totalAlpha >= 1 || ($totalAbsen > 0 && $kehadiranPct < 90)) {
                $tingkatRisiko = 'Kecil';
                $jenisPeringatan = 'Perhatian Ringan';
                $indikator = "Alpha {$totalAlpha} hari";
            }

            $counts[$tingkatRisiko]++;

            // Ekskul
            $ekskulList = $siswa->nilaiEkstrakurikuler->map(fn($e) => $e->ekstrakurikuler->nama_ekskul ?? '')->filter()->implode(', ');
            $ekskulStatus = $ekskulList ? 'Aktif (' . $ekskulList . ')' : 'Belum Ada';

            $item = [
                'id_siswa' => $siswa->id_siswa,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                'jenis_peringatan' => $jenisPeringatan,
                'indikator' => $indikator,
                'tingkat_risiko' => $tingkatRisiko,
                'rata_nilai' => number_format($avgNilai, 1, ',', '.'),
                'penurunan_nilai' => in_array(1, $terpicuKriteriaIds) ? '12%' : '0%',
                'kehadiran' => $kehadiranPct . '%',
                'alpha' => $totalAlpha > 0 ? "{$totalAlpha} hari tanpa keterangan" : "Tidak ada alpha",
                'penurunan_kehadiran' => (100 - $kehadiranPct) . '%',
                'ekskul_status' => $ekskulStatus,
                'kkm_info' => $countUnderKkm > 0 ? "Di bawah KKM pada {$countUnderKkm} mapel" : "Semua mapel tuntas KKM",
                'perilaku' => 'Tidak ada catatan pelanggaran berat',
            ];

            // Sinkronkan ke tabel analisis_siswa jika berisiko; pulihkan ke Normal jika sudah membaik (B4)
            if ($tingkatRisiko !== 'Normal') {
                $statusEnum = 'Perlu Perhatian';
                $analisisModel = AnalisisSiswa::updateOrCreate(
                    ['id_siswa' => $siswa->id_siswa, 'id_semester' => $selectedSemesterId],
                    [
                        'tanggal_analisis' => now()->toDateString(),
                        'status' => $statusEnum,
                        'ringkasan' => $indikator,
                    ]
                );

                if (!empty($terpicuKriteriaIds)) {
                    $analisisModel->kriteria()->sync($terpicuKriteriaIds);
                }
            } else {
                AnalisisSiswa::where('id_siswa', $siswa->id_siswa)
                    ->where('id_semester', $selectedSemesterId)
                    ->update(['status' => 'Normal']);
            }

            $processedStudents[] = $item;
        }

        $totalSiswaCount = count($processedStudents);

        $calculatePct = function ($count) use ($totalSiswaCount) {
            if ($totalSiswaCount == 0) return '0%';
            return number_format(($count / $totalSiswaCount) * 100, 1, ',', '.') . '%';
        };

        $stats = [
            'tinggi' => ['count' => $counts['Tinggi'], 'pct' => $calculatePct($counts['Tinggi'])],
            'sedang' => ['count' => $counts['Sedang'], 'pct' => $calculatePct($counts['Sedang'])],
            'kecil' => ['count' => $counts['Kecil'], 'pct' => $calculatePct($counts['Kecil'])],
            'normal' => ['count' => $counts['Normal'], 'pct' => $calculatePct($counts['Normal'])],
        ];

        // Filter: Hanya siswa yang memiliki risiko (Tinggi, Sedang, Kecil)
        $siswaEarlyWarning = array_filter($processedStudents, function ($st) {
            return $st['tingkat_risiko'] !== 'Normal';
        });

        // Filter if search query exists with re-indexed array_values
        if ($search) {
            $siswaEarlyWarning = array_values(array_filter($siswaEarlyWarning, function ($item) use ($search) {
                return stripos($item['nama'], $search) !== false ||
                       stripos($item['nis'], $search) !== false ||
                       stripos($item['kelas'], $search) !== false;
            }));
        } else {
            $siswaEarlyWarning = array_values($siswaEarlyWarning);
        }

        // Active selected student for side panel with proper null handling when empty
        $selectedId = (int) $request->query('selected_id', 0);
        $selectedSiswa = null;

        if ($selectedId > 0) {
            // Cari hanya di dalam kelas yang sedang difilter; jangan tampilkan siswa kelas lain diam-diam
            $selectedSiswa = collect($processedStudents)->firstWhere('id_siswa', $selectedId);
        }

        if (!$selectedSiswa) {
            // Jika selected_id dikirim tapi siswa tidak ada di kelas terfilter, jangan tampilkan siswa lain.
            // Fallback otomatis (siswa pertama berisiko) hanya berlaku ketika selected_id tidak dikirim.
            if ($selectedId > 0) {
                $selectedSiswa = null; // Panel akan menampilkan "Tidak Ada Siswa Terpilih"
            } else {
                $selectedSiswa = !empty($siswaEarlyWarning) ? $siswaEarlyWarning[0] : (!empty($processedStudents) ? $processedStudents[0] : [
                    'id_siswa' => 0,
                    'nis' => '-',
                    'nama' => 'Tidak Ada Siswa Terpilih',
                    'kelas' => '-',
                    'jenis_peringatan' => '-',
                    'indikator' => 'Tidak ada siswa yang memenuhi kriteria pencarian.',
                    'tingkat_risiko' => 'Normal',
                    'rata_nilai' => '0,0',
                    'penurunan_nilai' => '0%',
                    'kehadiran' => '100%',
                    'alpha' => '-',
                    'penurunan_kehadiran' => '0%',
                    'ekskul_status' => '-',
                    'kkm_info' => '-',
                    'perilaku' => '-',
                ]);
            }
        }

        return view('early-warning.index', compact(
            'kelasList',
            'semesterList',
            'selectedKelasId',
            'selectedSemesterId',
            'search',
            'stats',
            'siswaEarlyWarning',
            'selectedSiswa'
        ));
    }
}
