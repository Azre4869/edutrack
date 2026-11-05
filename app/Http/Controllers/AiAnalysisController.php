<?php

namespace App\Http\Controllers;

use App\Models\AiInsight;
use App\Models\AiRun;
use App\Models\Kelas;
use App\Models\Semester;
use App\Services\Ai\AiInsightService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class AiAnalysisController extends Controller
{
    public function __construct(
        protected AiInsightService $aiService
    ) {}

    /**
     * Cek apakah AI service aktif (di-cache 60 detik agar tidak spam)
     */
    private function checkAiServiceHealth(): bool
    {
        return Cache::remember('ai_health_status', 60, function () {
            try {
                $baseUrl = rtrim(config('services.ai.url', 'http://127.0.0.1:8001'), '/');
                $response = Http::timeout(2)->get("{$baseUrl}/health");
                return $response->ok();
            } catch (\Throwable) {
                return false;
            }
        });
    }

    /**
     * Halaman Utama Analisis AI: Klastering PCA 2D, Anomali, dan Pola Belajar
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $semesterList = Semester::orderBy('id_semester')->get();
        $selectedSemesterId = (int) $request->query('semester_id', 2);

        // DLAC Kelas
        if ($user && $user->isWaliKelas()) {
            $kelasBinaan = $user->requireKelasBinaan();
            $kelasList = collect([$kelasBinaan]);
            $selectedKelasId = $kelasBinaan->id_kelas;
        } else {
            $kelasList = Kelas::orderBy('nama_kelas')->get();
            $selectedKelasId = $request->query('kelas_id') ? (int) $request->query('kelas_id') : null;
        }

        $latestRun = $this->aiService->getLatestSuccessfulRun($selectedSemesterId);
        $aiServiceActive = $this->checkAiServiceHealth();

        $insights = collect();
        $clusterSummary = [];
        $anomalies = collect();
        $pcaPoints = [];

        if ($latestRun) {
            $query = AiInsight::with(['siswa.kelas'])
                ->where('id_run', $latestRun->id)
                ->where('id_semester', $selectedSemesterId);

            if ($selectedKelasId) {
                $query->whereHas('siswa', function ($q) use ($selectedKelasId) {
                    $q->where('id_kelas', $selectedKelasId);
                });
            }

            $insights = $query->get();
            $clusterSummary = $latestRun->metrik['cluster_summary'] ?? [];

            $pcaPoints = $insights->map(function ($ins) {
                return [
                    'x' => (float) ($ins->pca_x ?? 0.0),
                    'y' => (float) ($ins->pca_y ?? 0.0),
                    'id_siswa' => $ins->id_siswa,
                    'nama' => $ins->siswa->nama ?? "Siswa #{$ins->id_siswa}",
                    'nis' => $ins->siswa->nis ?? '-',
                    'kelas' => $ins->siswa->kelas->nama_kelas ?? '-',
                    'cluster_id' => $ins->cluster_id,
                    'cluster_label' => $ins->cluster_label,
                    'is_anomaly' => (bool) $ins->is_anomaly,
                    'anomaly_score' => (float) $ins->anomaly_score,
                    'berpotensi_turun' => (bool) $ins->berpotensi_turun,
                    'slope_nilai' => (float) $ins->slope_nilai,
                    'prediksi_nilai' => (float) $ins->prediksi_nilai,
                ];
            })->values()->toArray();

            $anomalies = $insights->where('is_anomaly', true)->sortByDesc('anomaly_score')->values();
        }

        return view('early-warning.ai-analysis', compact(
            'kelasList',
            'semesterList',
            'selectedKelasId',
            'selectedSemesterId',
            'latestRun',
            'insights',
            'clusterSummary',
            'anomalies',
            'pcaPoints',
            'aiServiceActive'
        ));
    }

    /**
     * Trigger manual eksekusi Analisis AI oleh Operator
     */
    public function run(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->isOperator()) {
            abort(403, 'Hanya Operator yang berwenang menjalankan ulang Analisis AI secara global.');
        }

        $semesterId = (int) $request->input('semester_id', 2);
        $kelasId = $request->input('kelas_id') ? (int) $request->input('kelas_id') : null;

        $result = $this->aiService->runAnalysis(
            semesterId: $semesterId,
            idLog: null,
            kelasId: $kelasId
        );

        // Invalidasi cache health setelah run
        Cache::forget('ai_health_status');

        if (!$result['success']) {
            return redirect()->back()->with('error', 'Analisis AI Gagal: ' . $result['message']);
        }

        return redirect()->back()->with(
            'success',
            "Analisis AI berhasil dijalankan! {$result['total_diproses']} siswa diproses, k={$result['run']->metrik['k']}, anomali={$result['run']->metrik['total_anomali']}."
        );
    }

    /**
     * Halaman Evaluasi Model Machine Learning
     * TIDAK memanggil /evaluate secara otomatis — baca dari ai_run.metrik terbaru
     */
    public function evaluasi(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->isOperator()) {
            abort(403, 'Halaman Evaluasi Model hanya dapat diakses oleh Operator.');
        }

        $semesterList = Semester::orderBy('id_semester')->get();
        $selectedSemesterId = (int) $request->query('semester_id', 2);
        $aiServiceActive = $this->checkAiServiceHealth();

        // Baca data evaluasi tersimpan dari ai_run.metrik (tidak panggil /evaluate otomatis)
        $latestRun = AiRun::where('status', 'Success')
            ->orderBy('id', 'desc')
            ->first();

        $evalData  = null;
        $evalError = session('eval_error', null);
        $evalRunAt = null;

        if ($latestRun && isset($latestRun->metrik['eval'])) {
            $evalData  = $latestRun->metrik['eval'];
            $evalRunAt = $latestRun->updated_at;
        }

        return view('early-warning.ai-evaluasi', compact(
            'semesterList',
            'selectedSemesterId',
            'evalData',
            'evalError',
            'evalRunAt',
            'latestRun',
            'aiServiceActive'
        ));
    }

    /**
     * POST: Jalankan evaluasi ulang (memanggil /evaluate ke AI service)
     * Hanya dipicu oleh tombol — bukan otomatis saat halaman dibuka
     */
    public function runEvaluasi(Request $request)
    {
        $user = auth()->user();
        if ($user && !$user->isOperator()) {
            abort(403, 'Hanya Operator yang berwenang menjalankan evaluasi model.');
        }

        $selectedSemesterId = (int) $request->input('semester_id', 2);

        $evalResponse = $this->aiService->evaluateModel($selectedSemesterId);

        if (!$evalResponse['success']) {
            return redirect()
                ->route('operator.early-warning.ai.evaluasi', ['semester_id' => $selectedSemesterId])
                ->with('eval_error', $evalResponse['error'] ?? 'Gagal menghubungi service AI.');
        }

        // Simpan hasil evaluasi ke ai_run.metrik terbaru
        $latestRun = AiRun::where('status', 'Success')->orderBy('id', 'desc')->first();
        if ($latestRun) {
            $metrik = $latestRun->metrik ?? [];
            $metrik['eval'] = $evalResponse['data'];
            $latestRun->update(['metrik' => $metrik]);
        }

        Cache::forget('ai_health_status');

        return redirect()
            ->route('operator.early-warning.ai.evaluasi', ['semester_id' => $selectedSemesterId])
            ->with('eval_success', 'Evaluasi model berhasil dijalankan.');
    }
}
