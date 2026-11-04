<?php

namespace App\Services\EarlyWarning\Contracts;

use App\Models\KriteriaAnalisis;
use App\Models\Siswa;
use App\Services\EarlyWarning\DTOs\CriterionResultDTO;

interface RiskCriterionInterface
{
    /**
     * Kode unik kriteria (misal: 'ABSENSI_ALPHA', 'NILAI_DIBAWAH_KKM', 'NILAI_PENURUNAN')
     */
    public function getKode(): string;

    /**
     * Evaluasi satu siswa berdasarkan kriteria ini.
     *
     * @param Siswa $siswa
     * @param int $semesterId
     * @param int|null $prevSemesterId
     * @param KriteriaAnalisis|null $kriteriaConfig
     * @param array $extraContext
     * @return CriterionResultDTO
     */
    public function evaluate(
        Siswa $siswa,
        int $semesterId,
        ?int $prevSemesterId = null,
        ?KriteriaAnalisis $kriteriaConfig = null,
        array $extraContext = []
    ): CriterionResultDTO;
}
