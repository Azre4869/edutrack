<?php

namespace Tests\Feature;

use App\Models\Prestasi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePrestasiValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Test validasi required pada store prestasi.
     */
    public function test_store_prestasi_validation_required_fields(): void
    {
        $operator = User::where('username', 'operator')->first();

        $response = $this->actingAs($operator)->post(route('wali.prestasi.store'), [
            // Kosong tanpa semester_id dan prestasi
        ]);

        $response->assertSessionHasErrors(['semester_id', 'prestasi']);
    }

    /**
     * Test update prestasi tidak menghasilkan duplikat baris baru.
     */
    public function test_updating_prestasi_does_not_create_duplicates(): void
    {
        $operator = User::where('username', 'operator')->first();
        $siswa = Siswa::first();

        // 1. Simpan pertama kali
        $this->actingAs($operator)->post(route('wali.prestasi.store'), [
            'semester_id' => 1,
            'prestasi' => [
                $siswa->id_siswa => [
                    'nama_prestasi' => 'Juara 1 Lari',
                    'jenis' => 'Non-Akademik',
                    'keterangan' => 'Kabupaten',
                ],
            ],
        ])->assertSessionHas('success');

        $this->assertEquals(1, Prestasi::where('id_siswa', $siswa->id_siswa)->where('id_semester', 1)->count());

        // 2. Simpan kedua kali dengan nama prestasi berbeda (harus mengupdate, BUKAN menambah baris duplikat)
        $this->actingAs($operator)->post(route('wali.prestasi.store'), [
            'semester_id' => 1,
            'prestasi' => [
                $siswa->id_siswa => [
                    'nama_prestasi' => 'Juara 1 Renang',
                    'jenis' => 'Non-Akademik',
                    'keterangan' => 'Provinsi',
                ],
            ],
        ])->assertSessionHas('success');

        // Jumlah tetap 1, data terupdate
        $this->assertEquals(1, Prestasi::where('id_siswa', $siswa->id_siswa)->where('id_semester', 1)->count());
        $this->assertDatabaseHas('prestasi', [
            'id_siswa' => $siswa->id_siswa,
            'id_semester' => 1,
            'nama_prestasi' => 'Juara 1 Renang',
            'keterangan' => 'Provinsi',
        ]);
    }
}
