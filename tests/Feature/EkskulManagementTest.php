<?php

namespace Tests\Feature;

use App\Models\Ekstrakurikuler;
use App\Models\NilaiEkstrakurikuler;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EkskulManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
        $this->seed(\Database\Seeders\SemesterAktifSeeder::class);
    }

    public function test_operator_can_view_ekskul_list_with_student_counts(): void
    {
        $operator = User::where('username', 'operator')->first();
        $activeSemester = Semester::aktif();

        // Buat ekskul baru
        $ekskul = Ekstrakurikuler::create([
            'nama_ekskul' => 'Koding & Robotika',
            'status' => 'Aktif',
            'jadwal' => 'Sabtu, 09.00',
        ]);

        // Ekskul baru belum ada anggota (harus melihat '0 Siswa')
        $response = $this->actingAs($operator)->get(route('operator.ekskul'));
        $response->assertStatus(200);
        $response->assertSee('Koding & Robotika');
        $response->assertSee('0 Siswa');

        // Buat 2 siswa uji
        $siswa1 = Siswa::create([
            'nis' => '99991',
            'nama' => 'Siswa Ekskul 1',
            'jenis_kelamin' => 'L',
            'id_kelas' => 1,
            'status_siswa' => 'Aktif',
        ]);
        $siswa2 = Siswa::create([
            'nis' => '99992',
            'nama' => 'Siswa Ekskul 2',
            'jenis_kelamin' => 'P',
            'id_kelas' => 1,
            'status_siswa' => 'Aktif',
        ]);

        // Daftarkan 2 siswa ke ekskul ini pada semester aktif
        foreach ([$siswa1, $siswa2] as $s) {
            NilaiEkstrakurikuler::create([
                'id_siswa' => $s->id_siswa,
                'id_ekskul' => $ekskul->id_ekskul,
                'id_semester' => $activeSemester->id_semester,
                'predikat' => 'Baik',
                'deskripsi' => 'Aktif mengikuti kegiatan',
            ]);
        }

        // Kunjungi kembali, harus melihat '2 Siswa'
        $response = $this->actingAs($operator)->get(route('operator.ekskul'));
        $response->assertStatus(200);
        $response->assertSee('2 Siswa');
    }

    public function test_non_operator_cannot_access_operator_ekskul(): void
    {
        $wali = User::where('role', 'Wali Kelas')->first();
        $guru = User::where('role', 'Guru Mapel')->first();

        $responseWali = $this->actingAs($wali)->get(route('operator.ekskul'));
        $responseWali->assertRedirect(route('wali.dashboard'));

        $responseGuru = $this->actingAs($guru)->get(route('operator.ekskul'));
        $responseGuru->assertRedirect(route('guru.dashboard'));
    }
}
