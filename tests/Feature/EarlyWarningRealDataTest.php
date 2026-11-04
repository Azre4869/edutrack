<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\AnalisisKriteria;
use App\Models\AnalisisSiswa;
use App\Models\Kelas;
use App\Models\KriteriaAnalisis;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EarlyWarningRealDataTest extends TestCase
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
     * Wali kelas hanya melihat siswa dari kelas binaannya sendiri di halaman Early Warning.
     */
    public function test_wali_kelas_only_sees_early_warning_for_own_class_students(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $kelasBinaan = $wali->getKelasBinaan();
        $this->assertNotNull($kelasBinaan);

        $response = $this->actingAs($wali)->get(route('wali.early-warning'));

        $response->assertStatus(200);

        $siswaList = $response->viewData('siswaEarlyWarning');
        $this->assertNotEmpty($siswaList);

        // Pastikan semua siswa yang muncul di daftar memang berada di kelas binaan wali
        foreach ($siswaList as $item) {
            $siswa = Siswa::find($item['id_siswa']);
            $this->assertEquals($kelasBinaan->id_kelas, $siswa->id_kelas, "Siswa {$siswa->nama} bukan dari kelas {$kelasBinaan->nama_kelas}");
        }

        // Siswa dari kelas lain tidak boleh muncul
        $siswaLain = Siswa::where('id_kelas', '!=', $kelasBinaan->id_kelas)->first();
        if ($siswaLain) {
            $idsInView = array_column($siswaList, 'id_siswa');
            $this->assertNotContains($siswaLain->id_siswa, $idsInView);
        }
    }

    /**
     * Sistem mendeteksi peringatan dini berdasarkan kriteria kehadiran (Alpha) riil dari database.
     */
    public function test_early_warning_detects_attendance_criteria_from_database(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $kelasBinaan = $wali->getKelasBinaan();

        // Buat siswa baru di kelas wali dengan banyak ketidakhadiran Alpha
        $siswaAlpha = Siswa::create([
            'nis' => '99911',
            'nama' => 'Siswa Sering Bolos',
            'jenis_kelamin' => 'L',
            'id_kelas' => $kelasBinaan->id_kelas,
            'status_siswa' => 'Aktif',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Absensi::create([
                'id_siswa' => $siswaAlpha->id_siswa,
                'id_mapel' => 1,
                'id_semester' => 2,
                'tanggal' => now()->subDays($i)->toDateString(),
                'status' => 'Alpha',
            ]);
        }

        $response = $this->actingAs($wali)->get(route('wali.early-warning', ['semester_id' => 2]));

        $response->assertStatus(200);
        $siswaList = $response->viewData('siswaEarlyWarning');
        $idsInView = array_column($siswaList, 'id_siswa');

        $this->assertContains($siswaAlpha->id_siswa, $idsInView);
    }

    /**
     * Menyimpan tindak lanjut mencatat data riil ke tabel tindak_lanjut.
     */
    public function test_tindak_lanjut_stores_record_in_database(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $siswa = Siswa::first();

        // Pastikan ada analisis siswa
        $analisis = AnalisisSiswa::firstOrCreate(
            ['id_siswa' => $siswa->id_siswa, 'id_semester' => 2],
            ['status' => 'Perlu Perhatian', 'tanggal_analisis' => now()->toDateString(), 'ringkasan' => 'Analisis test']
        );

        $response = $this->actingAs($wali)->post(route('wali.tindak-lanjut.store'), [
            'id_siswa' => $siswa->id_siswa,
            'tanggal_sesi' => now()->addDay()->toDateString(),
            'waktu_sesi' => '10:00',
            'jenis_konseling' => 'Akademik & perilaku',
            'penanggung_jawab' => 'Guru BK',
            'tanggal_evaluasi' => now()->addDays(7)->toDateString(),
            'catatan' => 'Siswa berkomitmen memperbaiki nilai',
        ]);

        $response->assertRedirect(route('wali.early-warning'));
        $this->assertDatabaseHas('tindak_lanjut', [
            'id_user' => $wali->id_user,
            'status' => 'Sedang Diproses',
        ]);
    }
}
