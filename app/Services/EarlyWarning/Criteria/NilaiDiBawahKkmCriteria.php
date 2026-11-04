<?php

namespace App\Services\EarlyWarning\Criteria;

use App\Models\KriteriaAnalisis;
use App\Models\Siswa;
use App\Services\EarlyWarning\Contracts\RiskCriterionInterface;
use App\Services\EarlyWarning\DTOs\CriterionResultDTO;

class NilaiDiBawahKkmCriteria implements RiskCriterionInterface
{
    public function getKode(): string
    {
        return 'NILAI_DIBAWAH_KKM';
    }

    public function evaluate(
        Siswa $siswa,
        int $semesterId,
        ?int $prevSemesterId = null,
        ?KriteriaAnalisis $kriteriaConfig = null,
        array $extraContext = []
    ): CriterionResultDTO {
        $idKriteria = $kriteriaConfig?->id_kriteria ?? 3;
        $namaKriteria = $kriteriaConfig?->nama_kriteria ?? 'Di Bawah KKM Minimal';
        $bobot = (float) ($kriteriaConfig?->bobot ?? 1.00);
        $kkmAmbang = (float) ($kriteriaConfig?->nilai_ambang ?? 75.0);

        $nilaiList = $siswa->nilai;
        $mapelUnderKkm = $nilaiList->filter(function ($n) use ($kkmAmbang) {
            return $n->nilai_akhir !== null && (float) $n->nilai_akhir < $kkmAmbang;
        });

        $countUnderKkm = $mapelUnderKkm->count();
        $isTriggered = $countUnderKkm >= 1;

        $mapelNames = $mapelUnderKkm->pluck('mataPelajaran.nama_mapel')->filter()->take(3)->implode(', ');
        $ringkasan = '';
        if ($isTriggered) {
            $ringkasan = "Di bawah KKM ({$countUnderKkm} mapel" . ($mapelNames ? ": {$mapelNames}" : "") . ")";
        }

        return new CriterionResultDTO(
            idKriteria: $idKriteria,
            kode: $this->getKode(),
            namaKriteria: $namaKriteria,
            isTriggered: $isTriggered,
            skorBobot: $isTriggered ? min(35.0 * $bobot, 15.0 * $countUnderKkm * $bobot) : 0.0,
            ringkasan: $ringkasan,
            detail: [
                'count_under_kkm' => $countUnderKkm,
                'mapel_under_kkm' => $mapelNames,
                'kkm_ambang' => $kkmAmbang,
            ]
        );
    }
}
