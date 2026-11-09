<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePresensiRequest;
use App\Models\Absensi;
use App\Models\CatatanWali;
use App\Models\Ekstrakurikuler;
use App\Models\Kelas;
use App\Models\NilaiEkstrakurikuler;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    // ==================== PRESENSI KELAS ====================
    public function presensiKelas(Request $request)
    {
        $user = auth()->user();
        $semesterList = Semester::cachedAll();

        // Data-Level Access Control
        if ($user && $user->isWaliKelas()) {
            $kelasBinaan = $user->requireKelasBinaan();
            $kelasList = collect([$kelasBinaan]);
            $defaultKelasId = $kelasBinaan->id_kelas;
            $selectedKelasId = $kelasBinaan->id_kelas;
        } else {
            $kelasList = Kelas::cachedAll();
            $defaultKelasId = $kelasList->first()->id_kelas ?? 1;
            $selectedKelasId = $request->query('kelas_id', $defaultKelasId);
        }

        $activeSemester = Semester::aktif();
        $selectedSemesterId = (int) $request->query('semester_id', $activeSemester?->id_semester ?? 1);
        $tahunAjaranList = Semester::getTahunAjaranList();
        $selectedTahunAjaran = $request->query('tahun_ajaran', $activeSemester?->tahun_ajaran ?? '2026/2027');
        $selectedTanggal = $request->query('tanggal', now()->toDateString());
        $search = $request->query('search');

        $query = Siswa::where('id_kelas', $selectedKelasId);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        $siswaList = $query->orderBy('nama')->get();

        // Get existing attendance records for the selected date & semester
        $absensiMap = Absensi::whereIn('id_siswa', $siswaList->pluck('id_siswa'))
            ->where('tanggal', $selectedTanggal)
            ->where('id_semester', $selectedSemesterId)
            ->get()
            ->keyBy('id_siswa');

        return view('presensi.kelas', compact(
            'kelasList',
            'semesterList',
            'selectedKelasId',
            'selectedSemesterId',
            'selectedTanggal',
            'tahunAjaranList',
            'selectedTahunAjaran',
            'search',
            'siswaList',
            'absensiMap'
        ));
    }

    public function storePresensiKelas(StorePresensiRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        if ($user && $user->isWaliKelas()) {
            $user->requireKelasBinaan();
        }

        $tanggal = $validated['tanggal'];
        $semesterId = $validated['semester_id'];
        $statusList = $validated['status'] ?? [];

        foreach ($statusList as $siswaId => $status) {
            $siswa = Siswa::find($siswaId);
            if (!$siswa) continue;

            if ($user && !$user->canAccessSiswa($siswa)) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mencatat presensi siswa di kelas ini.');
            }

            Absensi::updateOrCreate(
                [
                    'id_siswa'    => $siswaId,
                    'tanggal'     => $tanggal,
                    'id_semester' => $semesterId,
                ],
                [
                    'status'      => $status,
                ]
            );
        }

        $role = auth()->user()->role ?? 'Wali Kelas';
        $prefix = $role === 'Operator' ? 'operator' : 'wali';
        $lastSiswa = isset($siswa) && $siswa ? $siswa : null;
        $kelasId = $validated['kelas_id'] ?? ($lastSiswa ? $lastSiswa->id_kelas : ($request->input('kelas_id') ?: 1));

        return redirect()->route($prefix . '.presensi-kelas', [
            'tanggal'     => $tanggal,
            'kelas_id'    => $kelasId,
            'semester_id' => $semesterId,
        ])->with('success', 'Presensi kelas tanggal ' . date('d/m/Y', strtotime($tanggal)) . ' berhasil disimpan.');
    }

    // ==================== PRESENSI EKSTRAKURIKULER ====================
    public function presensiEkskul(Request $request)
    {
        $user = auth()->user();
        $semesterList = Semester::cachedAll();
        $ekskulList = Ekstrakurikuler::cachedAll();

        // Data-Level Access Control
        if ($user && $user->isWaliKelas()) {
            $kelasBinaan = $user->requireKelasBinaan();
            $kelasList = collect([$kelasBinaan]);
            $defaultKelasId = $kelasBinaan->id_kelas;
            $selectedKelasId = $kelasBinaan->id_kelas;
        } else {
            $kelasList = Kelas::cachedAll();
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

        $nilaiEkskul = NilaiEkstrakurikuler::whereIn('id_siswa', $siswaList->pluck('id_siswa'))
            ->where('id_semester', $selectedSemesterId)
            ->get()
            ->groupBy('id_siswa');

        $catatanMap = CatatanWali::whereIn('id_siswa', $siswaList->pluck('id_siswa'))
            ->where('id_semester', $selectedSemesterId)
            ->get()
            ->keyBy('id_siswa');

        return view('presensi.ekskul', compact(
            'kelasList',
            'semesterList',
            'ekskulList',
            'selectedKelasId',
            'selectedSemesterId',
            'tahunAjaranList',
            'selectedTahunAjaran',
            'search',
            'siswaList',
            'nilaiEkskul',
            'catatanMap'
        ));
    }

    public function storePresensiEkskul(Request $request)
    {
        $user = auth()->user();
        if ($user && $user->isWaliKelas()) {
            $user->requireKelasBinaan();
        }

        $semesterId = (int) $request->input('semester_id', Semester::aktif()?->id_semester ?? 1);
        $ekskulEntries = $request->input('ekskul', []);
        $catatanEntries = $request->input('catatan', []);

        foreach ($ekskulEntries as $siswaId => $items) {
            $siswa = Siswa::find($siswaId);
            if (!$siswa) continue;

            if ($user && !$user->canAccessSiswa($siswa)) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk siswa di kelas ini.');
            }

            // Dukung format single array atau multi-ekskul (Ekskul 1, 2, 3)
            if (isset($items['id_ekskul'])) {
                $items = [$items];
            }

            $submittedEkskulIds = [];

            foreach ($items as $data) {
                if (!empty($data['id_ekskul'])) {
                    $idEk = (int)$data['id_ekskul'];
                    $submittedEkskulIds[] = $idEk;
                    $predikat = $data['predikat'] ?? 'Baik';

                    // Buatkan deskripsi standar jika guru tidak mengisi modal khusus
                    $deskripsi = trim($data['deskripsi'] ?? '');
                    if ($deskripsi === '') {
                        if ($predikat === 'Sangat Baik') {
                            $deskripsi = 'Sangat aktif, berdisiplin tinggi, dan menunjukkan antusiasme luar biasa dalam setiap kegiatan.';
                        } elseif ($predikat === 'Baik') {
                            $deskripsi = 'Aktif mengikuti kegiatan secara konsisten dan mampu bekerja sama dengan baik.';
                        } else {
                            $deskripsi = 'Cukup aktif dalam kegiatan, perlu lebih ditingkatkan kedisiplinan dan kehadiran.';
                        }
                    }

                    NilaiEkstrakurikuler::updateOrCreate(
                        [
                            'id_siswa' => $siswaId,
                            'id_ekskul' => $idEk,
                            'id_semester' => $semesterId,
                        ],
                        [
                            'predikat' => $predikat,
                            'deskripsi' => $deskripsi,
                        ]
                    );
                }
            }

            // Hapus ekskul yang sebelumnya tersimpan namun diubah menjadi kosong / "-- Pilih --"
            NilaiEkstrakurikuler::where('id_siswa', $siswaId)
                ->where('id_semester', $semesterId)
                ->whereNotIn('id_ekskul', $submittedEkskulIds)
                ->delete();
        }

        // Simpan kolom Keterangan / Catatan Tambahan Siswa
        foreach ($catatanEntries as $siswaId => $note) {
            $siswa = Siswa::find($siswaId);
            if (!$siswa) continue;

            if ($user && !$user->canAccessSiswa($siswa)) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk siswa di kelas ini.');
            }

            $note = trim($note ?? '');
            if ($note !== '') {
                CatatanWali::updateOrCreate(
                    [
                        'id_siswa' => $siswaId,
                        'id_semester' => $semesterId,
                    ],
                    [
                        'catatan_wali' => $note,
                    ]
                );
            }
        }

        return back()->with('success', 'Data presensi, penilaian ekstrakurikuler, dan catatan siswa berhasil disimpan.');
    }

    public function storeDeskripsiAjax(Request $request)
    {
        $request->validate([
            'siswa_id'    => 'required|exists:siswa,id_siswa',
            'id_ekskul'   => 'required|exists:ekstrakurikuler,id_ekskul',
            'semester_id' => 'required',
            'predikat'    => 'required|string',
            'deskripsi'   => 'nullable|string',
        ]);

        $user = auth()->user();
        $siswa = Siswa::findOrFail($request->siswa_id);
        if ($user && !$user->canAccessSiswa($siswa)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk siswa ini.'], 403);
        }

        $predikat = $request->input('predikat', 'Baik');
        $deskripsi = trim($request->input('deskripsi', ''));

        if ($deskripsi === '') {
            if ($predikat === 'Sangat Baik') {
                $deskripsi = 'Sangat aktif, berdisiplin tinggi, dan menunjukkan antusiasme luar biasa dalam setiap kegiatan.';
            } elseif ($predikat === 'Baik') {
                $deskripsi = 'Aktif mengikuti kegiatan secara konsisten dan mampu bekerja sama dengan baik.';
            } else {
                $deskripsi = 'Cukup aktif dalam kegiatan, perlu lebih ditingkatkan kedisiplinan dan kehadiran.';
            }
        }

        NilaiEkstrakurikuler::updateOrCreate(
            [
                'id_siswa'    => $request->siswa_id,
                'id_ekskul'   => $request->id_ekskul,
                'id_semester' => $request->semester_id,
            ],
            [
                'predikat'  => $predikat,
                'deskripsi' => $deskripsi,
            ]
        );

        return response()->json([
            'status'    => 'success',
            'message'   => 'Deskripsi ekstrakurikuler berhasil disimpan ke database.',
            'predikat'  => $predikat,
            'deskripsi' => $deskripsi,
            'id_ekskul' => (int)$request->id_ekskul,
        ]);
    }
}
