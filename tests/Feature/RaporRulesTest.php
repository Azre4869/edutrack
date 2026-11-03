<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RaporRulesTest extends TestCase
{
    use RefreshDatabase;

    protected User $waliUser;
    protected Siswa $siswaIncomplete;
    protected Siswa $siswaComplete;
    protected Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);

        // Buat atau ambil user wali kelas VII A
        $this->waliUser = User::where('username', 'walikelas')->first();
        $this->semester = Semester::firstOrCreate(
            ['id_semester' => 2],
            ['nama_semester' => 'Genap', 'tahun_ajaran' => '2026/2027', 'status' => 'Aktif']
        );

        // Siswa 1: Belum Lengkap (hanya 1 mapel)
        $this->siswaIncomplete = Siswa::create([
            'nis' => '11111',
            'nama' => 'Siswa Belum Lengkap',
            'jenis_kelamin' => 'L',
            'id_kelas' => 1,
            'status_siswa' => 'Aktif',
        ]);

        // Siswa 2: Lengkap 12 Mapel
        $this->siswaComplete = Siswa::create([
            'nis' => '22222',
            'nama' => 'Siswa Lengkap Contoh',
            'jenis_kelamin' => 'P',
            'id_kelas' => 1,
            'status_siswa' => 'Aktif',
        ]);

        $mapels = MataPelajaran::all();

        // Isi 1 mapel untuk siswaIncomplete
        if ($mapels->isNotEmpty()) {
            $nInc = Nilai::create([
                'id_siswa' => $this->siswaIncomplete->id_siswa,
                'id_mapel' => $mapels->first()->id_mapel,
                'id_semester' => 2,
                'tp1' => 85,
                'sas' => 88,
                'nilai_akhir' => 86.5,
            ]);
            DB::table('capaian_kompetensi')->insert([
                'id_nilai' => $nInc->id_nilai,
                'deskripsi_1' => 'Sangat menguasai materi TP 1.',
                'deskripsi_2' => 'Perlu bimbingan lebih lanjut.',
            ]);
        }

        // Isi semua 12 mapel untuk siswaComplete
        foreach ($mapels as $m) {
            $nComp = Nilai::create([
                'id_siswa' => $this->siswaComplete->id_siswa,
                'id_mapel' => $m->id_mapel,
                'id_semester' => 2,
                'tp1' => 85,
                'tp2' => 90,
                'sas' => 88,
                'nilai_akhir' => 87.5,
            ]);
            DB::table('capaian_kompetensi')->insert([
                'id_nilai' => $nComp->id_nilai,
                'deskripsi_1' => 'Menunjukkan penguasaan sangat baik pada mata pelajaran ' . $m->nama_mapel,
                'deskripsi_2' => 'Perlu peningkatan pada pemahaman konsep lanjutan.',
            ]);
        }
    }

    /**
     * Test: Server menolak cetak rapor jika siswa belum lengkap (redirect dengan pesan error).
     */
    public function test_server_rejects_printing_incomplete_rapor(): void
    {
        $response = $this->actingAs($this->waliUser)
            ->get(route('wali.rapor.cetak', ['id' => $this->siswaIncomplete->id_siswa, 'semester_id' => 2]));

        // Harus di-redirect, bukan 200 OK!
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    /**
     * Test: Server mengizinkan cetak rapor jika siswa sudah lengkap 12 mapel.
     */
    public function test_server_allows_printing_complete_rapor(): void
    {
        $response = $this->actingAs($this->waliUser)
            ->get(route('wali.rapor.cetak', ['id' => $this->siswaComplete->id_siswa, 'semester_id' => 2]));

        $response->assertStatus(200);
        $response->assertViewIs('rapor.cetak');
    }

    /**
     * Test: Tombol toggle status palsu harus dihapus dari view index rapor.
     */
    public function test_rapor_index_has_no_fake_toggle_button(): void
    {
        $response = $this->actingAs($this->waliUser)
            ->get(route('wali.rapor', ['semester_id' => 2, 'id_kelas' => 1]));

        $response->assertStatus(200);
        $response->assertDontSee('toggleStatusRapor');
    }
}
