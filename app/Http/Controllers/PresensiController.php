<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\Ekstrakurikuler;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\NilaiEkstrakurikuler;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;

class PresensiController extends Controller
{
    // ==================== PRESENSI KELAS ====================
    public function presensiKelas(Request $request)
    {
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $semesterList = Semester::orderBy('id_semester')->get();

        $selectedKelasId = $request->query('kelas_id', $kelasList->first()->id_kelas ?? 1);
        $selectedSemesterId = $request->query('semester_id', 2);
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
            'search',
            'siswaList',
            'absensiMap'
        ));
    }

    public function storePresensiKelas(Request $request)
    {
        $tanggal = $request->input('tanggal', now()->toDateString());
        $semesterId = $request->input('semester_id', 2);
        $statusList = $request->input('status', []); // [id_siswa => 'Hadir'|'Sakit'|'Izin'|'Alpha']
        $mapelId = $request->input('mapel_id', 1);

        foreach ($statusList as $siswaId => $status) {
            Absensi::updateOrCreate(
                [
                    'id_siswa' => $siswaId,
                    'tanggal' => $tanggal,
                    'id_semester' => $semesterId,
                ],
                [
                    'id_mapel' => $mapelId,
                    'status' => $status,
                ]
            );
        }

        return back()->with('success', 'Presensi kelas berhasil disimpan.');
    }

    // ==================== PRESENSI EKSTRAKURIKULER ====================
    public function presensiEkskul(Request $request)
    {
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $semesterList = Semester::orderBy('id_semester')->get();
        $ekskulList = Ekstrakurikuler::orderBy('nama_ekskul')->get();

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

        $nilaiEkskul = NilaiEkstrakurikuler::whereIn('id_siswa', $siswaList->pluck('id_siswa'))
            ->where('id_semester', $selectedSemesterId)
            ->get()
            ->groupBy('id_siswa');

        return view('presensi.ekskul', compact(
            'kelasList',
            'semesterList',
            'ekskulList',
            'selectedKelasId',
            'selectedSemesterId',
            'search',
            'siswaList',
            'nilaiEkskul'
        ));
    }

    public function storePresensiEkskul(Request $request)
    {
        $semesterId = $request->input('semester_id', 2);
        $ekskulEntries = $request->input('ekskul', []);

        foreach ($ekskulEntries as $siswaId => $data) {
            if (!empty($data['id_ekskul'])) {
                NilaiEkstrakurikuler::updateOrCreate(
                    [
                        'id_siswa' => $siswaId,
                        'id_ekskul' => $data['id_ekskul'],
                        'id_semester' => $semesterId,
                    ],
                    [
                        'predikat' => $data['predikat'] ?? 'Baik',
                        'deskripsi' => $data['deskripsi'] ?? 'Aktif mengikuti kegiatan.',
                    ]
                );
            }
        }

        return back()->with('success', 'Data presensi dan penilaian ekstrakurikuler berhasil disimpan.');
    }
}
