<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataLossGuardsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Menghapus kelas yang berisi siswa harus ditolak.
     */
    public function test_deleting_class_with_students_is_rejected(): void
    {
        $operator = User::where('username', 'operator')->first();

        // Buat kelas berisi siswa
        $kelas = Kelas::create([
            'nama_kelas' => 'VII Test',
            'tingkat' => 7,
            'tahun_ajaran' => '2026/2027',
        ]);

        Siswa::create([
            'nis' => 'TEST12345',
            'nama' => 'Siswa Test Kelas',
            'jenis_kelamin' => 'L',
            'id_kelas' => $kelas->id_kelas,
            'status_siswa' => 'Aktif',
        ]);

        $response = $this->actingAs($operator)->delete(route('operator.kelas.destroy', $kelas->id_kelas));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('kelas', ['id_kelas' => $kelas->id_kelas]);
    }

    /**
     * Menghapus kelas kosong harus berhasil.
     */
    public function test_deleting_empty_class_succeeds(): void
    {
        $operator = User::where('username', 'operator')->first();

        // Buat kelas kosong tanpa siswa
        $kelasKosong = Kelas::create([
            'nama_kelas' => 'VII Empty',
            'tingkat' => 7,
            'tahun_ajaran' => '2026/2027',
        ]);

        $response = $this->actingAs($operator)->delete(route('operator.kelas.destroy', $kelasKosong->id_kelas));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('kelas', ['id_kelas' => $kelasKosong->id_kelas]);
    }

    /**
     * Artisan command clean ghost grades dry-run dan execution.
     */
    public function test_clean_ghost_grades_command(): void
    {
        $siswa = Siswa::create([
            'nis' => 'TESTGHOST',
            'nama' => 'Siswa Ghost',
            'jenis_kelamin' => 'L',
            'id_kelas' => 1,
            'status_siswa' => 'Aktif',
        ]);

        // Buat nilai palsu 85.00 dengan TP dan SAS kosong
        $ghost = Nilai::create([
            'id_siswa' => $siswa->id_siswa,
            'id_mapel' => 1,
            'id_semester' => 1,
            'tp1' => null,
            'tp2' => null,
            'sas' => null,
            'nilai_akhir' => 85.00,
        ]);

        // Test dry-run: data tidak boleh terhapus
        $this->artisan('grades:clean-ghost', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('[DRY-RUN]');

        $this->assertDatabaseHas('nilai', ['id_nilai' => $ghost->id_nilai]);

        // Test real run: nilai_akhir harus dikosongkan (null) tanpa menghapus baris
        $this->artisan('grades:clean-ghost')
            ->assertSuccessful()
            ->expectsOutputToContain('Berhasil mengosongkan nilai_akhir');

        $this->assertDatabaseHas('nilai', [
            'id_nilai' => $ghost->id_nilai,
            'nilai_akhir' => null,
        ]);
    }

    /**
     * Menghapus siswa yang memiliki analisis atau catatan wali harus ditolak.
     */
    public function test_deleting_student_with_analysis_or_notes_is_rejected(): void
    {
        $operator = User::where('username', 'operator')->first();

        $siswa = Siswa::create([
            'nis' => 'TEST_STUDENT_CATATAN',
            'nama' => 'Siswa Catatan Test',
            'jenis_kelamin' => 'L',
            'id_kelas' => 1,
            'status_siswa' => 'Aktif',
        ]);

        \App\Models\CatatanWali::create([
            'id_siswa' => $siswa->id_siswa,
            'id_semester' => 1,
            'catatan_wali' => 'Perlu pendampingan belajar',
            'status_kenaikan' => 'Naik',
        ]);

        $response = $this->actingAs($operator)->delete(route('operator.siswa.destroy', $siswa->id_siswa));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('siswa', ['id_siswa' => $siswa->id_siswa]);
    }
}
