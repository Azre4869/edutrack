<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiswaStatusAnakValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Test validasi dan penyimpanan status_anak sebagai dropdown enum.
     */
    public function test_store_siswa_with_status_anak_select(): void
    {
        $operator = User::where('username', 'operator')->first();
        $kelas = Kelas::first();

        // 1. Simpan dengan status_anak valid
        $response = $this->actingAs($operator)->post(route('operator.siswa.store'), [
            'nis' => 'TESTSTATUS1',
            'nama' => 'Siswa Status Anak',
            'jenis_kelamin' => 'L',
            'id_kelas' => $kelas->id_kelas,
            'status_anak' => 'Anak Kandung',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('siswa', [
            'nis' => 'TESTSTATUS1',
            'status_anak' => 'Anak Kandung',
        ]);

        // 2. Simpan dengan status_anak tidak valid
        $invalidResponse = $this->actingAs($operator)->post(route('operator.siswa.store'), [
            'nis' => 'TESTSTATUS2',
            'nama' => 'Siswa Status Invalid',
            'jenis_kelamin' => 'P',
            'id_kelas' => $kelas->id_kelas,
            'status_anak' => 'StatusAsalAsalan',
        ]);

        $invalidResponse->assertSessionHasErrors(['status_anak']);
    }
}
