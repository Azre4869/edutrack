<?php

namespace App\Console\Commands;

use App\Models\Semester;
use App\Services\Ai\AiInsightService;
use Illuminate\Console\Command;

class RunAiAnalysisCommand extends Command
{
    protected $signature = 'ai:analisis {--semester= : ID Semester (default: 2)} {--kelas= : ID Kelas spesifik (opsional)} {--log= : ID Log EWS terkait (opsional)}';
    protected $description = 'Jalankan analisis AI (clustering, anomaly detection, predictive trend) untuk siswa EduTrack';

    public function handle(AiInsightService $service): int
    {
        $this->info('Memulai pemrosesan Analisis AI (Machine Learning Lokal)...');

        $semesterId = (int) ($this->option('semester') ?: 2);
        $kelasId = $this->option('kelas') ? (int) $this->option('kelas') : null;
        $idLog = $this->option('log') ? (int) $this->option('log') : null;

        $semester = Semester::find($semesterId);
        if (!$semester) {
            $this->error("Semester dengan ID {$semesterId} tidak ditemukan.");
            return Command::FAILURE;
        }

        $this->line("Semester: {$semester->nama_semester} ({$semester->tahun_ajaran})");
        if ($kelasId) {
            $this->line("Target Kelas ID: {$kelasId}");
        } else {
            $this->line("Target: Seluruh Siswa Kohor");
        }

        $result = $service->runAnalysis(
            semesterId: $semesterId,
            idLog: $idLog,
            kelasId: $kelasId
        );

        if (!$result['success']) {
            $this->error("Analisis AI Gagal: " . $result['message']);
            return Command::FAILURE;
        }

        $run = $result['run'];
        $this->info("Analisis AI selesai sukses.");
        $this->table(
            ['ID Run', 'Status', 'Total Siswa', 'Total Anomali', 'Model Version', 'Waktu Selesai'],
            [[
                $run->id,
                $run->status,
                $run->metrik['total_diproses'] ?? 0,
                $run->metrik['total_anomali'] ?? 0,
                $run->model_version,
                $run->dibuat_pada,
            ]]
        );

        return Command::SUCCESS;
    }
}
