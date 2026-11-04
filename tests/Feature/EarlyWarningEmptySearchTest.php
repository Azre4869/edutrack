<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Masalah 3: Pencarian kosong di Early Warning.
 * Saat pencarian tidak menemukan hasil, panel detail harus menampilkan keadaan kosong ("Tidak Ada Siswa Terpilih"),
 * dan TIDAK menampilkan siswa mana pun di panel detail (bukan fallback ke siswa lain).
 */
class EarlyWarningEmptySearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    public function test_empty_search_shows_empty_state_and_no_student_details(): void
    {
        $operator = User::where('username', 'operator')->first();
        $kelas    = Kelas::first(); // VII A

        // Buat satu siswa berisiko di kelas ini
        $siswa = Siswa::create([
            'nis' => 'TEST_SEARCH_01',
            'nama' => 'Budi Berisiko Ada',
            'jenis_kelamin' => 'L',
            'id_kelas' => $kelas->id_kelas,
            'status_siswa' => 'Aktif',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Absensi::create([
                'id_siswa' => $siswa->id_siswa,
                'id_mapel' => 1,
                'id_semester' => 2,
                'tanggal' => now()->subDays($i)->toDateString(),
                'status' => 'Alpha',
            ]);
        }

        // Buka halaman dengan keyword pencarian yang tidak mungkin ada
        $response = $this->actingAs($operator)->get(route('operator.early-warning', [
            'kelas_id'     => $kelas->id_kelas,
            'tahun_ajaran' => '2026/2027',
            'semester_id'  => 2,
            'search'       => 'NAMA_TIDAK_MUNGKIN_ADA_XYZ123',
        ]));

        $response->assertStatus(200);

        // 1. Teks "Tidak Ada Siswa Terpilih" harus tampil di panel samping
        $response->assertSee('Tidak Ada Siswa Terpilih');

        // 2. viewData('selectedSiswa') harus null
        $this->assertNull($response->viewData('selectedSiswa'), 'selectedSiswa harus null saat hasil pencarian kosong');

        // 3. Nama siswa mana pun (termasuk Budi) TIDAK boleh tampil di panel detail
        $response->assertDontSee('Tindak Lanjut Siswa Ini');
        $response->assertDontSee($siswa->nama);

        // 4. Tabel menampilkan pesan kosong yang konsisten
        $response->assertSee('Tidak ada data peringatan dini yang cocok dengan pencarian');
    }
}
