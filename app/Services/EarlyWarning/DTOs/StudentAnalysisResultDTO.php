<?php

namespace App\Services\EarlyWarning\DTOs;

use App\Models\Siswa;

class StudentAnalysisResultDTO
{
    /**
     * @param Siswa $siswa
     * @param string $tingkatRisiko 'Tinggi', 'Sedang', 'Kecil', 'Normal'
     * @param float $skorRisiko 0.0 - 100.0
     * @param string $jenisPeringatan
     * @param string $indikator
     * @param array<int> $triggeredKriteriaIds
     * @param array<CriterionResultDTO> $criterionResults
     * @param array $snapshotData
     */
    public function __construct(
        public readonly Siswa $siswa,
        public readonly string $tingkatRisiko,
        public readonly float $skorRisiko,
        public readonly string $jenisPeringatan,
        public readonly string $indikator,
        public readonly array $triggeredKriteriaIds,
        public readonly array $criterionResults,
        public readonly array $snapshotData
    ) {}

    public function isBerisiko(): bool
    {
        return $this->tingkatRisiko !== 'Normal';
    }
}
