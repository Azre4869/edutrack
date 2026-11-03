<?php

namespace App\Console\Commands;

use App\Models\Nilai;
use Illuminate\Console\Command;

class CleanGhostGrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grades:clean-ghost {--dry-run : Jalankan simulasi tanpa menghapus data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan baris nilai_akhir = 85.00 palsu yang TP dan SAS-nya kosong';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Memeriksa baris nilai_akhir = 85.00 palsu (TP dan SAS kosong)...');

        $ghostQuery = Nilai::where('nilai_akhir', 85.00)
            ->where(function ($q) {
                for ($i = 1; $i <= 8; $i++) {
                    $q->where(function ($sub) use ($i) {
                        $sub->whereNull("tp{$i}")
                            ->orWhere("tp{$i}", '')
                            ->orWhere("tp{$i}", 0);
                    });
                }
            })
            ->where(function ($q) {
                $q->whereNull('sas')
                  ->orWhere('sas', '')
                  ->orWhere('sas', 0);
            });

        $count = $ghostQuery->count();

        if ($count === 0) {
            $this->info('Tidak ditemukan baris nilai_akhir = 85.00 palsu.');
            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->warn("[DRY-RUN] Ditemukan {$count} baris nilai palsu (85.00) dengan TP dan SAS kosong.");
            $this->line('Jalankan perintah tanpa opsi --dry-run untuk melakukan pembersihan.');
            return self::SUCCESS;
        }

        $ghostQuery->get()->each(function ($nilai) {
            // Hapus relasi capaian kompetensi jika ada
            if ($nilai->capaian) {
                $nilai->capaian->delete();
            }
            $nilai->delete();
        });

        $this->info("Berhasil membersihkan {$count} baris nilai_akhir = 85.00 palsu dari database.");

        return self::SUCCESS;
    }
}
