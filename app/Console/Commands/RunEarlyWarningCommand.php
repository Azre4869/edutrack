<?php

namespace App\Console\Commands;

use App\Models\Semester;
use App\Services\EarlyWarning\EarlyWarningService;
use Illuminate\Console\Command;

class RunEarlyWarningCommand extends Command
{
    protected $signature = 'ews:run {--semester= : ID Semester (default: semester aktif)} {--kelas= : ID Kelas spesifik (opsional)}';
    protected $description = 'Jalankan analisis Early Warning System (EWS) untuk mendeteksi siswa berisiko secara terjadwal';

    public function handle(EarlyWarningService $service): int
    {
        $this->info('Memulai analisis Early Warning System (EWS)...');

        $semesterId = (int) ($this->option('semester') ?: 2);
        $kelasId = $this->option('kelas') ? (int) $this->option('kelas') : null;

        $semester = Semester::find($semesterId);
        if (!$semester) {
            $this->error("Semester dengan ID {$semesterId} tidak ditemukan.");
            return Command::FAILURE;
        }

        $this->line("Semester: {$semester->nama_semester} ({$semester->tahun_ajaran})");
        if ($kelasId) {
            $this->line("Target Kelas ID: {$kelasId}");
        } else {
            $this->line("Target: Seluruh Siswa Sekolah");
        }

        try {
            $log = $service->runAnalysis(
                semesterId: $semesterId,
                kelasId: $kelasId,
                pemicu: 'Terjadwal',
                userId: null
            );

            $this->info("Analisis selesai sukses.");
            $this->table(
                ['ID Log', 'Pemicu', 'Jumlah Siswa Diproses', 'Total Berisiko', 'Waktu Mulai', 'Waktu Selesai'],
                [[
                    $log->id_log,
                    $log->pemicu,
                    $log->jumlah_siswa_diproses,
                    $log->total_berisiko,
                    $log->waktu_jalan,
                    $log->waktu_selesai,
                ]]
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal menjalankan analisis: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
