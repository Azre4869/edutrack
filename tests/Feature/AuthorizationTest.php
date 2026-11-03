<?php

namespace Tests\Feature;

use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
    }

    /**
     * Wali VII A mengirim id_siswa milik VII G dan harus mendapat 403.
     */
    public function test_wali_kelas_cannot_modify_other_class_students(): void
    {
        // User walikelas (Endang Sulistian, id_guru = 1, Wali Kelas VII A = id_kelas 1)
        $wali = User::where('username', 'walikelas')->first();

        // Buat atau cari siswa di kelas VII G (id_kelas = 2)
        $siswaLain = Siswa::create([
            'nis' => 'TEST9999',
            'nama' => 'Siswa Kelas Lain',
            'jenis_kelamin' => 'L',
            'id_kelas' => 2, // VII G
            'status_siswa' => 'Aktif',
        ]);

        $response = $this->actingAs($wali)->post(route('wali.catatan.store'), [
            'semester_id' => 1,
            'catatan' => [
                $siswaLain->id_siswa => [
                    'catatan_wali' => 'Hacked catatan',
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    /**
     * Guru tanpa mapel tidak bisa menyimpan nilai (mendapat 403).
     */
    public function test_guru_without_mapel_cannot_store_grades(): void
    {
        // Buat guru baru tanpa mapel
        $guru = Guru::create([
            'nip' => '999888777',
            'nama_guru' => 'Guru Tanpa Mapel',
            'jenis_guru' => 'Guru Honorer',
            'status' => 'Aktif',
        ]);

        $userGuru = User::create([
            'username' => 'gurutesting',
            'password' => bcrypt('secret123'),
            'role' => 'Guru Mapel',
            'id_guru' => $guru->id_guru,
        ]);

        $siswa = Siswa::create([
            'nis' => 'TEST1111',
            'nama' => 'Siswa Test',
            'jenis_kelamin' => 'L',
            'id_kelas' => 1,
            'status_siswa' => 'Aktif',
        ]);

        $mapel = MataPelajaran::first();

        $response = $this->actingAs($userGuru)->post(route('guru.nilai.store'), [
            'semester_id' => 1,
            'mapel_id' => $mapel->id_mapel,
            'nilai' => [
                $siswa->id_siswa => [
                    'tp1' => 80,
                    'tp2' => 85,
                    'sas' => 90,
                ],
            ],
        ]);

        $response->assertStatus(403);
    }

    /**
     * Wali tanpa kelas binaan ditolak (403) dan tidak melihat daftar kelas.
     */
    public function test_wali_without_class_is_forbidden_and_cannot_view_classes(): void
    {
        // Buat guru baru tanpa kelas wali
        $guru = Guru::create([
            'nip' => '555666777',
            'nama_guru' => 'Guru Belum Ada Kelas',
            'jenis_guru' => 'Guru Tetap',
            'status' => 'Aktif',
        ]);

        $waliTanpaKelas = User::create([
            'username' => 'walitanpakelas',
            'password' => bcrypt('secret123'),
            'role' => 'Wali Kelas',
            'id_guru' => $guru->id_guru,
        ]);

        $response = $this->actingAs($waliTanpaKelas)->get(route('wali.catatan'));

        $response->assertStatus(403);
    }

    /**
     * User dengan role tak dikenal memicu logout dan redirect ke login, bukan redirect berputar.
     */
    public function test_unknown_role_triggers_logout_instead_of_redirect_loop(): void
    {
        $unknownUser = User::create([
            'username' => 'unknownuser',
            'password' => bcrypt('secret123'),
            'role' => 'UnknownRole',
        ]);

        $response = $this->actingAs($unknownUser)->get('/');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
