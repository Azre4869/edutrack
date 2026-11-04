<?php

namespace App\Services\EarlyWarning\Criteria;

use App\Models\KriteriaAnalisis;
use App\Models\Siswa;
use App\Services\EarlyWarning\Contracts\RiskCriterionInterface;
use App\Services\EarlyWarning\DTOs\CriterionResultDTO;

class AbsensiBerlebihanCriteria implements RiskCriterionInterface
{
    public function getKode(): string
    {
        return 'ABSENSI_ALPHA';
    }

    public function evaluate(
        Siswa $siswa,
        int $semesterId,
        ?int $prevSemesterId = null,
        ?KriteriaAnalisis $kriteriaConfig = null,
        array $extraContext = []
    ): CriterionResultDTO {
        $idKriteria = $kriteriaConfig?->id_kriteria ?? 2;
        $namaKriteria = $kriteriaConfig?->nama_kriteria ?? 'Kehadiran Alpha Berlebih';
        $bobot = (float) ($kriteriaConfig?->bobot ?? 1.25);
        $ambangAlpha = (float) ($kriteriaConfig?->nilai_ambang ?? 3.0);

        $absensi = $siswa->absensi;
        $totalAbsen = $absensi->count();
        $totalAlpha = $absensi->where('status', 'Alpha')->count();
        $totalHadir = $absensi->where('status', 'Hadir')->count();
        $totalIzin  = $absensi->where('status', 'Izin')->count();
        $totalSakit = $absensi->where('status', 'Sakit')->count();

        $kehadiranPct = $totalAbsen > 0 ? (int) round(($totalHadir / $totalAbsen) * 100) : 100;

        $isTriggered = ($totalAlpha >= $ambangAlpha) || ($totalAbsen > 0 && $kehadiranPct < 85);
        $isCritical = $totalAlpha >= 5; // Alpha >= 5 hari = risiko tinggi otomatis (critical override)

        $ringkasan = '';
        if ($isTriggered) {
            $ringkasan = "Alpha {$totalAlpha} hari (Kehadiran {$kehadiranPct}%)";
        }

        return new CriterionResultDTO(
            idKriteria: $idKriteria,
            kode: $this->getKode(),
            namaKriteria: $namaKriteria,
            isTriggered: $isTriggered,
            skorBobot: $isTriggered ? (25.0 * $bobot) : ($totalAlpha >= 1 ? 10.0 : 0.0),
            ringkasan: $ringkasan,
            detail: [
                'total_alpha' => $totalAlpha,
                'total_hadir' => $totalHadir,
                'total_izin' => $totalIzin,
                'total_sakit' => $totalSakit,
                'kehadiran_pct' => $kehadiranPct,
                'total_pertemuan' => $totalAbsen,
            ],
            isCriticalOverride: $isCritical
        );
    }
}
