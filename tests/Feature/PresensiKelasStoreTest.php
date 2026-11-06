<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PresensiKelasStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
        $this->seed(\Database\Seeders\SemesterAktifSeeder::class);
    }

    public function test_wali_kelas_can_store_daily_attendance_without_mapel(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $kelasBinaan = $wali->requireKelasBinaan();

        $siswa = Siswa::create([
            'nis' => 'TEST_PRES_DAILY_01',
            'nama' => 'Siswa Absen Harian',
            'jenis_kelamin' => 'L',
            'id_kelas' => $kelasBinaan->id_kelas,
            'status_siswa' => 'Aktif',
        ]);

        $tanggal = '2026-11-06';
        $response = $this->actingAs($wali)->post(route('wali.presensi-kelas.store'), [
            'tanggal'     => $tanggal,
            'semester_id' => 2,
            'kelas_id'    => $kelasBinaan->id_kelas,
            'status'      => [
                $siswa->id_siswa => 'Sakit',
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('absensi', [
            'id_siswa'    => $siswa->id_siswa,
            'id_semester' => 2,
            'tanggal'     => $tanggal,
            'status'      => 'Sakit',
        ]);

        // Verifikasi bahwa kolom id_mapel memang sudah tidak ada
        $record = Absensi::where('id_siswa', $siswa->id_siswa)
            ->where('tanggal', $tanggal)
            ->first();

        $this->assertNotNull($record);
        $this->assertFalse(array_key_exists('id_mapel', $record->getAttributes()));
    }
}
