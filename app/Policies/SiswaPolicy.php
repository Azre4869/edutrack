<?php

namespace App\Policies;

use App\Models\Siswa;
use App\Models\User;

/**
 * Satu tempat aturan otorisasi data siswa.
 *
 * Pertanyaan yang dijawab:
 *   canAccessSiswa()  – "bolehkah user ini membaca/menulis siswa ini?"
 *   canAccessMapel()  – "bolehkah user ini menulis nilai mapel ini?"
 *   requiresKelas()   – helper: wali tanpa kelas binaan => 403
 */
class SiswaPolicy
{
    /**
     * Operator boleh semua hal.
     * Mengembalikan true = izinkan, false = tolak, null = lanjut ke method.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isOperator()) {
            return true;
        }
        return null;
    }

    /**
     * Apakah user boleh mengakses (baca/tulis) data siswa ini?
     *
     * - Wali Kelas: hanya boleh ke siswa di kelasnya sendiri.
     * - Guru Mapel: boleh ke semua siswa (filter mapel diurus canAccessMapel).
     */
    public function accessSiswa(User $user, Siswa $siswa): bool
    {
        if ($user->isWaliKelas()) {
            $kelas = $user->getKelasBinaan();
            return $kelas && $siswa->id_kelas === $kelas->id_kelas;
        }

        if ($user->isGuruMapel()) {
            return true; // dibatasi lebih lanjut oleh canAccessMapel
        }

        return false;
    }

    /**
     * Apakah user boleh menulis nilai / capaian untuk mapel dengan id ini?
     *
     * - Guru Mapel: hanya mapel yang diampu (id_guru == id_guru user).
     * - Wali Kelas & Operator: boleh semua mapel.
     */
    public function accessMapel(User $user, int $mapelId): bool
    {
        if ($user->isGuruMapel()) {
            $ampuan = $user->getMataPelajaranAmpuan();
            // Jika guru tidak mengampu mapel manapun => tolak semua
            if ($ampuan->isEmpty()) {
                return false;
            }
            return $ampuan->contains('id_mapel', $mapelId);
        }

        return true;
    }
}
