<?php

namespace App\Services\EarlyWarning;

use App\Models\Siswa;
use App\Services\EarlyWarning\DTOs\CriterionResultDTO;
use App\Services\EarlyWarning\DTOs\StudentAnalysisResultDTO;

class RiskScoreCalculator
{
    /**
     * Hitung skor komposit dan tentukan tingkat risiko siswa.
     *
     * @param Siswa $siswa
     * @param array<CriterionResultDTO> $criterionResults
     * @param array $extraContext
     * @return StudentAnalysisResultDTO
     */
    public function calculate(Siswa $siswa, array $criterionResults, array $extraContext = []): StudentAnalysisResultDTO
    {
        $rawScore = 0.0;
        $triggeredKriteriaIds = [];
        $alasanList = [];
        $hasCriticalOverride = false;

        $absensiDetail = [];
        $nilaiDetail = [];

        foreach ($criterionResults as $res) {
            $rawScore += $res->skorBobot;

            if ($res->isTriggered) {
                if ($res->idKriteria > 0) {
                    $triggeredKriteriaIds[] = $res->idKriteria;
                }
                if (!empty($res->ringkasan)) {
                    $alasanList[] = $res->ringkasan;
                }
            }

            if ($res->isCriticalOverride) {
                $hasCriticalOverride = true;
            }

            if ($res->kode === 'ABSENSI_ALPHA') {
                $absensiDetail = $res->detail;
            } elseif ($res->kode === 'NILAI_PENURUNAN') {
                $nilaiDetail = $res->detail;
            }
        }

        $totalAlpha = $absensiDetail['total_alpha'] ?? 0;
        $totalAbsen = $absensiDetail['total_pertemuan'] ?? 0;
        $kehadiranPct = $absensiDetail['kehadiran_pct'] ?? 100;
        $currentAvg = (float) ($nilaiDetail['current_avg'] ?? 0.0);

        // Ambang batas kritis tambahan
        if ($totalAlpha >= 5 || ($currentAvg > 0 && $currentAvg < 70.0)) {
            $hasCriticalOverride = true;
        }

        $kriteriaCount = count($triggeredKriteriaIds);
        $tingkatRisiko = 'Normal';
        $jenisPeringatan = 'Status Baik';
        $indikator = 'Semua parameter terpantau baik';

        if ($hasCriticalOverride || $kriteriaCount >= 2 || $rawScore >= 50.0) {
            $tingkatRisiko = 'Tinggi';
            $jenisPeringatan = count($alasanList) > 1
                ? 'Rendahnya Nilai & Kehadiran'
                : (in_array(2, $triggeredKriteriaIds) ? 'Ketidakhadiran Berat' : 'Rendahnya Nilai');
            $indikator = !empty($alasanList) ? implode(' & ', $alasanList) : 'Parameter akademik/kehadiran kritis';
            $skorRisiko = max(75.0, min(100.0, $rawScore));
        } elseif ($kriteriaCount === 1 || $totalAlpha >= 3 || ($currentAvg > 0 && $currentAvg < 75.0) || $rawScore >= 25.0) {
            $tingkatRisiko = 'Sedang';
            $jenisPeringatan = in_array(2, $triggeredKriteriaIds) ? 'Ketidakhadiran' : 'Rendahnya Nilai';
            $indikator = !empty($alasanList) ? implode('; ', $alasanList) : 'Perlu perhatian khusus';
            $skorRisiko = max(40.0, min(74.9, $rawScore));
        } elseif ($totalAlpha >= 1 || ($totalAbsen > 0 && $kehadiranPct < 90)) {
            $tingkatRisiko = 'Kecil';
            $jenisPeringatan = 'Perhatian Ringan';
            $indikator = "Alpha {$totalAlpha} hari";
            $skorRisiko = max(20.0, min(39.9, $rawScore));
        } else {
            $tingkatRisiko = 'Normal';
            $skorRisiko = min(19.9, $rawScore);
        }

        // Snapshot data untuk audit trail
        $snapshot = [
            'total_alpha' => $totalAlpha,
            'kehadiran_pct' => $kehadiranPct,
            'rata_nilai' => $currentAvg,
            'penurunan_teks' => $nilaiDetail['penurunan_teks'] ?? 'Tidak ada data pembanding',
            'kriteria_terpicu' => $triggeredKriteriaIds,
            'alasan' => $alasanList,
            'tanggal_kalkulasi' => now()->toDateTimeString(),
        ];

        return new StudentAnalysisResultDTO(
            siswa: $siswa,
            tingkatRisiko: $tingkatRisiko,
            skorRisiko: round($skorRisiko, 2),
            jenisPeringatan: $jenisPeringatan,
            indikator: $indikator,
            triggeredKriteriaIds: $triggeredKriteriaIds,
            criterionResults: $criterionResults,
            snapshotData: $snapshot
        );
    }
}
