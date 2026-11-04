<?php

namespace App\Services\EarlyWarning;

use App\Models\AnalisisSiswa;
use App\Models\Notifikasi;
use App\Models\User;
use App\Services\EarlyWarning\DTOs\StudentAnalysisResultDTO;

class NotificationBuilder
{
    /**
     * Buat notifikasi pintar untuk siswa berisiko tinggi / sedang dengan proteksi anti-spam.
     *
     * @param StudentAnalysisResultDTO $result
     * @param AnalisisSiswa $analisisRecord
     * @return int Jumlah notifikasi yang berhasil dibuat
     */
    public function notifyIfNeeded(StudentAnalysisResultDTO $result, AnalisisSiswa $analisisRecord): int
    {
        // Hanya notifikasi jika risiko Tinggi atau Sedang
        if (!in_array($result->tingkatRisiko, ['Tinggi', 'Sedang'])) {
            return 0;
        }

        $siswa = $result->siswa;
        $idWaliGuru = $siswa->kelas?->id_wali_kelas;
        $targetUsers = collect();

        // 1. Wali Kelas dari kelas siswa
        if ($idWaliGuru) {
            $waliUser = User::where('id_guru', $idWaliGuru)->where('role', 'Wali Kelas')->first();
            if ($waliUser) {
                $targetUsers->push($waliUser);
            }
        }

        // 2. Operator (koordinator akademik / BK)
        $operators = User::where('role', 'Operator')->get();
        foreach ($operators as $op) {
            $targetUsers->push($op);
        }

        $targetUsers = $targetUsers->unique('id_user');
        $createdCount = 0;

        foreach ($targetUsers as $targetUser) {
            // Anti-spam: Cek apakah sudah pernah ada notifikasi belum dibaca untuk siswa & analisis ini dalam 24 jam terakhir
            $existingNotif = Notifikasi::where('id_analisis', $analisisRecord->id_analisis)
                ->where('id_user', $targetUser->id_user)
                ->where('sudah_dibaca', false)
                ->exists();

            if (!$existingNotif) {
                $pesan = "Peringatan Dini ({$result->tingkatRisiko}): Siswa {$siswa->nama} (Kelas {$siswa->kelas?->nama_kelas}) terindikasi {$result->jenisPeringatan}. Indikator: {$result->indikator}.";

                Notifikasi::create([
                    'id_analisis' => $analisisRecord->id_analisis,
                    'id_user' => $targetUser->id_user,
                    'tipe' => 'EWS_ALERT',
                    'pesan' => mb_substr($pesan, 0, 500),
                    'metadata' => [
                        'id_siswa' => $siswa->id_siswa,
                        'nama_siswa' => $siswa->nama,
                        'tingkat_risiko' => $result->tingkatRisiko,
                        'skor_risiko' => $result->skorRisiko,
                    ],
                    'sudah_dibaca' => false,
                    'tanggal_kirim' => now(),
                ]);

                $createdCount++;
            }
        }

        return $createdCount;
    }
}
