<?php

namespace Tests\Feature;

use App\Models\AiInsight;
use App\Models\AiRun;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Ai\AiInsightService;
use Database\Seeders\DummyDataSeeder;
use Database\Seeders\MasterDataSeeder;
use Database\Seeders\SimulasiDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiInsightTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MasterDataSeeder::class);
        $this->seed(DummyDataSeeder::class);
        $this->seed(SimulasiDataSeeder::class);
    }

    public function test_ai_insight_service_handles_successful_analysis(): void
    {
        Http::fake([
            '*/analyze' => Http::response([
                'model_version' => '1.0.0-kmeans-iforest',
                'waktu_proses' => '0.05s',
                'status' => 'success',
                'total_siswa' => 2,
                'klaster' => [
                    'k' => 2,
                    'silhouette_score' => 0.65,
                    'ringkasan' => [
                        [
                            'cluster_id' => 0,
                            'label' => 'Konsisten Unggul',
                            'deskripsi' => 'Performa akademik sangat baik dan kehadiran konsisten tinggi.',
                            'jumlah_siswa' => 1,
                            'persentase' => 50.0,
                            'rata_rata_fitur' => ['rata_nilai_akhir' => 90.0],
                        ],
                        [
                            'cluster_id' => 1,
                            'label' => 'Rentan Ganda',
                            'deskripsi' => 'Kombinasi ketidakhadiran berulang dan capaian nilai di bawah KKM.',
                            'jumlah_siswa' => 1,
                            'persentase' => 50.0,
                            'rata_rata_fitur' => ['rata_nilai_akhir' => 65.0],
                        ],
                    ],
                ],
                'hasil' => [
                    [
                        'id_siswa' => 1,
                        'cluster_id' => 1,
                        'cluster_label' => 'Rentan Ganda',
                        'cluster_distance' => 0.45,
                        'pca_x' => -1.2,
                        'pca_y' => 0.8,
                        'anomaly_score' => 0.62,
                        'is_anomaly' => true,
                        'alasan' => [
                            [
                                'fitur' => 'jumlah_alpha',
                                'nilai' => 7,
                                'rata_kelas' => 1.2,
                                'z' => 2.8,
                                'teks_penjelasan' => 'Jumlah alpha (7 hari) melebihi batas wajar kelas (rata-rata: 1.2 hari).',
                            ],
                        ],
                        'slope_nilai' => -0.5,
                        'prediksi_nilai' => 64.5,
                        'slope_kehadiran' => -1.2,
                        'prediksi_kehadiran' => 82.0,
                        'berpotensi_turun' => true,
                        'data_cukup' => true,
                    ],
                ],
            ], 200),
        ]);

        /** @var AiInsightService $service */
        $service = app(AiInsightService::class);
        $result = $service->runAnalysis(semesterId: 2);

        $this->assertTrue($result['success']);
        $this->assertDatabaseHas('ai_run', [
            'id_semester' => 2,
            'status' => 'Success',
            'model_version' => '1.0.0-kmeans-iforest',
        ]);
        $this->assertDatabaseHas('ai_insight', [
            'id_siswa' => 1,
            'cluster_label' => 'Rentan Ganda',
            'is_anomaly' => 1,
            'berpotensi_turun' => 1,
        ]);
    }

    public function test_ai_insight_service_handles_service_failure_gracefully(): void
    {
        Http::fake([
            '*/analyze' => Http::response(['detail' => 'Internal server error'], 500),
        ]);

        /** @var AiInsightService $service */
        $service = app(AiInsightService::class);
        $result = $service->runAnalysis(semesterId: 2);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('500', $result['message']);
        $this->assertDatabaseHas('ai_run', [
            'id_semester' => 2,
            'status' => 'Failed',
        ]);
    }

    public function test_ai_insight_service_handles_service_timeout_gracefully(): void
    {
        Http::fake([
            '*/analyze' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        /** @var AiInsightService $service */
        $service = app(AiInsightService::class);
        $result = $service->runAnalysis(semesterId: 2);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection timed out', $result['message']);
        $this->assertDatabaseHas('ai_run', [
            'id_semester' => 2,
            'status' => 'Failed',
        ]);
    }

    public function test_ai_insight_service_respects_disabled_flag(): void
    {
        Config::set('services.ai.enabled', false);

        /** @var AiInsightService $service */
        $service = app(AiInsightService::class);
        $result = $service->runAnalysis(semesterId: 2);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('dinonaktifkan', $result['message']);
        $this->assertDatabaseCount('ai_run', 0);
    }
}
