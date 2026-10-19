<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;

class RaporController extends Controller
{
    public function index(Request $request)
    {
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $semesterList = Semester::orderBy('id_semester')->get();

        $selectedKelasId = $request->query('kelas_id', 1);
        $selectedSemesterId = $request->query('semester_id', 2);
        $selectedTanggal = $request->query('tanggal', '2026-10-01');
        $search = $request->query('search');

        $query = Siswa::where('id_kelas', $selectedKelasId);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        $siswaList = $query->orderBy('nis')->get();

        // Calculate completeness
        $totalSiswa = 32;
        $lengkapCount = 27;
        $belumLengkapCount = 5;
        $persentaseLengkap = 84;

        return view('rapor.index', compact(
            'kelasList',
            'semesterList',
            'selectedKelasId',
            'selectedSemesterId',
            'selectedTanggal',
            'search',
            'siswaList',
            'totalSiswa',
            'lengkapCount',
            'belumLengkapCount',
            'persentaseLengkap'
        ));
    }

    public function show($id)
    {
        $siswa = Siswa::with(['kelas.waliKelas', 'nilai.mataPelajaran', 'nilai.capaian', 'prestasi', 'nilaiEkstrakurikuler.ekstrakurikuler'])->findOrFail($id);

        $rekap = \Illuminate\Support\Facades\DB::select('CALL rekap_presensi(?)', [$siswa->nis]);
        $presensi = !empty($rekap) ? $rekap[0] : null;

        return view('rapor.cetak', compact('siswa', 'presensi'));
    }
}
