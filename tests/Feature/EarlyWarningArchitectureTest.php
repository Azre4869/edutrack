<?php

namespace Tests\Feature;

use App\Models\AnalisisLog;
use App\Models\AnalisisSiswa;
use App\Models\KriteriaAnalisis;
use App\Models\Notifikasi;
use App\Models\Siswa;
use App\Models\User;
use App\Services\EarlyWarning\EarlyWarningService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EarlyWarningArchitectureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MasterDataSeeder::class);
        $this->seed(\Database\Seeders\DummyDataSeeder::class);
    }

    public function test_early_warning_service_creates_execution_log(): void
    {
        $service = app(EarlyWarningService::class);
        $log = $service->runAnalysis(semesterId: 2, pemicu: 'Manual');

        $this->assertInstanceOf(AnalisisLog::class, $log);
        $this->assertEquals('Success', $log->status);
        $this->assertGreaterThan(0, $log->jumlah_siswa_diproses);
        $this->assertDatabaseHas('analisis_log', [
            'id_log' => $log->id_log,
            'status' => 'Success',
            'pemicu' => 'Manual',
        ]);
    }

    public function test_artisan_command_ews_run_executes_successfully(): void
    {
        $exitCode = Artisan::call('ews:run', ['--semester' => 2]);
        $this->assertEquals(0, $exitCode);

        $latestLog = AnalisisLog::latest('id_log')->first();
        $this->assertNotNull($latestLog);
        $this->assertEquals('Terjadwal', $latestLog->pemicu);
        $this->assertEquals('Success', $latestLog->status);
    }

    public function test_operator_can_trigger_early_warning_run_via_web(): void
    {
        $operator = User::where('role', 'Operator')->first();

        $response = $this->actingAs($operator)->post(route('operator.early-warning.run'), [
            'semester_id' => 2,
            'kelas_id' => 1,
        ]);

        $response->assertRedirect(route('operator.early-warning', ['kelas_id' => 1, 'semester_id' => 2]));
        $response->assertSessionHas('success');
    }

    public function test_operator_can_access_and_update_kriteria_analysis(): void
    {
        $operator = User::where('role', 'Operator')->first();

        $response = $this->actingAs($operator)->get(route('operator.early-warning.kriteria'));
        $response->assertStatus(200);
        $response->assertSeeText('Konfigurasi Parameter dan Ambang Batas EWS');

        $kriteria = KriteriaAnalisis::first();
        $this->assertNotNull($kriteria);

        $updateResponse = $this->actingAs($operator)->put(route('operator.early-warning.kriteria.update', $kriteria->id_kriteria), [
            'nama_kriteria' => 'Kriteria Custom Updated',
            'nilai_ambang' => 12.50,
            'bobot' => 1.50,
            'parameter' => 'Parameter uji coba',
            'aktif' => 1,
        ]);

        $updateResponse->assertRedirect(route('operator.early-warning.kriteria'));
        $this->assertDatabaseHas('kriteria_analisis', [
            'id_kriteria' => $kriteria->id_kriteria,
            'nama_kriteria' => 'Kriteria Custom Updated',
            'nilai_ambang' => 12.50,
            'bobot' => 1.50,
        ]);
    }

    public function test_guru_mapel_cannot_access_early_warning_or_kriteria(): void
    {
        $guru = User::where('role', 'Guru Mapel')->first();

        $responseEws = $this->actingAs($guru)->get(route('operator.early-warning'));
        $responseEws->assertRedirect(route('guru.dashboard'));

        $responseKriteria = $this->actingAs($guru)->get(route('operator.early-warning.kriteria'));
        $responseKriteria->assertRedirect(route('guru.dashboard'));
    }

    public function test_notification_generated_for_high_risk_students(): void
    {
        $service = app(EarlyWarningService::class);
        $service->runAnalysis(semesterId: 2, pemicu: 'Manual');

        // Pastikan tabel notifikasi berisi record EWS_ALERT
        $this->assertDatabaseHas('notifikasi', [
            'tipe' => 'EWS_ALERT',
        ]);
    }
}
