<?php

namespace App\Http\Controllers;

use App\Models\CapaianKompetensi;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Nilai;
use App\Models\Prestasi;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;

class NilaiController extends Controller
{
    // ==================== INPUT NILAI (GURU MAPEL) ====================
    public function inputNilai(Request $request)
    {
        $user = auth()->user();
        $semesterList = Semester::orderBy('id_semester')->get();

        // --- Data-Level Access Control ---
        if ($user->isGuruMapel()) {
            // Guru Mapel hanya melihat mapel yang diampu
            $mapelList = $user->getMataPelajaranAmpuan();
            $kelasList = Kelas::orderBy('nama_kelas')->get(); // Guru bisa mengajar di banyak kelas
            $defaultMapelId = $mapelList->first()->id_mapel ?? null;
        } elseif ($user->isWaliKelas()) {
            // Wali Kelas hanya melihat kelas binaannya
            $mapelList = MataPelajaran::orderBy('nama_mapel')->get();
            $kelasBinaan = $user->getKelasBinaan();
            $kelasList = $kelasBinaan ? collect([$kelasBinaan]) : Kelas::orderBy('nama_kelas')->get();
            $defaultMapelId = $mapelList->first()->id_mapel ?? null;
        } else {
            // Operator: akses penuh
            $mapelList = MataPelajaran::orderBy('nama_mapel')->get();
            $kelasList = Kelas::orderBy('nama_kelas')->get();
            $defaultMapelId = 3; // Default Bahasa Inggris as in mockup
        }

        $selectedKelasId = $request->query('kelas_id', $kelasList->first()->id_kelas ?? 1);
        $selectedSemesterId = $request->query('semester_id', 2);
        $selectedMapelId = $request->query('mapel_id', $defaultMapelId);

        // Proteksi jika user mencoba mengubah ID via URL query string
        if ($user->isGuruMapel() && $mapelList->isNotEmpty()) {
            if (!$mapelList->contains('id_mapel', $selectedMapelId)) {
                $selectedMapelId = $mapelList->first()->id_mapel;
            }
        } elseif ($user->isWaliKelas() && $user->getKelasBinaan()) {
            $selectedKelasId = $user->getKelasBinaan()->id_kelas;
        }

        $search = $request->query('search');

        $query = Siswa::where('id_kelas', $selectedKelasId);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        $siswaList = $query->orderBy('nama')->get();

        $nilaiMap = Nilai::with('capaian')
            ->whereIn('id_siswa', $siswaList->pluck('id_siswa'))
            ->where('id_semester', $selectedSemesterId)
            ->where('id_mapel', $selectedMapelId)
            ->get()
            ->keyBy('id_siswa');

        return view('nilai.input-nilai', compact(
            'kelasList',
            'semesterList',
            'mapelList',
            'selectedKelasId',
            'selectedSemesterId',
            'selectedMapelId',
            'search',
            'siswaList',
            'nilaiMap'
        ));
    }

    public function storeNilai(Request $request)
    {
        $user = auth()->user();
        $semesterId = $request->input('semester_id', 2);
        $mapelId = $request->input('mapel_id', 3);
        $nilaiData = $request->input('nilai', []);

        if ($user->isGuruMapel()) {
            $mapelAmpuan = $user->getMataPelajaranAmpuan();
            if ($mapelAmpuan->isNotEmpty() && !$mapelAmpuan->contains('id_mapel', $mapelId)) {
                abort(403, 'Akses ditolak: Anda hanya dapat menyimpan nilai untuk mata pelajaran yang Anda ampu.');
            }
        }

        foreach ($nilaiData as $siswaId => $item) {
            $nilaiAkhir = $item['nilai_akhir'] ?? $item['rata_rata'] ?? 85.00;

            Nilai::updateOrCreate(
                [
                    'id_siswa' => $siswaId,
                    'id_mapel' => $mapelId,
                    'id_semester' => $semesterId,
                ],
                [
                    'nilai_akhir' => $nilaiAkhir,
                ]
            );
        }

        return back()->with('success', 'Rekap nilai siswa berhasil disimpan.');
    }

    // ==================== CAPAIAN KOMPETENSI (MODAL & STORE) ====================
    public function getCapaian(Request $request)
    {
        $siswaId = $request->query('siswa_id');
        $mapelId = $request->query('mapel_id');
        $semesterId = $request->query('semester_id', 2);

        $nilai = Nilai::with('capaian')
            ->where('id_siswa', $siswaId)
            ->where('id_mapel', $mapelId)
            ->where('id_semester', $semesterId)
            ->first();

        return response()->json([
            'status' => 'success',
            'id_nilai' => $nilai ? $nilai->id_nilai : null,
            'deskripsi_1' => $nilai && $nilai->capaian ? $nilai->capaian->deskripsi_1 : '',
            'deskripsi_2' => $nilai && $nilai->capaian ? $nilai->capaian->deskripsi_2 : '',
        ]);
    }

    public function storeCapaian(Request $request)
    {
        $user = auth()->user();
        if ($user->isGuruMapel()) {
            $mapelAmpuan = $user->getMataPelajaranAmpuan();
            if ($mapelAmpuan->isNotEmpty() && !$mapelAmpuan->contains('id_mapel', $request->mapel_id)) {
                return response()->json(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak mengampu mata pelajaran ini.'], 403);
            }
        }

        $request->validate([
            'siswa_id' => 'required|exists:siswa,id_siswa',
            'mapel_id' => 'required|exists:mata_pelajaran,id_mapel',
            'semester_id' => 'required',
            'nomor' => 'required|in:1,2',
            'deskripsi' => 'required|string',
        ]);

        $nilai = Nilai::firstOrCreate(
            [
                'id_siswa' => $request->siswa_id,
                'id_mapel' => $request->mapel_id,
                'id_semester' => $request->semester_id,
            ],
            [
                'nilai_akhir' => 85.00,
            ]
        );

        $capaian = CapaianKompetensi::firstOrNew(['id_nilai' => $nilai->id_nilai]);
        if ($request->nomor == 1) {
            $capaian->deskripsi_1 = $request->deskripsi;
            if (empty($capaian->deskripsi_2)) {
                $capaian->deskripsi_2 = 'Menunjukkan penguasaan materi sesuai tujuan pembelajaran.';
            }
        } else {
            $capaian->deskripsi_2 = $request->deskripsi;
            if (empty($capaian->deskripsi_1)) {
                $capaian->deskripsi_1 = 'Menunjukkan penguasaan materi dengan sangat baik.';
            }
        }
        $capaian->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Capaian kompetensi berhasil disimpan.',
        ]);
    }

    // ==================== INPUT PRESTASI (WALI KELAS) ====================
    public function inputPrestasi(Request $request)
    {
        $user = auth()->user();
        $semesterList = Semester::orderBy('id_semester')->get();

        // --- Data-Level Access Control ---
        if ($user->isWaliKelas()) {
            // Wali Kelas hanya melihat kelas binaannya
            $kelasBinaan = $user->getKelasBinaan();
            $kelasList = $kelasBinaan ? collect([$kelasBinaan]) : Kelas::orderBy('nama_kelas')->get();
            $defaultKelasId = $kelasBinaan->id_kelas ?? ($kelasList->first()->id_kelas ?? 1);
        } else {
            // Operator: akses penuh
            $kelasList = Kelas::orderBy('nama_kelas')->get();
            $defaultKelasId = $kelasList->first()->id_kelas ?? 1;
        }

        $selectedKelasId = $request->query('kelas_id', $defaultKelasId);
        if ($user && $user->isWaliKelas() && $user->getKelasBinaan()) {
            $selectedKelasId = $user->getKelasBinaan()->id_kelas;
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

        $prestasiMap = Prestasi::whereIn('id_siswa', $siswaList->pluck('id_siswa'))
            ->where('id_semester', $selectedSemesterId)
            ->get()
            ->groupBy('id_siswa');

        return view('nilai.input-prestasi', compact(
            'kelasList',
            'semesterList',
            'selectedKelasId',
            'selectedSemesterId',
            'search',
            'siswaList',
            'prestasiMap'
        ));
    }

    public function storePrestasi(Request $request)
    {
        $semesterId = $request->input('semester_id', 2);
        $prestasiData = $request->input('prestasi', []);

        foreach ($prestasiData as $siswaId => $data) {
            if (!empty($data['nama_prestasi'])) {
                Prestasi::updateOrCreate(
                    [
                        'id_siswa' => $siswaId,
                        'id_semester' => $semesterId,
                        'nama_prestasi' => $data['nama_prestasi'],
                    ],
                    [
                        'jenis' => $data['jenis'] ?? 'Akademik',
                        'keterangan' => $data['keterangan'] ?? 'Tingkat Kabupaten',
                    ]
                );
            }
        }

        return back()->with('success', 'Data prestasi siswa berhasil disimpan.');
    }
}
