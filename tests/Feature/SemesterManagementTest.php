<?php

namespace Tests\Feature;

use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemesterManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
        $this->seed(\Database\Seeders\SemesterAktifSeeder::class);
    }

    public function test_operator_can_view_semester_management_page(): void
    {
        $operator = User::where('username', 'operator')->first();

        $response = $this->actingAs($operator)->get(route('operator.semester'));

        $response->assertOk();
        $response->assertSee('Data Semester & Tahun Ajaran');
        $response->assertSee('2026/2027');
        $response->assertSee('Aktif Berjalan');
    }

    public function test_operator_can_store_new_semester_and_set_it_active(): void
    {
        $operator = User::where('username', 'operator')->first();

        $response = $this->actingAs($operator)->post(route('operator.semester.store'), [
            'nama_semester' => 'Ganjil',
            'tahun_ajaran'  => '2027/2028',
            'is_active'     => '1',
        ]);

        $response->assertSessionHas('success');

        // Pastikan semester baru aktif dan semester lama nonaktif
        $newSem = Semester::where('tahun_ajaran', '2027/2028')->first();
        $this->assertNotNull($newSem);
        $this->assertTrue($newSem->is_active);

        $oldSem = Semester::find(2);
        $this->assertFalse($oldSem->is_active);
    }

    public function test_operator_can_toggle_active_semester(): void
    {
        $operator = User::where('username', 'operator')->first();

        // Aktifkan semester 1 (Ganjil 2026/2027)
        $response = $this->actingAs($operator)->post(route('operator.semester.set-active', 1));

        $response->assertSessionHas('success');

        $sem1 = Semester::find(1);
        $sem2 = Semester::find(2);

        $this->assertTrue($sem1->is_active);
        $this->assertFalse($sem2->is_active);
    }

    public function test_wali_and_guru_cannot_access_semester_management(): void
    {
        $wali = User::where('username', 'walikelas')->first();
        $this->actingAs($wali)->get(route('operator.semester'))->assertRedirect(route('wali.dashboard'));

        $guru = User::where('username', 'gurumapel')->first();
        $this->actingAs($guru)->get(route('operator.semester'))->assertRedirect(route('guru.dashboard'));
    }
}
