<?php

namespace Tests\Feature;

use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreNilaiValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Test validasi input nilai (semester, mapel, range nilai).
     */
    public function test_store_nilai_validation_rules(): void
    {
        $operator = User::where('username', 'operator')->first();

        // Data tidak lengkap (missing semester & mapel)
        $response = $this->actingAs($operator)->post(route('operator.nilai.store'), [
            'nilai' => [],
        ]);

        $response->assertSessionHasErrors(['semester_id', 'mapel_id', 'nilai']);
    }

    /**
     * Test NS dan nilai akhir dihitung di server, bukan dari input client.
     */
    public function test_ns_and_nilai_akhir_are_calculated_on_server(): void
    {
        $operator = User::where('username', 'operator')->first();
        $siswa = Siswa::firstOrCreate(
            ['nis' => 'TEST_NILAI_01'],
            ['nama' => 'Siswa Test Nilai', 'jenis_kelamin' => 'L', 'id_kelas' => 1, 'status_siswa' => 'Aktif']
        );
        $mapel = MataPelajaran::first();

        $response = $this->actingAs($operator)->post(route('operator.nilai.store'), [
            'semester_id' => 1,
            'mapel_id' => $mapel->id_mapel,
            'nilai' => [
                $siswa->id_siswa => [
                    'tp1' => 80,
                    'tp2' => 90,
                    'sas' => 70,
                    // Client mencoba mengirim NS palsu (99) dan Nilai Akhir palsu (100)
                    'ns' => 99,
                    'nilai_akhir' => 100,
                ],
            ],
        ]);

        $response->assertSessionHas('success');

        // NS harus (80 + 90) / 2 = 85.00
        // Nilai Akhir harus (85 + 70) / 2 = 77.50
        $this->assertDatabaseHas('nilai', [
            'id_siswa' => $siswa->id_siswa,
            'id_mapel' => $mapel->id_mapel,
            'ns' => 85.00,
            'nilai_akhir' => 77.50,
        ]);
    }
}
