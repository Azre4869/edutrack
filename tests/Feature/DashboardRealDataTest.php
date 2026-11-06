<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\AnalisisSiswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRealDataTest extends TestCase
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
     * Operator melihat total agregat seluruh sekolah sesuai data seed.
     */
    public function test_operator_dashboard_displays_correct_school_wide_metrics(): void
    {
        $operator = User::where('username', 'operator')->first();

        $totalSiswaDB = Siswa::count();
        $perluPerhatianDB = AnalisisSiswa::where('status', 'Perlu Perhatian')->count();

        $response = $this->actingAs($operator)->get(route('operator.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('totalSiswa', $totalSiswaDB);
        $response->assertViewHas('perluPerhatian', $perluPerhatianDB);

        // Nilai rata-rata sekolah
        $avgNilaiDB = round(Nilai::avg('nilai_akhir'), 1);
        $response->assertViewHas('rataRataNilai', $avgNilaiDB);

        // Metrik perkembangan nilai riil
        $response->assertViewHas('tuntasCount');
        $response->assertViewHas('belumTuntasCount');
        $response->assertViewHas('persentaseKetuntasan');
        $this->assertEquals($response->viewData('totalBernilai'), $response->viewData('tuntasCount') + $response->viewData('belumTuntasCount'));
    }

    /**
     * Wali kelas hanya melihat data kelas binaannya.
     */
    public function test_wali_kelas_dashboard_only_shows_binaan_class_metrics(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $kelasBinaan = $wali->getKelasBinaan();
        $this->assertNotNull($kelasBinaan, 'User walikelas harus memiliki kelas binaan.');

        $siswaKelasIds = Siswa::where('id_kelas', $kelasBinaan->id_kelas)->pluck('id_siswa');
        $totalSiswaKelas = $siswaKelasIds->count();
        $perluPerhatianKelas = AnalisisSiswa::whereIn('id_siswa', $siswaKelasIds)
            ->where('status', 'Perlu Perhatian')
            ->count();

        $avgNilaiKelas = round(Nilai::whereIn('id_siswa', $siswaKelasIds)->avg('nilai_akhir'), 1);

        $response = $this->actingAs($wali)->get(route('wali.dashboard'));

        $response->assertStatus(200);
        $response->assertViewHas('totalSiswa', $totalSiswaKelas);
        $response->assertViewHas('perluPerhatian', $perluPerhatianKelas);
        $response->assertViewHas('rataRataNilai', $avgNilaiKelas);
        $response->assertViewHas('kelasBinaan');
        $response->assertViewHas('tuntasCount');
        $response->assertViewHas('belumTuntasCount');
        $response->assertViewHas('persentaseKetuntasan');
        $this->assertEquals($response->viewData('totalBernilai'), $response->viewData('tuntasCount') + $response->viewData('belumTuntasCount'));

        // Pastikan siswa dari kelas lain tidak muncul di daftar perhatian wali
        $siswaLain = Siswa::where('id_kelas', '!=', $kelasBinaan->id_kelas)->first();
        if ($siswaLain) {
            $siswaPerhatianView = $response->viewData('siswaPerhatian');
            $namesInView = array_column($siswaPerhatianView, 'nama');
            $this->assertNotContains($siswaLain->nama, $namesInView);
        }
    }

    /**
     * Dashboard menampilkan label 'Data contoh' pada bagian grafik yang belum real-time.
     */
    public function test_dashboard_displays_example_data_badge_for_static_charts(): void
    {
        $operator = User::where('username', 'operator')->first();
        $response = $this->actingAs($operator)->get(route('operator.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Data contoh');

        $wali = User::where('username', 'walikelas')->first();
        $responseWali = $this->actingAs($wali)->get(route('wali.dashboard'));
        $responseWali->assertStatus(200);
        $responseWali->assertSee('Data contoh');
    }
}
