<?php

namespace App\Services\EarlyWarning\Criteria;

use App\Models\KriteriaAnalisis;
use App\Models\Siswa;
use App\Services\EarlyWarning\Contracts\RiskCriterionInterface;
use App\Services\EarlyWarning\DTOs\CriterionResultDTO;

class CatatanPerilakuCriteria implements RiskCriterionInterface
{
    public function getKode(): string
    {
        return 'CATATAN_PERILAKU';
    }

    public function evaluate(
        Siswa $siswa,
        int $semesterId,
        ?int $prevSemesterId = null,
        ?KriteriaAnalisis $kriteriaConfig = null,
        array $extraContext = []
    ): CriterionResultDTO {
        $idKriteria = $kriteriaConfig?->id_kriteria ?? 0;
        $namaKriteria = $kriteriaConfig?->nama_kriteria ?? 'Catatan Masalah Perilaku/Karakter';
        $bobot = (float) ($kriteriaConfig?->bobot ?? 1.00);

        // Periksa catatan wali kelas terkait perilaku/kedisiplinan
        $catatan = $siswa->catatanWali?->firstWhere('id_semester', $semesterId);
        $teksCatatan = $catatan?->catatan ?? '';

        $kataKunciMasalah = ['peringatan', 'bolos', 'pelanggaran', 'berkelahi', 'merokok', 'teguran', 'kasus'];
        $ditemukan = false;
        foreach ($kataKunciMasalah as $kw) {
            if (stripos($teksCatatan, $kw) !== false) {
                $ditemukan = true;
                break;
            }
        }

        $ringkasan = $ditemukan ? 'Terdapat catatan perhatian perilaku dari wali kelas' : '';

        return new CriterionResultDTO(
            idKriteria: $idKriteria,
            kode: $this->getKode(),
            namaKriteria: $namaKriteria,
            isTriggered: $ditemukan,
            skorBobot: $ditemukan ? (20.0 * $bobot) : 0.0,
            ringkasan: $ringkasan,
            detail: [
                'catatan_teks' => $teksCatatan,
            ]
        );
    }
}
