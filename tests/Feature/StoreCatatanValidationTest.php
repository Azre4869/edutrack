<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCatatanValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Test validasi field required pada store catatan.
     */
    public function test_store_catatan_validation_required_fields(): void
    {
        $operator = User::where('username', 'operator')->first();

        $response = $this->actingAs($operator)->post(route('operator.catatan.store'), [
            // Kosong tanpa semester_id dan catatan
        ]);

        $response->assertSessionHasErrors(['semester_id', 'catatan']);
    }

    /**
     * Test validasi status kenaikan kelas (hanya Naik atau Tinggal).
     */
    public function test_store_catatan_validates_status_kenaikan(): void
    {
        $operator = User::where('username', 'operator')->first();
        $siswa = Siswa::first();

        $response = $this->actingAs($operator)->post(route('operator.catatan.store'), [
            'semester_id' => 1,
            'catatan' => [
                $siswa->id_siswa => [
                    'status_kenaikan' => 'LulusBersyarat', // Invalid value
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['catatan.' . $siswa->id_siswa . '.status_kenaikan']);
    }
}
