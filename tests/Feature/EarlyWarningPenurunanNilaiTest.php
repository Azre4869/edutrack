<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Masalah 2: Persen penurunan nilai selalu 12% (audit B4).
 * Kriteria 1 (nilai turun) harus dihitung secara riil dari perbandingan rata-rata
 * nilai semester ini terhadap semester sebelumnya. Siswa tanpa semester sebelumnya
 * menampilkan "Tidak ada data pembanding".
 */
class EarlyWarningPenurunanNilaiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    public function test_penurunan_nilai_calculated_correctly_from_real_data(): void
    {
        $operator = User::where('username', 'operator')->first();
        $kelas    = Kelas::first();
        $mapel1   = MataPelajaran::find(1);
        $mapel2   = MataPelajaran::find(2);

        // Pastikan semester 1 dan semester 2 ada
        $sm1 = Semester::find(1);
        $sm2 = Semester::find(2);

        // Siswa A: Semester 1 rata-rata 100, Semester 2 rata-rata 80 -> turun 20%
        $siswaA = Siswa::create([
            'nis' => 'TEST_NILAI_TURUN_A',
            'nama' => 'Siswa Nilai Turun Riil',
            'jenis_kelamin' => 'L',
            'id_kelas' => $kelas->id_kelas,
            'status_siswa' => 'Aktif',
        ]);

        // Semester 1: rata-rata 100.00
        Nilai::create([
            'id_siswa' => $siswaA->id_siswa,
            'id_mapel' => $mapel1->id_mapel,
            'id_semester' => $sm1->id_semester,
            'nilai_akhir' => 100.00,
        ]);

        // Semester 2: rata-rata 80.00 -> turun = (100 - 80) / 100 * 100% = 20.0%
        // Nilai di bawah KKM agar masuk daftar early warning
        Nilai::create([
            'id_siswa' => $siswaA->id_siswa,
            'id_mapel' => $mapel1->id_mapel,
            'id_semester' => $sm2->id_semester,
            'nilai_akhir' => 70.00, // < 75 KKM
        ]);
        Nilai::create([
            'id_siswa' => $siswaA->id_siswa,
            'id_mapel' => $mapel2->id_mapel,
            'id_semester' => $sm2->id_semester,
            'nilai_akhir' => 90.00,
        ]);
        // Rata-rata semester 2 = (70 + 90) / 2 = 80.0. Turun = (100 - 80) / 100 = 20.0%

        // Siswa B: Hanya ada di semester 2 (tanpa semester 1)
        $siswaB = Siswa::create([
            'nis' => 'TEST_NILAI_BARU_B',
            'nama' => 'Siswa Baru Tanpa Pembanding',
            'jenis_kelamin' => 'P',
            'id_kelas' => $kelas->id_kelas,
            'status_siswa' => 'Aktif',
        ]);
        Nilai::create([
            'id_siswa' => $siswaB->id_siswa,
            'id_mapel' => $mapel1->id_mapel,
            'id_semester' => $sm2->id_semester,
            'nilai_akhir' => 65.00, // < 75 KKM
        ]);

        $response = $this->actingAs($operator)->get(route('operator.early-warning', [
            'kelas_id'    => $kelas->id_kelas,
            'semester_id' => $sm2->id_semester,
        ]));

        $response->assertStatus(200);

        $siswaList = collect($response->viewData('siswaEarlyWarning'));

        $itemA = $siswaList->firstWhere('id_siswa', $siswaA->id_siswa);
        $this->assertNotNull($itemA);
        $this->assertEquals('20,0%', $itemA['penurunan_nilai'], 'Penurunan nilai siswa A harus 20,0% bukan 12%');

        $itemB = $siswaList->firstWhere('id_siswa', $siswaB->id_siswa);
        $this->assertNotNull($itemB);
        $this->assertEquals('Tidak ada data pembanding', $itemB['penurunan_nilai'], 'Siswa B tanpa riwayat harus menampilkan tidak ada data pembanding');
    }
}
