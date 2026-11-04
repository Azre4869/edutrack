<?php

namespace App\Services\EarlyWarning\DTOs;

class CriterionResultDTO
{
    public function __construct(
        public readonly int $idKriteria,
        public readonly string $kode,
        public readonly string $namaKriteria,
        public readonly bool $isTriggered,
        public readonly float $skorBobot,
        public readonly string $ringkasan,
        public readonly array $detail = [],
        public readonly bool $isCriticalOverride = false
    ) {}
}
