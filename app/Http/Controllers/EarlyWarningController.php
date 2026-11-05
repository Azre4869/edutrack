<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Semester;
use App\Services\Ai\AiInsightService;
use App\Services\EarlyWarning\EarlyWarningService;
use Illuminate\Http\Request;

class EarlyWarningController extends Controller
{
    public function __construct(
        protected EarlyWarningService $ewsService,
        protected AiInsightService $aiService
    ) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $semesterList = Semester::orderBy('id_semester')->get();

        // --- Data-Level Access Control ---
        if ($user && $user->isWaliKelas()) {
            $kelasBinaan = $user->requireKelasBinaan();
            $kelasList = collect([$kelasBinaan]);
            $defaultKelasId = $kelasBinaan->id_kelas;
            $selectedKelasId = $kelasBinaan->id_kelas; // Wali hanya bisa melihat kelasnya sendiri
        } else {
            // Operator: akses penuh
            $kelasList = Kelas::orderBy('nama_kelas')->get();
            $defaultKelasId = $kelasList->first()->id_kelas ?? 1;
            $selectedKelasId = (int) $request->query('kelas_id', $defaultKelasId);
        }

        $selectedSemesterId = (int) $request->query('semester_id', 2);
        $selectedTahunAjaran = $request->query('tahun_ajaran', '2026/2027');
        $search = $request->query('search');
        $selectedId = (int) $request->query('selected_id', 0);

        // Panggil EarlyWarningService
        $viewData = $this->ewsService->getAnalysisViewData(
            selectedKelasId: $selectedKelasId,
            selectedSemesterId: $selectedSemesterId,
            search: $search,
            selectedId: $selectedId
        );

        $stats = $viewData['stats'];
        $siswaEarlyWarning = $viewData['siswaEarlyWarning'];
        $selectedSiswa = $viewData['selectedSiswa'];
        $lastLog = $viewData['lastLog'];

        // Ambil hasil AI run terbaru untuk semester ini
        $latestAiRun = $this->aiService->getLatestSuccessfulRun($selectedSemesterId);

        if ($latestAiRun) {
            $studentIds = array_column($siswaEarlyWarning, 'id_siswa');
            if ($selectedSiswa && !in_array($selectedSiswa['id_siswa'], $studentIds)) {
                $studentIds[] = $selectedSiswa['id_siswa'];
            }

            $insightsMap = $this->aiService->getInsightsForSiswaIds($studentIds, $selectedSemesterId);

            foreach ($siswaEarlyWarning as &$st) {
                $st['ai_insight'] = $insightsMap->get($st['id_siswa']);
            }
            unset($st);

            if ($selectedSiswa) {
                $selectedSiswa['ai_insight'] = $insightsMap->get($selectedSiswa['id_siswa']);
            }
        }

        return view('early-warning.index', compact(
            'kelasList',
            'semesterList',
            'selectedKelasId',
            'selectedSemesterId',
            'selectedTahunAjaran',
            'search',
            'stats',
            'siswaEarlyWarning',
            'selectedSiswa',
            'lastLog',
            'latestAiRun'
        ));
    }

    /**
     * Trigger manual eksekusi EWS
     */
    public function run(Request $request)
    {
        $user = auth()->user();
        $semesterId = (int) $request->input('semester_id', 2);
        $kelasId = $request->input('kelas_id') ? (int) $request->input('kelas_id') : null;

        // Wali Kelas hanya bisa menjalankan untuk kelas binaannya
        if ($user && $user->isWaliKelas()) {
            $kelasBinaan = $user->requireKelasBinaan();
            $kelasId = $kelasBinaan->id_kelas;
        }

        $log = $this->ewsService->runAnalysis(
            semesterId: $semesterId,
            kelasId: $kelasId,
            pemicu: 'Manual',
            userId: $user?->id_user
        );

        // Jika AI diaktifkan, otomatis jalankan analisis AI sinkron dengan log EWS
        if (config('services.ai.enabled', true)) {
            $this->aiService->runAnalysis(
                semesterId: $semesterId,
                idLog: $log->id_log,
                kelasId: $kelasId
            );
        }

        $rolePrefix = $user && $user->isOperator() ? 'operator' : 'wali';

        return redirect()->route($rolePrefix . '.early-warning', array_filter([
            'kelas_id' => $kelasId,
            'semester_id' => $semesterId,
        ]))->with('success', "Analisis Peringatan Dini berhasil dijalankan. {$log->jumlah_siswa_diproses} siswa diproses, {$log->total_berisiko} siswa terindikasi perlu perhatian.");
    }
}
