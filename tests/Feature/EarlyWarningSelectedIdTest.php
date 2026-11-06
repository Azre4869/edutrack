<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test untuk memastikan selected_id meneruskan filter yang ada,
 * dan panel detail menampilkan siswa yang diklik — bukan siswa lain.
 *
 * Bug yang diperbaiki: link baris memakai `?selected_id=...` relatif
 * sehingga membuang semua query string (kelas_id, semester_id, tahun_ajaran).
 */
class EarlyWarningSelectedIdTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Buat siswa dengan alpha yang cukup agar muncul di daftar early warning.
     */
    private function buatSiswaBerisiko(int $kelasId, string $nis, string $nama, int $semesterId = 1): Siswa
    {
        $siswa = Siswa::create([
            'nis'           => $nis,
            'nama'          => $nama,
            'jenis_kelamin' => 'L',
            'id_kelas'      => $kelasId,
            'status_siswa'  => 'Aktif',
        ]);

        // 5 hari Alpha → memenuhi kriteria "Kehadiran Alpha Berlebih" (ambang 3)
        for ($i = 1; $i <= 5; $i++) {
            Absensi::create([
                'id_siswa'    => $siswa->id_siswa,
                'id_mapel'    => 1,
                'id_semester' => $semesterId,
                'tanggal'     => now()->subDays($i)->toDateString(),
                'status'      => 'Alpha',
            ]);
        }

        return $siswa;
    }

    /**
     * (a) Respons 200.
     * (b) Panel detail menampilkan nama siswa yang diklik.
     * (c) URL baris di tabel memuat kelas_id, semester_id, tahun_ajaran.
     */
    public function test_selected_id_shows_correct_student_and_preserves_filters(): void
    {
        $operator = User::where('username', 'operator')->first();
        $kelas    = Kelas::where('id_kelas', 1)->first(); // VII A

        $siswa = $this->buatSiswaBerisiko($kelas->id_kelas, 'TST_EW_01', 'Andi Berisiko');

        $params = [
            'tahun_ajaran' => '2026/2027',
            'semester_id'  => 1,
            'kelas_id'     => $kelas->id_kelas,
            'search'       => '',
            'selected_id'  => $siswa->id_siswa,
        ];

        $response = $this->actingAs($operator)->get(route('operator.early-warning', $params));

        // (a) Halaman berhasil
        $response->assertStatus(200);

        // (b) Panel detail menampilkan nama siswa yang diklik
        $selectedSiswa = $response->viewData('selectedSiswa');
        $this->assertNotNull($selectedSiswa, 'selectedSiswa tidak boleh null');
        $this->assertEquals($siswa->id_siswa, $selectedSiswa['id_siswa'], 'Panel detail harus menampilkan siswa yang dipilih');
        $response->assertSee($siswa->nama);

        // (c) Link baris di tabel memuat query filter
        $response->assertSee('kelas_id=' . $kelas->id_kelas, false);
        $response->assertSee('semester_id=1', false);
        $response->assertSee('tahun_ajaran=2026', false); // bagian URL encoded
    }

    /**
     * Ketika selected_id merujuk siswa di kelas berbeda dari filter,
     * panel tidak diam-diam menampilkan siswa dari kelas lain.
     */
    public function test_selected_id_from_different_class_does_not_leak(): void
    {
        $operator = User::where('username', 'operator')->first();

        $kelasA = Kelas::where('id_kelas', 1)->first(); // VII A
        $kelasB = Kelas::where('id_kelas', 3)->first(); // VIII A

        // Buat siswa di kelas B yang berisiko
        $siswaB = $this->buatSiswaBerisiko($kelasB->id_kelas, 'TST_EW_B1', 'Budi Kelas Lain');

        // Request dengan filter kelas A, tapi selected_id menunjuk siswa kelas B
        $params = [
            'kelas_id'    => $kelasA->id_kelas,
            'semester_id' => 1,
            'selected_id' => $siswaB->id_siswa,
        ];

        $response = $this->actingAs($operator)->get(route('operator.early-warning', $params));

        $response->assertStatus(200);

        // Panel tidak boleh menampilkan nama siswa kelas B
        $selectedSiswa = $response->viewData('selectedSiswa');
        if ($selectedSiswa !== null) {
            $this->assertNotEquals($siswaB->id_siswa, $selectedSiswa['id_siswa'],
                'Panel tidak boleh menampilkan siswa dari kelas berbeda');
        }
        // Jika null, panel menampilkan placeholder "Tidak Ada Siswa Terpilih" — itu benar
    }

    /**
     * Wali kelas hanya boleh melihat siswa kelas binaannya;
     * selected_id dari kelas lain tidak boleh bocor ke panel detail.
     */
    public function test_wali_cannot_view_student_from_other_class_via_selected_id(): void
    {
        $wali = User::where('username', 'walikelas')->first();

        // Kelas binaan wali: id_kelas=1 (guru id_guru=1, wali kelas VII A)
        $kelasBinaan = $wali->getKelasBinaan();
        $this->assertNotNull($kelasBinaan, 'Wali harus punya kelas binaan');

        // Cari kelas lain
        $kelasLain = Kelas::where('id_kelas', '!=', $kelasBinaan->id_kelas)->first();
        $this->assertNotNull($kelasLain);

        // Buat siswa berisiko di kelas lain
        $siswaLain = $this->buatSiswaBerisiko($kelasLain->id_kelas, 'TST_EW_WALI', 'Zahra Kelas Lain');

        $response = $this->actingAs($wali)->get(route('wali.early-warning', [
            'selected_id' => $siswaLain->id_siswa,
        ]));

        $response->assertStatus(200);

        $selectedSiswa = $response->viewData('selectedSiswa');
        if ($selectedSiswa !== null) {
            $this->assertNotEquals($siswaLain->id_siswa, $selectedSiswa['id_siswa'],
                'Wali kelas tidak boleh melihat detail siswa kelas lain via selected_id');
        }
    }
}
