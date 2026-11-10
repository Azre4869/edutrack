<?php

namespace App\Services\Ai;

use App\Models\AiInsight;
use App\Models\AiRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiInsightService
{
    public function __construct(
        protected AiFeatureExtractor $extractor
    ) {}

    /**
     * Jalankan analisis AI untuk satu semester (bisa dihubungkan dengan id_log EWS).
     */
    public function runAnalysis(int $semesterId, ?int $idLog = null, ?int $kelasId = null): array
    {
        $enabled = config('services.ai.enabled', true);
        $baseUrl = rtrim(config('services.ai.url', 'http://127.0.0.1:8001'), '/');
        $timeout = (int) config('services.ai.timeout', 60);

        if (!$enabled) {
            return [
                'success' => false,
                'message' => 'Layanan Analisis AI dinonaktifkan di konfigurasi.',
                'run' => null,
            ];
        }

        // Ekstraksi data fitur siswa dari database
        $payload = $this->extractor->extractForCohort($semesterId, $kelasId);

        if (empty($payload['siswa'])) {
            return [
                'success' => false,
                'message' => 'Tidak ada data siswa untuk dianalisis.',
                'run' => null,
            ];
        }

        $run = AiRun::create([
            'id_log' => $idLog,
            'id_semester' => $semesterId,
            'model_version' => '1.0.0-kmeans-iforest',
            'parameter' => [
                'kkm' => $payload['kkm'],
                'contamination' => $payload['contamination'],
                'kelas_id' => $kelasId,
            ],
            'status' => 'Running',
            'dibuat_pada' => now(),
        ]);

        try {
            $response = Http::timeout($timeout)
                ->asJson()
                ->acceptJson()
                ->post("{$baseUrl}/analyze", $payload);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    "Service AI merespons dengan HTTP {$response->status()}: " . $response->body()
                );
            }

            $data = $response->json();
            $hasilList = $data['hasil'] ?? [];
            $klasterOverview = $data['klaster'] ?? [];
            $clusterSummary = $klasterOverview['ringkasan'] ?? [];

            // Simpan hasil insight per siswa dalam transaksi
            DB::transaction(function () use ($run, $semesterId, $hasilList, $clusterSummary, $klasterOverview) {
                foreach ($hasilList as $item) {
                    AiInsight::create([
                        'id_run' => $run->id,
                        'id_siswa' => $item['id_siswa'],
                        'id_semester' => $semesterId,
                        'cluster_id' => $item['cluster_id'] ?? 0,
                        'cluster_label' => $item['cluster_label'] ?? 'Tidak Terdefinisi',
                        'cluster_distance' => $item['cluster_distance'] ?? null,
                        'pca_x' => $item['pca_x'] ?? null,
                        'pca_y' => $item['pca_y'] ?? null,
                        'anomaly_score' => $item['anomaly_score'] ?? 0.0,
                        'is_anomaly' => (bool) ($item['is_anomaly'] ?? false),
                        'alasan' => $item['alasan'] ?? [],
                        'slope_nilai' => $item['slope_nilai'] ?? 0.0,
                        'prediksi_nilai' => $item['prediksi_nilai'] ?? 0.0,
                        'slope_kehadiran' => $item['slope_kehadiran'] ?? 0.0,
                        'prediksi_kehadiran' => $item['prediksi_kehadiran'] ?? 0.0,
                        'berpotensi_turun' => (bool) ($item['berpotensi_turun'] ?? false),
                        'data_cukup' => (bool) ($item['data_cukup'] ?? true),
                        'dihitung_pada' => now(),
                    ]);
                }

                $run->update([
                    'status' => 'Success',
                    'metrik' => [
                        'k' => $klasterOverview['k'] ?? count($clusterSummary),
                        'silhouette_score' => $klasterOverview['silhouette_score'] ?? 0.0,
                        'cluster_summary' => $clusterSummary,
                        'total_diproses' => count($hasilList),
                        'total_anomali' => collect($hasilList)->where('is_anomaly', true)->count(),
                    ],
                ]);
            });

            return [
                'success' => true,
                'message' => 'Analisis AI berhasil diproses.',
                'run' => $run->fresh(['insights']),
                'total_diproses' => count($hasilList),
            ];
        } catch (Throwable $e) {
            Log::warning("AI Service call failed: " . $e->getMessage());

            $run->update([
                'status' => 'Failed',
                'metrik' => ['error' => $e->getMessage()],
            ]);

            return [
                'success' => false,
                'message' => 'Gagal menghubungi service AI: ' . $e->getMessage(),
                'run' => $run,
            ];
        }
    }

    /**
     * Dapatkan run AI terakhir yang sukses untuk semester ini.
     */
    public function getLatestSuccessfulRun(int $semesterId): ?AiRun
    {
        return AiRun::where('id_semester', $semesterId)
            ->where('status', 'Success')
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * Dapatkan insight AI terbaru untuk daftar siswa.
     */
    public function getInsightsForSiswaIds(array $siswaIds, int $semesterId): Collection
    {
        $latestRun = $this->getLatestSuccessfulRun($semesterId);
        if (!$latestRun) {
            return collect();
        }

        return AiInsight::where('id_run', $latestRun->id)
            ->whereIn('id_siswa', $siswaIds)
            ->get()
            ->keyBy('id_siswa');
    }

    /**
     * Panggil evaluasi model ke AI service (/evaluate).
     */
    public function evaluateModel(int $semesterId): array
    {
        $baseUrl = rtrim(config('services.ai.url', 'http://127.0.0.1:8001'), '/');
        $timeout = max(60, (int) config('services.ai.timeout', 60));

        $payload = $this->extractor->extractForCohort($semesterId);

        // Sisipkan mapping persona_labels dari CSV jika tersedia di repo Laravel
        $csvCandidates = [
            database_path('seeders/data/persona_label.csv'),
            base_path('ai-service/data/persona_label.csv'),
        ];

        foreach ($csvCandidates as $csvPath) {
            if (file_exists($csvPath) && ($handle = fopen($csvPath, 'r')) !== false) {
                $header = fgetcsv($handle);
                $idIdx = is_array($header) ? array_search('id_siswa', $header) : false;
                $nisIdx = is_array($header) ? array_search('nis', $header) : false;
                $personaIdx = is_array($header) ? array_search('persona', $header) : false;

                // Ambil mapping NIS => id_siswa aktual dari database siswa
                $nisToIdMap = \App\Models\Siswa::pluck('id_siswa', 'nis')->toArray();

                if ($personaIdx !== false && ($idIdx !== false || $nisIdx !== false)) {
                    $personaLabels = [];
                    while (($row = fgetcsv($handle)) !== false) {
                        $nis = ($nisIdx !== false && isset($row[$nisIdx])) ? trim($row[$nisIdx]) : null;
                        $persona = isset($row[$personaIdx]) ? trim($row[$personaIdx]) : null;

                        if ($persona) {
                            // Prioritaskan ID aktual dari database berdasarkan NIS unik
                            $actualId = ($nis && isset($nisToIdMap[$nis]))
                                ? $nisToIdMap[$nis]
                                : ($idIdx !== false && isset($row[$idIdx]) ? trim($row[$idIdx]) : null);

                            if ($actualId) {
                                $personaLabels[(string) $actualId] = (string) $persona;
                            }
                        }
                    }
                    if (!empty($personaLabels)) {
                        $payload['persona_labels'] = $personaLabels;
                        fclose($handle);
                        break;
                    }
                }
                fclose($handle);
            }
        }

        try {
            $response = Http::timeout($timeout)
                ->asJson()
                ->acceptJson()
                ->post("{$baseUrl}/evaluate", $payload);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "Service merespons HTTP {$response->status()}: " . $response->body(),
                ];
            }

            return [
                'success' => true,
                'data' => $response->json(),
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
