<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Ai\AiInsightService;
use Database\Seeders\DummyDataSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SimulasiDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAnalysisControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        $this->seed(DummyDataSeeder::class);
        $this->seed(SimulasiDataSeeder::class);
    }

    public function test_operator_can_access_ai_analysis_page_with_active_run(): void
    {
        $operator = User::where('role', 'Operator')->first();

        Http::fake([
            '*/analyze' => Http::response([
                'model_version' => '1.0.0-kmeans-iforest',
                'waktu_proses' => '0.05s',
                'status' => 'success',
                'total_siswa' => 1,
                'klaster' => [
                    'k' => 1,
                    'silhouette_score' => 0.65,
                    'ringkasan' => [
                        [
                            'cluster_id' => 0,
                            'label' => 'Konsisten Unggul',
                            'deskripsi' => 'Performa akademik sangat baik.',
                            'jumlah_siswa' => 1,
                            'persentase' => 100.0,
                            'rata_rata_fitur' => ['rata_nilai_akhir' => 90.0],
                        ],
                    ],
                ],
                'hasil' => [
                    [
                        'id_siswa' => 1,
                        'cluster_id' => 0,
                        'cluster_label' => 'Konsisten Unggul',
                        'cluster_distance' => 0.15,
                        'pca_x' => 1.2,
                        'pca_y' => -0.5,
                        'anomaly_score' => 0.12,
                        'is_anomaly' => false,
                        'alasan' => [],
                        'slope_nilai' => 0.2,
                        'prediksi_nilai' => 91.0,
                        'slope_kehadiran' => 0.0,
                        'prediksi_kehadiran' => 98.0,
                        'berpotensi_turun' => false,
                        'data_cukup' => true,
                    ],
                ],
            ], 200),
        ]);

        app(AiInsightService::class)->runAnalysis(2);

        $response = $this->actingAs($operator)->get(route('operator.early-warning.ai', ['semester_id' => 2]));

        $response->assertStatus(200);
        $response->assertSee('Analisis AI');
        $response->assertSee('Peta Proyeksi 2D Pola Siswa');
    }

    public function test_operator_can_access_ai_evaluasi_page(): void
    {
        $operator = User::where('role', 'Operator')->first();

        Http::fake([
            '*/evaluate' => Http::response([
                'status' => 'success',
                'clustering' => ['ari' => 0.45, 'purity' => 0.72],
                'anomali_p8' => ['precision' => 0.85, 'recall' => 0.90, 'f1' => 0.87],
                'tren_prediksi' => ['mae_model' => 1.8, 'mae_baseline_naive' => 3.2, 'peningkatan_akurasi_pct' => 43.75],
            ], 200),
        ]);

        $response = $this->actingAs($operator)->get(route('operator.early-warning.ai.evaluasi', ['semester_id' => 2]));

        $response->assertStatus(200);
        $response->assertSee('Evaluasi', false);
        $response->assertSee('Adjusted Rand Index (ARI)');
    }

    public function test_wali_kelas_can_access_their_class_ai_page(): void
    {
        $wali = User::where('role', 'Wali Kelas')->first();

        $response = $this->actingAs($wali)->get(route('wali.early-warning.ai', ['semester_id' => 2]));

        $response->assertStatus(200);
        $response->assertSee('Analisis AI');
    }

    public function test_wali_kelas_forbidden_from_evaluasi_page(): void
    {
        $wali = User::where('role', 'Wali Kelas')->first();

        $response = $this->actingAs($wali)->get(route('operator.early-warning.ai.evaluasi'));

        $response->assertRedirect(route('wali.dashboard'));
    }

    public function test_guru_mapel_forbidden_from_early_warning_and_ai(): void
    {
        $guru = User::where('role', 'Guru Mapel')->first();

        $response = $this->actingAs($guru)->get(route('operator.early-warning.ai'));

        $response->assertRedirect(route('guru.dashboard'));
    }
}
