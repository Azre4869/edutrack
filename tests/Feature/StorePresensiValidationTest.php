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
     * Test validasi required pada store presensi kelas.
     */
    public function test_store_presensi_validation_required_fields(): void
    {
        $operator = User::where('username', 'operator')->first();

        $response = $this->actingAs($operator)->post(route('operator.presensi-kelas.store'), [
            // Kosong tanpa tanggal, semester_id, status
        ]);

        $response->assertSessionHasErrors(['tanggal', 'semester_id', 'status']);
    }

    /**
     * Test status presensi harus berupa salah satu dari Hadir, Sakit, Izin, Alpha.
     */
    public function test_store_presensi_validates_status_values(): void
    {
        $operator = User::where('username', 'operator')->first();
        $siswa = Siswa::first();

        $response = $this->actingAs($operator)->post(route('operator.presensi-kelas.store'), [
            'tanggal' => '2026-11-03',
            'semester_id' => 1,
            'status' => [
                $siswa->id_siswa => 'Bolo-bolo', // Invalid status
            ],
        ]);

        $response->assertSessionHasErrors(['status.' . $siswa->id_siswa]);
    }
}
