<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Test persentase kehadiran di dashboard operator dihitung benar dengan status 'Hadir'.
     */
    public function test_dashboard_and_laporan_attendance_percentage_calculated_correctly(): void
    {
        $operator = User::where('username', 'operator')->first();

        $siswa = Siswa::create([
            'nis' => 'TEST_ABSEN_1',
            'nama' => 'Siswa Absen 1',
            'jenis_kelamin' => 'L',
            'id_kelas' => 1,
            'status_siswa' => 'Aktif',
        ]);

        // Masukkan 3 baris Hadir dan 1 baris Izin (total 4 baris, persentase = 3/4 * 100 = 75%)
        Absensi::create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => '2026-11-01',
            'status' => 'Hadir',
            'id_semester' => 1,
        ]);
        Absensi::create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => '2026-11-02',
            'status' => 'Hadir',
            'id_semester' => 1,
        ]);
        Absensi::create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => '2026-11-03',
            'status' => 'Hadir',
            'id_semester' => 1,
        ]);
        Absensi::create([
            'id_siswa' => $siswa->id_siswa,
            'tanggal' => '2026-11-04',
            'status' => 'Izin',
            'id_semester' => 1,
        ]);

        // Cek Dashboard Operator
        $response = $this->actingAs($operator)->get(route('operator.dashboard'));
        $response->assertStatus(200);
        $response->assertViewHas('kehadiranHariIni', 75);

        // Cek Halaman Laporan
        $responseLaporan = $this->actingAs($operator)->get(route('operator.laporan'));
        $responseLaporan->assertStatus(200);
        $responseLaporan->assertViewHas('kehadiranPct', 75);
    }
}
