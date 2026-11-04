<?php

namespace App\Services\EarlyWarning;

use App\Models\AnalisisLog;
use App\Models\AnalisisSiswa;
use App\Models\KriteriaAnalisis;
use App\Models\Nilai;
use App\Models\Semester;
use App\Models\Siswa;
use App\Services\EarlyWarning\Contracts\RiskCriterionInterface;
use App\Services\EarlyWarning\Criteria\AbsensiBerlebihanCriteria;
use App\Services\EarlyWarning\Criteria\CatatanPerilakuCriteria;
use App\Services\EarlyWarning\Criteria\NilaiDiBawahKkmCriteria;
use App\Services\EarlyWarning\Criteria\PenurunanNilaiCriteria;
use App\Services\EarlyWarning\DTOs\StudentAnalysisResultDTO;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class EarlyWarningService
{
    /** @var array<RiskCriterionInterface> */
    protected array $criteria = [];
    protected RiskScoreCalculator $calculator;
    protected NotificationBuilder $notificationBuilder;

    public function __construct(
        ?RiskScoreCalculator $calculator = null,
        ?NotificationBuilder $notificationBuilder = null
    ) {
        $this->calculator = $calculator ?? new RiskScoreCalculator();
        $this->notificationBuilder = $notificationBuilder ?? new NotificationBuilder();

        // Register default criteria strategies
        $this->registerCriterion(new PenurunanNilaiCriteria());
        $this->registerCriterion(new AbsensiBerlebihanCriteria());
        $this->registerCriterion(new NilaiDiBawahKkmCriteria());
        $this->registerCriterion(new CatatanPerilakuCriteria());
    }

    public function registerCriterion(RiskCriterionInterface $criterion): self
    {
        $this->criteria[$criterion->getKode()] = $criterion;
        return $this;
    }

    /**
     * Jalankan analisis EWS lengkap untuk satu semester (bisa difilter per kelas atau seluruh sekolah).
     */
    public function runAnalysis(
        int $semesterId,
        ?int $kelasId = null,
        string $pemicu = 'Manual',
        ?int $userId = null
    ): AnalisisLog {
        $log = AnalisisLog::create([
            'waktu_jalan' => now(),
            'pemicu' => $pemicu,
            'jumlah_siswa_diproses' => 0,
            'total_berisiko' => 0,
            'status' => 'Running',
            'id_user' => $userId,
        ]);

        try {
            // 1. Ambil kriteria aktif dari DB
            $kriteriaList = KriteriaAnalisis::where('aktif', 1)->get()->keyBy(function ($item) {
                return $item->kode ?: (string) $item->id_kriteria;
            });

            // Map ID kriteria lama ke kode jika kode kosong
            $kriteriaById = KriteriaAnalisis::where('aktif', 1)->get()->keyBy('id_kriteria');

            // 2. Ambil semester sebelumnya
            $semesterSebelumnya = Semester::where('id_semester', '<', $semesterId)
                ->orderBy('id_semester', 'desc')
                ->first();
            $prevSemesterId = $semesterSebelumnya?->id_semester;

            // 3. Query siswa
            $siswaQuery = Siswa::with([
                'kelas',
                'absensi' => fn($q) => $q->where('id_semester', $semesterId),
                'nilai' => fn($q) => $q->where('id_semester', $semesterId)->with('mataPelajaran'),
                'nilaiEkstrakurikuler' => fn($q) => $q->where('id_semester', $semesterId)->with('ekstrakurikuler'),
                'catatanWali' => fn($q) => $q->where('id_semester', $semesterId),
            ]);

            if ($kelasId) {
                $siswaQuery->where('id_kelas', $kelasId);
            }

            $siswaCollection = $siswaQuery->get();
            $siswaIds = $siswaCollection->pluck('id_siswa')->toArray();

            // 4. Preload rata-rata semester sebelumnya
            $avgNilaiPrevMap = [];
            if ($prevSemesterId && !empty($siswaIds)) {
                $avgNilaiPrevMap = Nilai::whereIn('id_siswa', $siswaIds)
                    ->where('id_semester', $prevSemesterId)
                    ->whereNotNull('nilai_akhir')
                    ->selectRaw('id_siswa, AVG(nilai_akhir) as avg_nilai')
                    ->groupBy('id_siswa')
                    ->pluck('avg_nilai', 'id_siswa')
                    ->toArray();
            }

            $extraContext = [
                'prev_avg_map' => $avgNilaiPrevMap,
            ];

            $processedCount = 0;
            $berisikoCount = 0;

            DB::transaction(function () use (
                $siswaCollection,
                $semesterId,
                $prevSemesterId,
                $kriteriaList,
                $kriteriaById,
                $extraContext,
                $log,
                &$processedCount,
                &$berisikoCount
            ) {
                foreach ($siswaCollection as $siswa) {
                    $criterionResults = [];

                    foreach ($this->criteria as $kode => $criterion) {
                        // Cari konfigurasi kriteria berdasarkan kode atau fallback ke ID mapping
                        $config = $kriteriaList->get($kode);
                        if (!$config) {
                            if ($kode === 'NILAI_PENURUNAN') $config = $kriteriaById->get(1);
                            elseif ($kode === 'ABSENSI_ALPHA') $config = $kriteriaById->get(2);
                            elseif ($kode === 'NILAI_DIBAWAH_KKM') $config = $kriteriaById->get(3);
                        }

                        // Jika kriteria dinonaktifkan di DB, jangan evaluasi
                        if ($config && !$config->aktif) {
                            continue;
                        }

                        $criterionResults[] = $criterion->evaluate(
                            $siswa,
                            $semesterId,
                            $prevSemesterId,
                            $config,
                            $extraContext
                        );
                    }

                    /** @var StudentAnalysisResultDTO $analysisResult */
                    $analysisResult = $this->calculator->calculate($siswa, $criterionResults, $extraContext);

                    // Persistensi ke database
                    $isBerisiko = $analysisResult->isBerisiko();
                    $statusEnum = $isBerisiko ? 'Perlu Perhatian' : 'Normal';

                    $analisisModel = AnalisisSiswa::updateOrCreate(
                        ['id_siswa' => $siswa->id_siswa, 'id_semester' => $semesterId],
                        [
                            'id_log' => $log->id_log,
                            'tanggal_analisis' => now()->toDateString(),
                            'status' => $statusEnum,
                            'tingkat_risiko' => $analysisResult->tingkatRisiko,
                            'skor_risiko' => $analysisResult->skorRisiko,
                            'ringkasan' => $analysisResult->indikator,
                            'data_snapshot' => $analysisResult->snapshotData,
                        ]
                    );

                    if ($isBerisiko) {
                        $berisikoCount++;
                        if (!empty($analysisResult->triggeredKriteriaIds)) {
                            $analisisModel->kriteria()->sync($analysisResult->triggeredKriteriaIds);
                        } else {
                            $analisisModel->kriteria()->detach();
                        }

                        // Kirim notifikasi jika perlu
                        $this->notificationBuilder->notifyIfNeeded($analysisResult, $analisisModel);
                    } else {
                        $analisisModel->kriteria()->detach();
                    }

                    $processedCount++;
                }
            });

            $log->update([
                'waktu_selesai' => now(),
                'jumlah_siswa_diproses' => $processedCount,
                'total_berisiko' => $berisikoCount,
                'status' => 'Success',
            ]);

            return $log;
        } catch (Throwable $e) {
            $log->update([
                'waktu_selesai' => now(),
                'status' => 'Failed',
                'pesan_error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Mempersiapkan data untuk view early-warning/index.blade.php
     */
    public function getAnalysisViewData(
        int $selectedKelasId,
        int $selectedSemesterId,
        ?string $search = null,
        int $selectedId = 0
    ): array {
        // Jalankan sinkronisasi analisis untuk kelas terpilih agar data selalu akurat & up-to-date
        $siswaCount = Siswa::where('id_kelas', $selectedKelasId)->count();
        if ($siswaCount > 0) {
            $this->runAnalysis($selectedSemesterId, $selectedKelasId, 'Manual', auth()->id());
        }

        // Ambil data siswa di kelas dengan relasi analisis terbaru untuk semester terpilih
        $siswaList = Siswa::where('id_kelas', $selectedKelasId)
            ->with([
                'kelas',
                'absensi' => fn($q) => $q->where('id_semester', $selectedSemesterId),
                'nilai' => fn($q) => $q->where('id_semester', $selectedSemesterId)->with('mataPelajaran'),
                'nilaiEkstrakurikuler' => fn($q) => $q->where('id_semester', $selectedSemesterId)->with('ekstrakurikuler'),
                'analisisSiswa' => fn($q) => $q->where('id_semester', $selectedSemesterId)->with('kriteria'),
            ])
            ->get();

        // Ambil data pembanding semester sebelumnya
        $semesterSebelumnya = Semester::where('id_semester', '<', $selectedSemesterId)
            ->orderBy('id_semester', 'desc')
            ->first();
        $prevSemesterId = $semesterSebelumnya?->id_semester;

        $siswaIds = $siswaList->pluck('id_siswa')->toArray();
        $avgNilaiPrevMap = [];
        if ($prevSemesterId && !empty($siswaIds)) {
            $avgNilaiPrevMap = Nilai::whereIn('id_siswa', $siswaIds)
                ->where('id_semester', $prevSemesterId)
                ->whereNotNull('nilai_akhir')
                ->selectRaw('id_siswa, AVG(nilai_akhir) as avg_nilai')
                ->groupBy('id_siswa')
                ->pluck('avg_nilai', 'id_siswa')
                ->toArray();
        }

        $kriteriaList = KriteriaAnalisis::where('aktif', 1)->get()->keyBy('id_kriteria');
        $kkmAmbang = $kriteriaList[3]->nilai_ambang ?? 75.0;

        $processedStudents = [];
        $counts = [
            'Tinggi' => 0,
            'Sedang' => 0,
            'Kecil' => 0,
            'Normal' => 0,
        ];

        foreach ($siswaList as $siswa) {
            $analisisRecord = $siswa->analisisSiswa->first();
            $tingkatRisiko = $analisisRecord?->tingkat_risiko;
            if (!$tingkatRisiko || $tingkatRisiko === 'Normal') {
                if ($analisisRecord?->status === 'Perlu Perhatian') {
                    $tingkatRisiko = 'Sedang';
                } else {
                    $tingkatRisiko = 'Normal';
                }
            }
            $snapshot = $analisisRecord?->data_snapshot ?? [];

            $absensi = $siswa->absensi;
            $nilai = $siswa->nilai;

            $totalAbsen = $absensi->count();
            $totalAlpha = $absensi->where('status', 'Alpha')->count();
            $totalHadir = $absensi->where('status', 'Hadir')->count();
            $kehadiranPct = $totalAbsen > 0 ? (int) round(($totalHadir / $totalAbsen) * 100) : 100;

            $avgNilai = $nilai->isNotEmpty() && $nilai->whereNotNull('nilai_akhir')->count() > 0
                ? round((float) $nilai->whereNotNull('nilai_akhir')->avg('nilai_akhir'), 1)
                : 0.0;

            $mapelUnderKkm = $nilai->filter(fn($n) => $n->nilai_akhir !== null && $n->nilai_akhir < $kkmAmbang);
            $countUnderKkm = $mapelUnderKkm->count();

            // Hitung teks penurunan nilai
            $prevAvg = isset($avgNilaiPrevMap[$siswa->id_siswa]) ? (float) $avgNilaiPrevMap[$siswa->id_siswa] : null;
            $penurunanNilaiText = 'Tidak ada data pembanding';
            if ($prevAvg !== null && $prevAvg > 0 && $avgNilai > 0) {
                if ($avgNilai < $prevAvg) {
                    $selisih = $prevAvg - $avgNilai;
                    $persenTurun = round(($selisih / $prevAvg) * 100, 1);
                    $penurunanNilaiText = number_format($persenTurun, 1, ',', '.') . '%';
                } else {
                    $penurunanNilaiText = '0%';
                }
            }

            // Ekskul
            $ekskulList = $siswa->nilaiEkstrakurikuler->map(fn($e) => $e->ekstrakurikuler->nama_ekskul ?? '')->filter()->implode(', ');
            $ekskulStatus = $ekskulList ? 'Aktif (' . $ekskulList . ')' : 'Belum Ada';

            $jenisPeringatan = 'Status Baik';
            if ($tingkatRisiko === 'Tinggi') {
                $jenisPeringatan = ($totalAlpha >= 3 && $countUnderKkm > 0) ? 'Rendahnya Nilai & Kehadiran' : ($totalAlpha >= 5 ? 'Ketidakhadiran Berat' : 'Rendahnya Nilai');
            } elseif ($tingkatRisiko === 'Sedang') {
                $jenisPeringatan = ($totalAlpha >= 3) ? 'Ketidakhadiran' : 'Rendahnya Nilai';
            } elseif ($tingkatRisiko === 'Kecil') {
                $jenisPeringatan = 'Perhatian Ringan';
            }

            $counts[$tingkatRisiko] = ($counts[$tingkatRisiko] ?? 0) + 1;

            $item = [
                'id_siswa' => $siswa->id_siswa,
                'nis' => $siswa->nis,
                'nama' => $siswa->nama,
                'kelas' => $siswa->kelas->nama_kelas ?? '-',
                'jenis_peringatan' => $jenisPeringatan,
                'indikator' => $analisisRecord?->ringkasan ?? 'Semua parameter terpantau baik',
                'tingkat_risiko' => $tingkatRisiko,
                'skor_risiko' => (float) ($analisisRecord?->skor_risiko ?? 0.0),
                'rata_nilai' => number_format($avgNilai, 1, ',', '.'),
                'penurunan_nilai' => $penurunanNilaiText,
                'kehadiran' => $kehadiranPct . '%',
                'alpha' => $totalAlpha > 0 ? "{$totalAlpha} hari tanpa keterangan" : "Tidak ada alpha",
                'penurunan_kehadiran' => (100 - $kehadiranPct) . '%',
                'ekskul_status' => $ekskulStatus,
                'kkm_info' => $countUnderKkm > 0 ? "Di bawah KKM pada {$countUnderKkm} mapel" : "Semua mapel tuntas KKM",
                'perilaku' => 'Tidak ada catatan pelanggaran berat',
            ];

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

        if ($search) {
            $siswaEarlyWarning = array_values(array_filter($siswaEarlyWarning, function ($item) use ($search) {
                return stripos($item['nama'], $search) !== false ||
                       stripos($item['nis'], $search) !== false ||
                       stripos($item['kelas'], $search) !== false;
            }));
        } else {
            $siswaEarlyWarning = array_values($siswaEarlyWarning);
        }

        // Selected siswa for side panel
        $selectedSiswa = null;
        if ($selectedId > 0) {
            $selectedSiswa = collect($siswaEarlyWarning)->firstWhere('id_siswa', $selectedId);
        } else {
            $selectedSiswa = !empty($siswaEarlyWarning) ? $siswaEarlyWarning[0] : null;
        }

        // Ambil info log analisis terakhir
        $lastLog = AnalisisLog::latest('id_log')->first();

        return [
            'stats' => $stats,
            'siswaEarlyWarning' => $siswaEarlyWarning,
            'selectedSiswa' => $selectedSiswa,
            'lastLog' => $lastLog,
        ];
    }
}
