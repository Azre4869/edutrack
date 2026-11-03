<?php

namespace App\Http\Controllers;

use App\Models\CatatanWali;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;

class CatatanController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $semesterList = Semester::orderBy('id_semester')->get();

        // Data-Level Access Control
        if ($user && $user->isWaliKelas()) {
            $kelasBinaan = $user->requireKelasBinaan();
            $kelasList = collect([$kelasBinaan]);
            $defaultKelasId = $kelasBinaan->id_kelas;
            $selectedKelasId = $kelasBinaan->id_kelas;
        } else {
            $kelasList = Kelas::orderBy('nama_kelas')->get();
            $defaultKelasId = $kelasList->first()->id_kelas ?? 1;
            $selectedKelasId = $request->query('kelas_id', $defaultKelasId);
        }

        $selectedSemesterId = $request->query('semester_id', 2);
        $search = $request->query('search');

        $query = Siswa::where('id_kelas', $selectedKelasId);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        $siswaList = $query->orderBy('nama')->get();

        $catatanMap = CatatanWali::whereIn('id_siswa', $siswaList->pluck('id_siswa'))
            ->where('id_semester', $selectedSemesterId)
            ->get()
            ->keyBy('id_siswa');

        return view('rapor.catatan', compact(
            'kelasList',
            'semesterList',
            'selectedKelasId',
            'selectedSemesterId',
            'search',
            'siswaList',
            'catatanMap'
        ));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isWaliKelas()) {
            $user->requireKelasBinaan();
        }

        $semesterId = $request->input('semester_id', 2);
        $catatanEntries = $request->input('catatan', []);

        foreach ($catatanEntries as $siswaId => $data) {
            $siswa = Siswa::find($siswaId);
            if (!$siswa) continue;

            if ($user && !$user->canAccessSiswa($siswa)) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengubah catatan siswa di luar kelas binaan.');
            }

            $spiritual = trim($data['sikap_spiritual'] ?? '');
            $sosial    = trim($data['sikap_sosial'] ?? '');
            $wali      = trim($data['catatan_wali'] ?? '');
            $kenaikan  = $data['status_kenaikan'] ?? 'Naik';

            // Jika ada minimal satu data terisi atau sudah ada record sebelumnya
            if ($spiritual !== '' || $sosial !== '' || $wali !== '' || isset($data['status_kenaikan'])) {
                CatatanWali::updateOrCreate(
                    [
                        'id_siswa' => $siswaId,
                        'id_semester' => $semesterId,
                    ],
                    [
                        'sikap_spiritual' => $spiritual ?: null,
                        'sikap_sosial'    => $sosial ?: null,
                        'catatan_wali'    => $wali ?: null,
                        'status_kenaikan' => $kenaikan,
                    ]
                );
            }
        }

        return back()->with('success', 'Catatan sikap, catatan wali kelas, dan status kenaikan kelas berhasil disimpan.');
    }
}
