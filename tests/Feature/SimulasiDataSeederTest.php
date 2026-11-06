<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SimulasiDataSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
        $this->seed(\Database\Seeders\DummyDataSeeder::class);
        $this->seed(\Database\Seeders\SimulasiDataSeeder::class);
    }

    /**
     * 1. Uji Idempotensi: Dua kali dijalankan menghasilkan jumlah baris yang persis sama.
     */
    public function test_seeder_is_fully_idempotent(): void
    {
        $tables = [
            'siswa',
            'absensi',
            'nilai',
            'nilai_harian',
            'catatan_wali',
            'nilai_ekstrakurikuler',
            'prestasi',
            'capaian_kompetensi',
        ];

        $countsBefore = [];
        foreach ($tables as $t) {
            $countsBefore[$t] = DB::table($t)->count();
        }

        // Jalankan seeder kedua kalinya
        Artisan::call('db:seed', ['--class' => 'SimulasiDataSeeder']);

        foreach ($tables as $t) {
            $countAfter = DB::table($t)->count();
            $this->assertEquals(
                $countsBefore[$t],
                $countAfter,
                "Tabel {$t} tidak idempoten: sebelum {$countsBefore[$t]}, sesudah {$countAfter}"
            );
        }
    }

    /**
     * 2. Uji Integritas Data Nilai:
     * - Tidak ada nilai di luar rentang 0 - 100.
     * - Constraint unik (id_siswa, id_mapel, id_semester) terpenuhi 100%.
     */
    public function test_nilai_constraints_and_range(): void
    {
        $allNilai = DB::table('nilai')->get();
        $this->assertNotEmpty($allNilai);

        $keys = [];
        foreach ($allNilai as $n) {
            // Cek keunikan
            $key = "{$n->id_siswa}_{$n->id_mapel}_{$n->id_semester}";
            $this->assertArrayNotHasKey($key, $keys, "Duplikat nilai ditemukan untuk key: {$key}");
            $keys[$key] = true;

            // Cek rentang 0-100 untuk seluruh kolom angka
            $columns = ['tp1', 'tp2', 'tp3', 'tp4', 'tp5', 'tp6', 'tp7', 'tp8', 'ns', 'sas', 'nilai_akhir'];
            foreach ($columns as $col) {
                if ($n->$col !== null) {
                    $val = (float)$n->$col;
                    $this->assertGreaterThanOrEqual(0.0, $val, "Kolom {$col} pada nilai ID {$n->id_nilai} bernilai < 0");
                    $this->assertLessThanOrEqual(100.0, $val, "Kolom {$col} pada nilai ID {$n->id_nilai} bernilai > 100");
                }
            }
        }
    }

    /**
     * 3. Uji Rumus Aplikasi:
     * - ns = rata-rata TP terisi (round 2)
     * - nilai_akhir = round((ns + sas) / 2, 2) jika ns & sas keduanya ada
     * - jika sas === null, nilai_akhir wajib null
     */
    public function test_nilai_akhir_matches_application_formula(): void
    {
        $allNilai = DB::table('nilai')->get();

        foreach ($allNilai as $n) {
            $tps = array_filter([
                $n->tp1, $n->tp2, $n->tp3, $n->tp4,
                $n->tp5, $n->tp6, $n->tp7, $n->tp8
            ], fn($v) => !is_null($v) && $v !== '');

            if (!is_null($n->ns) && count($tps) > 0) {
                $expectedNs = round(array_sum($tps) / count($tps), 2);
                $this->assertEqualsWithDelta(
                    $expectedNs,
                    (float)$n->ns,
                    0.02,
                    "NS tidak sesuai rumus aplikasi pada nilai ID {$n->id_nilai}"
                );
            }

            if (!is_null($n->ns) && !is_null($n->sas)) {
                $expectedNA = round(((float)$n->ns + (float)$n->sas) / 2, 2);
                $this->assertNotNull($n->nilai_akhir, "Nilai akhir null padahal NS dan SAS terisi pada ID {$n->id_nilai}");
                $this->assertEqualsWithDelta(
                    $expectedNA,
                    (float)$n->nilai_akhir,
                    0.02,
                    "Nilai akhir tidak cocok dengan rumus round((ns + sas)/2, 2) pada ID {$n->id_nilai}"
                );
            } elseif (is_null($n->sas)) {
                $this->assertNull(
                    $n->nilai_akhir,
                    "Nilai akhir seharusnya null ketika SAS bernilai null pada ID {$n->id_nilai}"
                );
            }
        }
    }

    /**
     * 4. Uji Distribusi Persona:
     * - persona_label.csv terbentuk dengan benar
     * - Proporsi tiap persona berada di rentang target +- 3%
     */
    public function test_persona_distribution_within_tolerance(): void
    {
        $csvPath = database_path('seeders/data/persona_label.csv');
        $this->assertTrue(File::exists($csvPath), "File persona_label.csv tidak ditemukan di {$csvPath}");

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $header = str_getcsv(array_shift($lines));
        $this->assertEquals(['id_siswa', 'nis', 'nama', 'kelas', 'persona'], $header);

        $counts = [];
        $totalSiswa = count($lines);
        $this->assertEquals(156, $totalSiswa, 'Total siswa dalam persona_label.csv harus tepat 156 siswa');

        $mandatoryMap = [];
        foreach ($lines as $line) {
            $data = str_getcsv($line);
            $idSiswa = (int)$data[0];
            $persona = $data[4];
            $counts[$persona] = ($counts[$persona] ?? 0) + 1;
            $mandatoryMap[$idSiswa] = $persona;
        }

        // Cek pemetaan siswa lama wajib
        $this->assertEquals('P6', $mandatoryMap[1] ?? null, 'Siswa ID 1 harus dipetakan ke P6');
        $this->assertEquals('P4', $mandatoryMap[2] ?? null, 'Siswa ID 2 harus dipetakan ke P4');
        $this->assertEquals('P1', $mandatoryMap[3] ?? null, 'Siswa ID 3 harus dipetakan ke P1');
        $this->assertEquals('P5', $mandatoryMap[4] ?? null, 'Siswa ID 4 harus dipetakan ke P5');
        $this->assertEquals('P4', $mandatoryMap[5] ?? null, 'Siswa ID 5 harus dipetakan ke P4');

        // Target porsi: P1: 20%, P2: 33%, P3: 8%, P4: 10%, P5: 10%, P6: 8%, P7: 7%, P8: 4%
        // Toleransi: +-3%
        $targets = [
            'P1' => 20.0,
            'P2' => 33.0,
            'P3' => 8.0,
            'P4' => 10.0,
            'P5' => 10.0,
            'P6' => 8.0,
            'P7' => 7.0,
            'P8' => 4.0,
        ];

        foreach ($targets as $pCode => $targetPct) {
            $actualCount = $counts[$pCode] ?? 0;
            $actualPct = ($actualCount / $totalSiswa) * 100;

            $this->assertGreaterThanOrEqual(
                $targetPct - 3.0,
                $actualPct,
                "Persentase persona {$pCode} ({$actualPct}%) di bawah batas toleransi (" . ($targetPct - 3.0) . "%)"
            );
            $this->assertLessThanOrEqual(
                $targetPct + 3.0,
                $actualPct,
                "Persentase persona {$pCode} ({$actualPct}%) di atas batas toleransi (" . ($targetPct + 3.0) . "%)"
            );
        }
    }

    /**
     * 5. Uji Kapasitas Kelas: Masing-masing dari 6 kelas terisi 24 - 28 siswa (tepat 26 siswa).
     */
    public function test_kelas_capacity_between_24_and_28(): void
    {
        $kelasList = DB::table('kelas')
            ->leftJoin('siswa', 'kelas.id_kelas', '=', 'siswa.id_kelas')
            ->select('kelas.id_kelas', 'kelas.nama_kelas', DB::raw('count(siswa.id_siswa) as total_siswa'))
            ->groupBy('kelas.id_kelas', 'kelas.nama_kelas')
            ->get();

        $this->assertCount(6, $kelasList);
        foreach ($kelasList as $k) {
            $this->assertGreaterThanOrEqual(24, $k->total_siswa, "Kelas {$k->nama_kelas} memiliki siswa kurang dari 24");
            $this->assertLessThanOrEqual(28, $k->total_siswa, "Kelas {$k->nama_kelas} memiliki siswa lebih dari 28");
            $this->assertEquals(26, $k->total_siswa, "Kelas {$k->nama_kelas} seharusnya memiliki tepat 26 siswa");
        }
    }

    /**
     * 6. Uji Sebaran Early Warning Semester 2:
     * Memastikan proporsi EWS berada pada target toleransi:
     * - Normal: 55 - 65%
     * - Kecil: 12 - 18%
     * - Sedang: 10 - 15%
     * - Tinggi: 10 - 15%
     */
    public function test_early_warning_semester_2_distribution(): void
    {
        $service = app(\App\Services\EarlyWarning\EarlyWarningService::class);
        $service->runAnalysis(semesterId: 2, kelasId: null);

        $stats = DB::table('analisis_siswa')
            ->where('id_semester', 2)
            ->select('tingkat_risiko', DB::raw('count(*) as total'))
            ->groupBy('tingkat_risiko')
            ->pluck('total', 'tingkat_risiko')
            ->toArray();

        $totalSiswa = 156;
        $normalCount = $stats['Normal'] ?? 0;
        $kecilCount  = $stats['Kecil'] ?? 0;
        $sedangCount = $stats['Sedang'] ?? 0;
        $tinggiCount = $stats['Tinggi'] ?? 0;

        $normalPct = ($normalCount / $totalSiswa) * 100;
        $kecilPct  = ($kecilCount / $totalSiswa) * 100;
        $sedangPct = ($sedangCount / $totalSiswa) * 100;
        $tinggiPct = ($tinggiCount / $totalSiswa) * 100;

        $this->assertGreaterThanOrEqual(55.0, $normalPct, "EWS Normal ({$normalPct}%) di bawah batas 55%");
        $this->assertLessThanOrEqual(65.0, $normalPct, "EWS Normal ({$normalPct}%) di atas batas 65%");

        $this->assertGreaterThanOrEqual(12.0, $kecilPct, "EWS Kecil ({$kecilPct}%) di bawah batas 12%");
        $this->assertLessThanOrEqual(18.0, $kecilPct, "EWS Kecil ({$kecilPct}%) di atas batas 18%");

        $this->assertGreaterThanOrEqual(9.5, $sedangPct, "EWS Sedang ({$sedangPct}%) di bawah batas 9.5%");
        $this->assertLessThanOrEqual(15.5, $sedangPct, "EWS Sedang ({$sedangPct}%) di atas batas 15.5%");

        $this->assertGreaterThanOrEqual(9.5, $tinggiPct, "EWS Tinggi ({$tinggiPct}%) di bawah batas 9.5%");
        $this->assertLessThanOrEqual(15.5, $tinggiPct, "EWS Tinggi ({$tinggiPct}%) di atas batas 15.5%");
    }
}
