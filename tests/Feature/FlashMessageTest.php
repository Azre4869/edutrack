<?php

namespace Tests\Feature;

use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Masalah 1: Pesan sukses muncul dua kali karena dirender di layouts/app.blade.php
 * DAN di dalam masing-masing view (input-nilai, catatan, ekskul, dll).
 * Perbaikan: hapus blok @if(session('success')) lokal dari semua view — sumber tunggal di layout.
 */
class FlashMessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Setelah POST simpan nilai, pesan sukses muncul tepat SATU kali di halaman.
     */
    public function test_success_flash_appears_exactly_once_after_store_nilai(): void
    {
        $operator  = User::where('username', 'operator')->first();
        $mapel     = MataPelajaran::first();

        $siswa = Siswa::firstOrCreate(
            ['nis' => 'FLASH_TEST_01'],
            ['nama' => 'Siswa Flash Test', 'jenis_kelamin' => 'L', 'id_kelas' => 1, 'status_siswa' => 'Aktif']
        );

        // Kirim POST dengan Referer agar back() redirect ke halaman nilai, bukan ke /
        $response = $this->actingAs($operator)
            ->withHeaders(['Referer' => route('operator.nilai')])
            ->followingRedirects()
            ->post(route('operator.nilai.store'), [
                'semester_id' => 1,
                'mapel_id'    => $mapel->id_mapel,
                'nilai'       => [
                    $siswa->id_siswa => ['tp1' => 80, 'tp2' => 85, 'sas' => 75],
                ],
            ]);

        $response->assertStatus(200);

        $body = $response->getContent();

        $expectedMsg = 'Rekap nilai siswa berhasil disimpan.';
        $count = substr_count($body, $expectedMsg);

        $this->assertEquals(1, $count,
            "Pesan sukses ditemukan {$count} kali di halaman, seharusnya tepat 1 kali."
        );
    }
}
