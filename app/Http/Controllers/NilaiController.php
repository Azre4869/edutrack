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
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $semesterList = Semester::orderBy('id_semester')->get();
        $mapelList = MataPelajaran::orderBy('nama_mapel')->get();

        $selectedKelasId = $request->query('kelas_id', $kelasList->first()->id_kelas ?? 1);
        $selectedSemesterId = $request->query('semester_id', 2);
        $selectedMapelId = $request->query('mapel_id', 3); // Default Bahasa Inggris as in mockup
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
        $semesterId = $request->input('semester_id', 2);
        $mapelId = $request->input('mapel_id', 3);
        $nilaiData = $request->input('nilai', []);

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
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $semesterList = Semester::orderBy('id_semester')->get();

        $selectedKelasId = $request->query('kelas_id', $kelasList->first()->id_kelas ?? 1);
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
