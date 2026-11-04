<?php

namespace Tests\Feature;

use App\Models\CatatanWali;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Masalah 4: Status kenaikan "Tinggal" berubah jadi "Naik" (audit B12).
 * Jika status_kenaikan tidak dikirim dalam request update, nilai yang ada (misal: "Tinggal")
 * harus tetap dipertahankan, bukan ditimpa menjadi default "Naik".
 */
class CatatanStatusKenaikanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    public function test_status_tinggal_preserved_when_field_not_sent(): void
    {
        $operator = User::where('username', 'operator')->first();
        $kelas    = Kelas::first();

        $siswa = Siswa::create([
            'nis' => 'TEST_CATATAN_KENAIKAN',
            'nama' => 'Siswa Status Tinggal',
            'jenis_kelamin' => 'L',
            'id_kelas' => $kelas->id_kelas,
            'status_siswa' => 'Aktif',
        ]);

        // 1. Simpan pertama kali dengan status 'Tinggal'
        $response1 = $this->actingAs($operator)->post(route('operator.catatan.store'), [
            'semester_id' => 2,
            'catatan' => [
                $siswa->id_siswa => [
                    'catatan_wali' => 'Perlu bimbingan intensif',
                    'status_kenaikan' => 'Tinggal',
                ],
            ],
        ]);
        $response1->assertSessionHas('success');

        $this->assertDatabaseHas('catatan_wali', [
            'id_siswa' => $siswa->id_siswa,
            'id_semester' => 2,
            'status_kenaikan' => 'Tinggal',
        ]);

        // 2. Simpan kedua kali TANPA field status_kenaikan (hanya update catatan sikap)
        $response2 = $this->actingAs($operator)->post(route('operator.catatan.store'), [
            'semester_id' => 2,
            'catatan' => [
                $siswa->id_siswa => [
                    'catatan_wali' => 'Catatan diperbarui, tetap tinggal',
                    'sikap_spiritual' => 'Baik',
                    // status_kenaikan sengaja TIDAK dikirim
                ],
            ],
        ]);
        $response2->assertSessionHas('success');

        // Pastikan di database tetap 'Tinggal', tidak berubah menjadi 'Naik'
        $this->assertDatabaseHas('catatan_wali', [
            'id_siswa' => $siswa->id_siswa,
            'id_semester' => 2,
            'catatan_wali' => 'Catatan diperbarui, tetap tinggal',
            'status_kenaikan' => 'Tinggal',
        ]);

        // 3. Simpan ketiga kali DENGAN field 'Naik' -> harus berubah jadi 'Naik'
        $response3 = $this->actingAs($operator)->post(route('operator.catatan.store'), [
            'semester_id' => 2,
            'catatan' => [
                $siswa->id_siswa => [
                    'status_kenaikan' => 'Naik',
                ],
            ],
        ]);
        $response3->assertSessionHas('success');

        $this->assertDatabaseHas('catatan_wali', [
            'id_siswa' => $siswa->id_siswa,
            'id_semester' => 2,
            'status_kenaikan' => 'Naik',
        ]);
    }
}
