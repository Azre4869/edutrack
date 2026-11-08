<?php

namespace Tests\Feature;

use App\Models\AnalisisSiswa;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TindakLanjutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Test operator dapat menyimpan tindak lanjut konseling dan diarahkan ke operator.early-warning.
     */
    public function test_operator_can_store_tindak_lanjut(): void
    {
        $operator = User::where('username', 'operator')->first();
        $siswa = Siswa::firstOrCreate(
            ['nis' => 'TEST_TL_01'],
            ['nama' => 'Siswa Test TL', 'jenis_kelamin' => 'L', 'id_kelas' => 1, 'status_siswa' => 'Aktif']
        );

        $response = $this->actingAs($operator)->post(route('operator.tindak-lanjut.store'), [
            'id_siswa'         => $siswa->id_siswa,
            'tanggal_sesi'     => '2026-11-10',
            'waktu_sesi'       => '10:00',
            'jenis_konseling'  => 'Akademik',
            'penanggung_jawab' => 'Guru Konselor',
            'tanggal_evaluasi' => '2026-11-24',
            'catatan'          => 'Catatan konseling operator.',
        ]);

        $response->assertRedirect(route('operator.early-warning'));
        $this->assertDatabaseHas('tindak_lanjut', [
            'id_user' => $operator->id_user,
            'tanggal' => '2026-11-10',
            'jenis_konseling' => 'Akademik',
        ]);
    }

    /**
     * Test wali kelas dapat menyimpan tindak lanjut dan diarahkan ke wali.early-warning (tidak terkena 403).
     */
    public function test_wali_can_store_tindak_lanjut_and_redirects_to_wali_early_warning(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $guru = Guru::first();
        $kelasBinaan = $wali->requireKelasBinaan();
        $siswa = Siswa::create([
            'nis' => 'TEST_WALI_01',
            'nama' => 'Siswa Binaan Wali',
            'jenis_kelamin' => 'L',
            'id_kelas' => $kelasBinaan->id_kelas,
            'status_siswa' => 'Aktif'
        ]);

        $response = $this->actingAs($wali)->post(route('wali.tindak-lanjut.store'), [
            'id_siswa'         => $siswa->id_siswa,
            'pendamping'       => $guru->id_guru,
            'penanggung_jawab' => $guru->id_guru,
            'tanggal_sesi'     => '2026-11-15',
            'waktu_sesi'       => '08:30',
            'jenis_konseling'  => 'Kehadiran',
            'tanggal_evaluasi' => '2026-11-29',
            'catatan'          => 'Catatan konseling wali kelas.',
        ]);

        $response->assertRedirect(route('wali.early-warning'));
        $this->assertDatabaseHas('tindak_lanjut', [
            'id_user' => $wali->id_user,
            'id_guru' => $guru->id_guru,
            'tanggal' => '2026-11-15',
            'status'  => 'Sedang Diproses',
        ]);
    }

    /**
     * Test siswa B tidak tertaut ke id_analisis milik siswa A.
     */
    public function test_siswa_b_not_linked_to_analisis_siswa_a(): void
    {
        $operator = User::where('username', 'operator')->first();

        // Buat Siswa A dan analisisnya
        $siswaA = Siswa::create([
            'nis'          => 'SISWA_A',
            'nama'         => 'Siswa A',
            'jenis_kelamin' => 'L',
            'id_kelas'     => 1,
            'status_siswa' => 'Aktif',
        ]);

        $analisisA = AnalisisSiswa::create([
            'id_siswa'         => $siswaA->id_siswa,
            'id_semester'      => 1,
            'tanggal_analisis' => '2026-10-01',
            'status'           => 'Perlu Perhatian',
            'ringkasan'        => 'Analisis Siswa A',
        ]);

        // Buat Siswa B
        $siswaB = Siswa::create([
            'nis'          => 'SISWA_B',
            'nama'         => 'Siswa B',
            'jenis_kelamin' => 'P',
            'id_kelas'     => 1,
            'status_siswa' => 'Aktif',
        ]);

        // Simpan tindak lanjut untuk Siswa B
        $response = $this->actingAs($operator)->post(route('operator.tindak-lanjut.store'), [
            'id_siswa'         => $siswaB->id_siswa,
            'tanggal_sesi'     => '2026-11-12',
            'waktu_sesi'       => '09:00',
            'jenis_konseling'  => 'Kehadiran',
            'penanggung_jawab' => 'Guru BK',
            'tanggal_evaluasi' => '2026-11-26',
        ]);

        $response->assertRedirect(route('operator.early-warning'));

        // Ambil tindak lanjut yang baru dibuat
        $tindakLanjutB = TindakLanjut::where('tanggal', '2026-11-12')->first();

        $this->assertNotNull($tindakLanjutB);
        // Pastikan id_analisis B BUKAN id_analisis A
        $this->assertNotEquals($analisisA->id_analisis, $tindakLanjutB->id_analisis);
        // Pastikan id_analisis B tertaut ke Siswa B
        $this->assertEquals($siswaB->id_siswa, $tindakLanjutB->analisisSiswa->id_siswa);
    }

    /**
     * Test validasi gagal bila id_analisis yang dikirim tidak sesuai dengan siswa.
     */
    public function test_cannot_store_tindak_lanjut_with_mismatched_id_analisis(): void
    {
        $operator = User::where('username', 'operator')->first();

        $siswaA = Siswa::create([
            'nis' => 'TEST_A', 'nama' => 'Siswa A', 'jenis_kelamin' => 'L', 'id_kelas' => 1, 'status_siswa' => 'Aktif'
        ]);
        $analisisA = AnalisisSiswa::create([
            'id_siswa' => $siswaA->id_siswa, 'id_semester' => 1, 'tanggal_analisis' => '2026-10-01', 'status' => 'Perlu Perhatian'
        ]);

        $siswaB = Siswa::create([
            'nis' => 'TEST_B', 'nama' => 'Siswa B', 'jenis_kelamin' => 'P', 'id_kelas' => 1, 'status_siswa' => 'Aktif'
        ]);

        // Mencoba mengirim analisis milik siswa A untuk siswa B
        $response = $this->actingAs($operator)->post(route('operator.tindak-lanjut.store'), [
            'id_siswa'         => $siswaB->id_siswa,
            'id_analisis'      => $analisisA->id_analisis,
            'tanggal_sesi'     => '2026-11-15',
            'tanggal_evaluasi' => '2026-11-29',
        ]);

        $response->assertSessionHasErrors('id_analisis');
    }

    /**
     * Test view tindak lanjut menampilkan data riil siswa.
     */
    public function test_tindak_lanjut_view_shows_real_metrics_without_dummy_data(): void
    {
        $operator = User::where('username', 'operator')->first();
        $siswa = Siswa::create([
            'nis' => 'TEST_METRIK', 'nama' => 'Siswa Riil Metrik', 'jenis_kelamin' => 'L', 'id_kelas' => 1, 'status_siswa' => 'Aktif'
        ]);

        $activeSemester = Semester::aktif();

        Nilai::create([
            'id_siswa' => $siswa->id_siswa,
            'id_mapel' => 1,
            'id_semester' => $activeSemester->id_semester,
            'nilai_akhir' => 70.0, // di bawah KKM 75
        ]);

        $response = $this->actingAs($operator)->get(route('operator.tindak-lanjut', $siswa->id_siswa));

        $response->assertStatus(200);
        $response->assertSee('Siswa Riil Metrik');
        $response->assertSee('70,0');
        $response->assertSee('1 mapel');
    }

    /**
     * Test pencarian kosong di Early Warning tidak menyebabkan error 500.
     */
    public function test_empty_search_in_early_warning_handled_gracefully(): void
    {
        $operator = User::where('username', 'operator')->first();

        // Cari dengan keyword yang dipastikan tidak ada
        $response = $this->actingAs($operator)->get(route('operator.early-warning', [
            'search' => 'NAMA_TIDAK_MUNGKIN_ADA_XYZ123',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Tidak Ada Siswa Terpilih');
    }

    /**
     * Test operator dapat menandai tindak lanjut sebagai selesai.
     */
    public function test_operator_can_mark_tindak_lanjut_completed(): void
    {
        $operator = User::where('username', 'operator')->first();
        $siswa = Siswa::firstOrCreate(
            ['nis' => 'TEST_TL_DONE'],
            ['nama' => 'Siswa Test Selesai', 'jenis_kelamin' => 'L', 'id_kelas' => 1, 'status_siswa' => 'Aktif']
        );
        $analisis = AnalisisSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_semester' => Semester::aktif()?->id_semester ?? 1,
            'tanggal_analisis' => now()->toDateString(),
            'status' => 'Perlu Perhatian',
        ]);
        $tl = TindakLanjut::create([
            'id_analisis' => $analisis->id_analisis,
            'id_user' => $operator->id_user,
            'tanggal' => now()->toDateString(),
            'tindakan' => 'Sesi Konseling',
            'status' => 'Sedang Diproses',
        ]);

        $response = $this->actingAs($operator)->patch(route('operator.tindak-lanjut.selesai', $tl->id_tindak_lanjut), [
            'hasil_evaluasi' => 'Siswa menunjukkan perbaikan.',
            'skor_efektivitas' => 'Membaik',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tindak_lanjut', [
            'id_tindak_lanjut' => $tl->id_tindak_lanjut,
            'status' => 'Selesai',
            'hasil_evaluasi' => 'Siswa menunjukkan perbaikan.',
            'skor_efektivitas' => 'Membaik',
        ]);
    }

    /**
     * Test operator dapat menghapus tindak lanjut.
     */
    public function test_operator_can_delete_tindak_lanjut(): void
    {
        $operator = User::where('username', 'operator')->first();
        $siswa = Siswa::firstOrCreate(
            ['nis' => 'TEST_TL_DEL'],
            ['nama' => 'Siswa Test Hapus', 'jenis_kelamin' => 'L', 'id_kelas' => 1, 'status_siswa' => 'Aktif']
        );
        $analisis = AnalisisSiswa::create([
            'id_siswa' => $siswa->id_siswa,
            'id_semester' => Semester::aktif()?->id_semester ?? 1,
            'tanggal_analisis' => now()->toDateString(),
            'status' => 'Perlu Perhatian',
        ]);
        $tl = TindakLanjut::create([
            'id_analisis' => $analisis->id_analisis,
            'id_user' => $operator->id_user,
            'tanggal' => now()->toDateString(),
            'tindakan' => 'Sesi Konseling Hapus',
            'status' => 'Sedang Diproses',
        ]);

        $response = $this->actingAs($operator)->delete(route('operator.tindak-lanjut.destroy', $tl->id_tindak_lanjut));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('tindak_lanjut', [
            'id_tindak_lanjut' => $tl->id_tindak_lanjut,
        ]);
    }

    /**
     * Test wali kelas tidak dapat menghapus tindak lanjut milik siswa di luar kelas binaannya (DLAC).
     */
    public function test_wali_cannot_delete_tindak_lanjut_of_other_class(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $kelasBinaan = $wali->requireKelasBinaan();

        // Siswa di kelas lain (id_kelas != kelas binaan)
        $siswaLain = Siswa::create([
            'nis' => 'TEST_SISWA_OTHER',
            'nama' => 'Siswa Kelas Lain',
            'jenis_kelamin' => 'P',
            'id_kelas' => $kelasBinaan->id_kelas === 1 ? 2 : 1,
            'status_siswa' => 'Aktif'
        ]);
        $analisis = AnalisisSiswa::create([
            'id_siswa' => $siswaLain->id_siswa,
            'id_semester' => Semester::aktif()?->id_semester ?? 1,
            'tanggal_analisis' => now()->toDateString(),
            'status' => 'Perlu Perhatian',
        ]);
        $tl = TindakLanjut::create([
            'id_analisis' => $analisis->id_analisis,
            'id_user' => 1,
            'tanggal' => now()->toDateString(),
            'tindakan' => 'Sesi Konseling Kelas Lain',
            'status' => 'Sedang Diproses',
        ]);

        $response = $this->actingAs($wali)->delete(route('wali.tindak-lanjut.destroy', $tl->id_tindak_lanjut));
        $response->assertStatus(403);
    }
}
