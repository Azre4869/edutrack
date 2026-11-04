<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanRealDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
        $this->seed(\Database\Seeders\DummyDataSeeder::class);
    }

    /**
     * Halaman laporan menampilkan metrik agregat riil dari database.
     */
    public function test_laporan_displays_real_aggregated_metrics(): void
    {
        $operator = User::where('username', 'operator')->first();

        $expectedTotalSiswa = Siswa::count();
        $expectedTotalGuru = Guru::count();
        $expectedAvgNilai = round(Nilai::avg('nilai_akhir'), 1);

        $response = $this->actingAs($operator)->get(route('operator.laporan'));

        $response->assertStatus(200);
        $response->assertViewHas('totalSiswa', $expectedTotalSiswa);
        $response->assertViewHas('jumlahGuru', $expectedTotalGuru);
        $response->assertViewHas('rataRataNilai', $expectedAvgNilai);
    }

    /**
     * Tabel 10 siswa dengan nilai terendah diambil dari data riil tabel nilai.
     */
    public function test_laporan_displays_lowest_scoring_students_from_database(): void
    {
        $operator = User::where('username', 'operator')->first();

        $response = $this->actingAs($operator)->get(route('operator.laporan'));

        $response->assertStatus(200);
        $response->assertViewHas('siswaTerendah');

        $siswaTerendah = $response->viewData('siswaTerendah');
        $this->assertIsArray($siswaTerendah);

        // Jika ada nilai di database, pastikan siswa dengan nilai terendah ada di daftar
        $lowestNilai = Nilai::with('siswa')->orderBy('nilai_akhir', 'asc')->first();
        if ($lowestNilai && $lowestNilai->siswa) {
            $names = array_column($siswaTerendah, 'nama');
            $this->assertContains($lowestNilai->siswa->nama, $names);
        }
    }

    /**
     * Halaman laporan menampilkan label 'Data contoh' pada bagian grafik yang masih mock.
     */
    public function test_laporan_displays_example_data_badge_for_mock_charts(): void
    {
        $operator = User::where('username', 'operator')->first();
        $response = $this->actingAs($operator)->get(route('operator.laporan'));

        $response->assertStatus(200);
        $response->assertSee('Data contoh');
    }
}
