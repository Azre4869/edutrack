<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePresensiValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Test operator tidak diperbolehkan menyimpan presensi (read-only) -> 403 Forbidden.
     */
    public function test_operator_cannot_store_presensi(): void
    {
        $operator = User::where('username', 'operator')->first();

        $response = $this->actingAs($operator)->post(route('operator.presensi-kelas.store'), [
            'tanggal' => '2026-11-03',
            'semester_id' => 1,
            'status' => [],
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test validasi required pada store presensi kelas.
     */
    public function test_store_presensi_validation_required_fields(): void
    {
        $wali = User::where('username', 'walikelas')->first();

        $response = $this->actingAs($wali)->post(route('wali.presensi-kelas.store'), [
            // Kosong tanpa tanggal, semester_id, status
        ]);

        $response->assertSessionHasErrors(['tanggal', 'semester_id', 'status']);
    }

    /**
     * Test status presensi harus berupa salah satu dari Hadir, Sakit, Izin, Alpha.
     */
    public function test_store_presensi_validates_status_values(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $siswa = Siswa::firstOrCreate(
            ['nis' => 'TEST_PRESENSI_01'],
            ['nama' => 'Siswa Test Presensi', 'jenis_kelamin' => 'L', 'id_kelas' => 1, 'status_siswa' => 'Aktif']
        );

        $response = $this->actingAs($wali)->post(route('wali.presensi-kelas.store'), [
            'tanggal' => '2026-11-03',
            'semester_id' => 1,
            'status' => [
                $siswa->id_siswa => 'Bolo-bolo', // Invalid status
            ],
        ]);

        $response->assertSessionHasErrors(['status.' . $siswa->id_siswa]);
    }
}
