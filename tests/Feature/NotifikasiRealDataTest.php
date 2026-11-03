<?php

namespace Tests\Feature;

use App\Models\AnalisisSiswa;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifikasiRealDataTest extends TestCase
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
     * User melihat data notifikasi riil dari tabel notifikasi di database.
     */
    public function test_user_sees_own_notifications_from_database(): void
    {
        $wali = User::where('username', 'walikelas')->first();

        $initialTotal = Notifikasi::where('id_user', $wali->id_user)->count();
        $initialUnread = Notifikasi::where('id_user', $wali->id_user)->where('sudah_dibaca', 0)->count();

        // Buat dummy analisis jika belum ada
        $analisis = AnalisisSiswa::create([
            'id_siswa' => 1,
            'id_semester' => 1,
            'status' => 'Perlu Perhatian',
            'skor_total' => 65.5,
            'rekomendasi' => 'Perlu bimbingan',
            'tanggal_analisis' => now(),
        ]);

        // Buat notifikasi riil untuk user wali
        $notif1 = Notifikasi::create([
            'id_analisis' => $analisis->id_analisis,
            'id_user' => $wali->id_user,
            'pesan' => 'Peringatan khusus EWS untuk kelas Anda',
            'sudah_dibaca' => 0,
            'tanggal_kirim' => now(),
        ]);

        $notif2 = Notifikasi::create([
            'id_analisis' => $analisis->id_analisis,
            'id_user' => $wali->id_user,
            'pesan' => 'Jadwal input nilai akhir semester genap',
            'sudah_dibaca' => 1,
            'tanggal_kirim' => now()->subDay(),
        ]);

        $response = $this->actingAs($wali)->get(route('wali.notifikasi'));

        $response->assertStatus(200);
        $response->assertSee('Peringatan khusus EWS untuk kelas Anda');
        $response->assertSee('Jadwal input nilai akhir semester genap');
        $response->assertViewHas('total', $initialTotal + 2);
        $response->assertViewHas('belumDibaca', $initialUnread + 1);
    }

    /**
     * User tidak melihat notifikasi milik user lain.
     */
    public function test_user_does_not_see_other_users_private_notifications(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $operator = User::where('username', 'operator')->first();

        $analisis = AnalisisSiswa::create([
            'id_siswa' => 1,
            'id_semester' => 1,
            'status' => 'Perlu Perhatian',
            'skor_total' => 65.5,
            'rekomendasi' => 'Perlu bimbingan',
            'tanggal_analisis' => now(),
        ]);

        Notifikasi::create([
            'id_analisis' => $analisis->id_analisis,
            'id_user' => $operator->id_user,
            'pesan' => 'Pesan Rahasia Operator Sistem',
            'sudah_dibaca' => 0,
            'tanggal_kirim' => now(),
        ]);

        $response = $this->actingAs($wali)->get(route('wali.notifikasi'));

        $response->assertStatus(200);
        $response->assertDontSee('Pesan Rahasia Operator Sistem');
    }

    /**
     * Menandai notifikasi sebagai terbaca (mark as read).
     */
    public function test_user_can_mark_notification_as_read(): void
    {
        $wali = User::where('username', 'walikelas')->first();

        $analisis = AnalisisSiswa::create([
            'id_siswa' => 1,
            'id_semester' => 1,
            'status' => 'Perlu Perhatian',
            'skor_total' => 65.5,
            'rekomendasi' => 'Perlu bimbingan',
            'tanggal_analisis' => now(),
        ]);

        $notif = Notifikasi::create([
            'id_analisis' => $analisis->id_analisis,
            'id_user' => $wali->id_user,
            'pesan' => 'Pesan belum dibaca',
            'sudah_dibaca' => 0,
            'tanggal_kirim' => now(),
        ]);

        $response = $this->actingAs($wali)->post(route('wali.notifikasi.read', $notif->id_notifikasi));

        $response->assertRedirect();
        $this->assertEquals(1, $notif->fresh()->sudah_dibaca);
    }
}
