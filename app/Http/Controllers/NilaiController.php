<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNilaiRequest;
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
            $kelasBinaan = $user->requireKelasBinaan();
            $kelasList = collect([$kelasBinaan]);
            $mapelList = MataPelajaran::orderBy('nama_mapel')->get();
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
        } elseif ($user->isWaliKelas()) {
            $selectedKelasId = $user->requireKelasBinaan()->id_kelas;
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

    public function storeNilai(StoreNilaiRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $semesterId = $validated['semester_id'];
        $mapelId = (int)$validated['mapel_id'];
        $nilaiData = $validated['nilai'] ?? [];

        if ($user && !$user->canAccessMapel($mapelId)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk menyimpan nilai pada mata pelajaran ini.');
        }

        if ($user && $user->isWaliKelas()) {
            $user->requireKelasBinaan();
        }

        foreach ($nilaiData as $siswaId => $item) {
            $siswa = Siswa::find($siswaId);
            if (!$siswa) continue;

            if ($user && !$user->canAccessSiswa($siswa)) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk siswa di luar kelas binaan.');
            }

            $tp1 = isset($item['tp1']) && $item['tp1'] !== '' ? (float)$item['tp1'] : null;
            $tp2 = isset($item['tp2']) && $item['tp2'] !== '' ? (float)$item['tp2'] : null;
            $tp3 = isset($item['tp3']) && $item['tp3'] !== '' ? (float)$item['tp3'] : null;
            $tp4 = isset($item['tp4']) && $item['tp4'] !== '' ? (float)$item['tp4'] : null;
            $tp5 = isset($item['tp5']) && $item['tp5'] !== '' ? (float)$item['tp5'] : null;
            $tp6 = isset($item['tp6']) && $item['tp6'] !== '' ? (float)$item['tp6'] : null;
            $tp7 = isset($item['tp7']) && $item['tp7'] !== '' ? (float)$item['tp7'] : null;
            $tp8 = isset($item['tp8']) && $item['tp8'] !== '' ? (float)$item['tp8'] : null;

            // Hitung NS (Nilai Sumatif - rata-rata TP yang terisi) murni di server
            $tps = array_filter([$tp1, $tp2, $tp3, $tp4, $tp5, $tp6, $tp7, $tp8], fn($v) => !is_null($v) && $v !== '');
            $ns = count($tps) > 0 ? round(array_sum($tps) / count($tps), 2) : null;

            // SAS (Sumatif Akhir Semester)
            $sas = isset($item['sas']) && $item['sas'] !== '' ? (float)$item['sas'] : null;

            // Hitung Nilai Akhir murni di server: HANYA jika NS dan SAS keduanya ada
            $nilaiAkhir = (!is_null($ns) && !is_null($sas)) ? round(($ns + $sas) / 2, 2) : null;

            $hasScore = count($tps) > 0 || !is_null($sas);

            $existing = Nilai::where('id_siswa', $siswaId)
                ->where('id_mapel', $mapelId)
                ->where('id_semester', $semesterId)
                ->first();

            // Jika siswa tidak punya nilai sama sekali
            if (!$hasScore && (is_null($nilaiAkhir) || $nilaiAkhir == 0)) {
                // Jika ada data dummy/kosong tanpa capaian, bersihkan dari database
                if ($existing && (!$existing->capaian || (empty($existing->capaian->deskripsi_1) && empty($existing->capaian->deskripsi_2)))) {
                    $existing->delete();
                }
                continue;
            }

            Nilai::updateOrCreate(
                [
                    'id_siswa' => $siswaId,
                    'id_mapel' => $mapelId,
                    'id_semester' => $semesterId,
                ],
                [
                    'tp1' => $tp1,
                    'tp2' => $tp2,
                    'tp3' => $tp3,
                    'tp4' => $tp4,
                    'tp5' => $tp5,
                    'tp6' => $tp6,
                    'tp7' => $tp7,
                    'tp8' => $tp8,
                    'ns' => $ns,
                    'sas' => $sas,
                    'nilai_akhir' => $nilaiAkhir ?? 0.00,
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

        $deskripsi1 = $nilai && $nilai->capaian ? $nilai->capaian->deskripsi_1 : '';
        $deskripsi2 = $nilai && $nilai->capaian ? $nilai->capaian->deskripsi_2 : '';

        // Abaikan teks default palsu yang pernah ter-generate oleh bug sebelumnya
        if ($deskripsi2 === 'Menunjukkan penguasaan materi sesuai tujuan pembelajaran.') {
            $deskripsi2 = '';
        }

        return response()->json([
            'status' => 'success',
            'id_nilai' => $nilai ? $nilai->id_nilai : null,
            'deskripsi_1' => $deskripsi1,
            'deskripsi_2' => $deskripsi2,
        ]);
    }

    public function storeCapaian(Request $request)
    {
        $user = auth()->user();
        $mapelId = (int)$request->mapel_id;

        if ($user && !$user->canAccessMapel($mapelId)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk mata pelajaran ini.'], 403);
        }

        $request->validate([
            'siswa_id' => 'required|exists:siswa,id_siswa',
            'mapel_id' => 'required|exists:mata_pelajaran,id_mapel',
            'semester_id' => 'required',
            'nomor' => 'required|in:1,2',
            'deskripsi' => 'nullable|string',
        ]);

        $siswa = Siswa::findOrFail($request->siswa_id);
        if ($user && !$user->canAccessSiswa($siswa)) {
            return response()->json(['status' => 'error', 'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk siswa ini.'], 403);
        }

        // Kumpulkan data TP, NS, SAS jika dikirim bersamaan dari baris tabel saat ini
        $tpData = [];
        for ($i = 1; $i <= 8; $i++) {
            if ($request->has("tp{$i}") && $request->input("tp{$i}") !== '') {
                $tpData["tp{$i}"] = (float)$request->input("tp{$i}");
            }
        }
        if ($request->has('ns') && $request->input('ns') !== '') {
            $tpData['ns'] = (float)$request->input('ns');
        }
        if ($request->has('sas') && $request->input('sas') !== '') {
            $tpData['sas'] = (float)$request->input('sas');
        }
        if ($request->has('nilai_akhir') && $request->input('nilai_akhir') !== '') {
            $tpData['nilai_akhir'] = (float)$request->input('nilai_akhir');
        }

        $nilai = Nilai::firstOrNew([
            'id_siswa' => $request->siswa_id,
            'id_mapel' => $request->mapel_id,
            'id_semester' => $request->semester_id,
        ]);

        foreach ($tpData as $col => $val) {
            $nilai->$col = $val;
        }

        // Pastikan nilai_akhir tidak null agar tidak melanggar DB constraint
        // Nilai akhir HANYA dihitung jika NS dan SAS keduanya sudah terisi
        if (is_null($nilai->nilai_akhir)) {
            if (isset($tpData['ns']) && isset($tpData['sas'])) {
                $nilai->nilai_akhir = round(($tpData['ns'] + $tpData['sas']) / 2, 2);
            } else {
                $nilai->nilai_akhir = 0.00;
            }
        }
        $nilai->save();

        $capaian = CapaianKompetensi::firstOrNew(['id_nilai' => $nilai->id_nilai]);
        $deskripsi = $request->input('deskripsi', '') ?? '';
        if ($request->nomor == 1) {
            $capaian->deskripsi_1 = $deskripsi;
            if (is_null($capaian->deskripsi_2)) {
                $capaian->deskripsi_2 = '';
            }
        } else {
            $capaian->deskripsi_2 = $deskripsi;
            if (is_null($capaian->deskripsi_1)) {
                $capaian->deskripsi_1 = '';
            }
        }
        $capaian->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Capaian kompetensi ' . $request->nomor . ' berhasil disimpan.',
            'nomor' => (int)$request->nomor,
            'deskripsi_1' => $capaian->deskripsi_1,
            'deskripsi_2' => $capaian->deskripsi_2,
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
            $kelasBinaan = $user->requireKelasBinaan();
            $kelasList = collect([$kelasBinaan]);
            $defaultKelasId = $kelasBinaan->id_kelas;
            $selectedKelasId = $kelasBinaan->id_kelas;
        } else {
            // Operator: akses penuh
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
        $user = auth()->user();
        if ($user && $user->isWaliKelas()) {
            $user->requireKelasBinaan();
        }

        $semesterId = $request->input('semester_id', 2);
        $prestasiData = $request->input('prestasi', []);

        foreach ($prestasiData as $siswaId => $data) {
            $siswa = Siswa::find($siswaId);
            if (!$siswa) continue;

            if ($user && !$user->canAccessSiswa($siswa)) {
                abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mencatat prestasi siswa ini.');
            }

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
