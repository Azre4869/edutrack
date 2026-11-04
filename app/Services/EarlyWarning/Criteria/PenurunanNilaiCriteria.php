<?php

namespace App\Services\EarlyWarning\Criteria;

use App\Models\KriteriaAnalisis;
use App\Models\Siswa;
use App\Services\EarlyWarning\Contracts\RiskCriterionInterface;
use App\Services\EarlyWarning\DTOs\CriterionResultDTO;

class PenurunanNilaiCriteria implements RiskCriterionInterface
{
    public function getKode(): string
    {
        return 'NILAI_PENURUNAN';
    }

    public function evaluate(
        Siswa $siswa,
        int $semesterId,
        ?int $prevSemesterId = null,
        ?KriteriaAnalisis $kriteriaConfig = null,
        array $extraContext = []
    ): CriterionResultDTO {
        $idKriteria = $kriteriaConfig?->id_kriteria ?? 1;
        $namaKriteria = $kriteriaConfig?->nama_kriteria ?? 'Nilai Turun Signifikan';
        $bobot = (float) ($kriteriaConfig?->bobot ?? 1.00);
        $ambangTurun = (float) ($kriteriaConfig?->nilai_ambang ?? 10.0);

        $nilaiList = $siswa->nilai;
        $currentAvg = $nilaiList->isNotEmpty() && $nilaiList->whereNotNull('nilai_akhir')->count() > 0
            ? round((float) $nilaiList->whereNotNull('nilai_akhir')->avg('nilai_akhir'), 1)
            : 0.0;

        // Ambil rata-rata nilai semester sebelumnya dari extraContext jika ada untuk efisiensi
        $prevAvg = $extraContext['prev_avg_map'][$siswa->id_siswa] ?? null;

        $isTriggered = false;
        $persenTurun = 0.0;
        $ringkasan = '';
        $penurunanNilaiText = 'Tidak ada data pembanding';

        if ($prevAvg !== null && (float) $prevAvg > 0 && $currentAvg > 0) {
            $prevAvgFloat = (float) $prevAvg;
            if ($currentAvg < $prevAvgFloat) {
                $selisih = $prevAvgFloat - $currentAvg;
                $persenTurun = round(($selisih / $prevAvgFloat) * 100, 1);
                $penurunanNilaiText = number_format($persenTurun, 1, ',', '.') . '%';

                if ($persenTurun >= $ambangTurun) {
                    $isTriggered = true;
                    $ringkasan = "Rata-rata nilai turun {$penurunanNilaiText}";
                }
            } else {
                $penurunanNilaiText = '0%';
            }
        }

        return new CriterionResultDTO(
            idKriteria: $idKriteria,
            kode: $this->getKode(),
            namaKriteria: $namaKriteria,
            isTriggered: $isTriggered,
            skorBobot: $isTriggered ? min(30.0 * $bobot, ($persenTurun * 2.0) * $bobot) : 0.0,
            ringkasan: $ringkasan,
            detail: [
                'current_avg' => $currentAvg,
                'prev_avg' => $prevAvg,
                'persen_turun' => $persenTurun,
                'penurunan_teks' => $penurunanNilaiText,
            ]
        );
    }
}
