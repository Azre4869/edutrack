<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCatatanRequest;
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

        $activeSemester = Semester::aktif();
        $selectedSemesterId = (int) $request->query('semester_id', $activeSemester?->id_semester ?? 1);
        $tahunAjaranList = Semester::getTahunAjaranList();
        $selectedTahunAjaran = $request->query('tahun_ajaran', $activeSemester?->tahun_ajaran ?? '2026/2027');
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
            'tahunAjaranList',
            'selectedTahunAjaran',
            'search',
            'siswaList',
            'catatanMap'
        ));
    }

    public function store(StoreCatatanRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        if ($user && $user->isWaliKelas()) {
            $user->requireKelasBinaan();
        }

        $semesterId = $validated['semester_id'];
        $catatanEntries = $validated['catatan'] ?? [];

        foreach ($catatanEntries as $siswaId => $data) {
            $siswa = Siswa::find($siswaId);
            if (!$siswa) continue;

            if ($user && !$user->canAccessSiswa($siswa)) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengubah catatan siswa di luar kelas binaan.');
            }

            $spiritual = trim($data['sikap_spiritual'] ?? '');
            $sosial    = trim($data['sikap_sosial'] ?? '');
            $wali      = trim($data['catatan_wali'] ?? '');

            $existing = CatatanWali::where('id_siswa', $siswaId)->where('id_semester', $semesterId)->first();

            // Ubah status hanya jika field dikirim dan tidak kosong; pertahankan jika tidak dikirim (B12)
            if (array_key_exists('status_kenaikan', $data) && !empty($data['status_kenaikan'])) {
                $kenaikan = $data['status_kenaikan'];
            } else {
                $kenaikan = $existing ? $existing->status_kenaikan : 'Naik';
            }

            // Jika ada minimal satu teks terisi atau sudah ada record sebelumnya
            if ($spiritual !== '' || $sosial !== '' || $wali !== '' || $existing) {
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
