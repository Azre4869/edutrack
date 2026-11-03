<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Absensi;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;

class RaporController extends Controller
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
        $selectedTanggal = $request->query('tanggal', '2026-10-01');
        $search = $request->query('search');

        $query = Siswa::where('id_kelas', $selectedKelasId)
            ->with([
                'kelas',
                'nilai' => function ($q) use ($selectedSemesterId) {
                    $q->where('id_semester', $selectedSemesterId)->with(['capaian', 'mataPelajaran']);
                }
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }
        $siswaList = $query->orderBy('nis')->get();

        // Hitung kelengkapan riil berdasarkan nilai yang diinput
        $totalSiswa = $siswaList->count();
        $totalMapel = \App\Models\MataPelajaran::count() ?: 12;
        $lengkapCount = 0;
        $belumLengkapCount = 0;

        foreach ($siswaList as $s) {
            $totalNilai = $s->nilai->count();
            $completedNilai = $s->nilai->filter(function ($n) {
                return $this->isNilaiComplete($n);
            })->count();

            // Siswa berstatus Lengkap hanya jika seluruh mata pelajaran (12 mapel) nilainya lengkap
            if ($completedNilai >= $totalMapel) {
                $s->status_rapor = 'Lengkap';
                $s->is_rapor_lengkap = true;
                $lengkapCount++;
            } elseif ($totalNilai > 0) {
                $s->status_rapor = 'Belum Lengkap';
                $s->is_rapor_lengkap = false;
                $belumLengkapCount++;
            } else {
                $s->status_rapor = 'Belum Diisi';
                $s->is_rapor_lengkap = false;
                $belumLengkapCount++;
            }
            $s->nilai_completed_count = $completedNilai;
            $s->nilai_total_count = $totalNilai;
        }

        $persentaseLengkap = $totalSiswa > 0 ? round(($lengkapCount / $totalSiswa) * 100) : 0;
        $isWali = $user && $user->isWaliKelas();

        return view('rapor.index', compact(
            'isWali',
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

    public function show($id, Request $request)
    {
        $user = auth()->user();
        if ($user && $user->role === 'Operator') {
            abort(403, 'Akses ditolak: Cetak dan detail rapor merupakan hak eksklusif Wali Kelas.');
        }

        $selectedSemesterId = $request->query('semester_id', 2);

        $siswa = Siswa::with([
            'kelas.waliKelas',
            'nilai' => function ($q) use ($selectedSemesterId) {
                $q->where('id_semester', $selectedSemesterId)->orderBy('id_mapel');
            },
            'nilai.mataPelajaran',
            'nilai.capaian',
            'prestasi' => function ($q) use ($selectedSemesterId) {
                $q->where('id_semester', $selectedSemesterId);
            },
            'nilaiEkstrakurikuler' => function ($q) use ($selectedSemesterId) {
                $q->where('id_semester', $selectedSemesterId)->with('ekstrakurikuler');
            },
            'catatanWali' => function ($q) use ($selectedSemesterId) {
                $q->where('id_semester', $selectedSemesterId);
            },
        ])->findOrFail($id);

        $catatan = $siswa->catatanWali->first();

        if ($user && !$user->canAccessSiswa($siswa)) {
            abort(403, 'Akses ditolak: Anda hanya berhak mengakses rapor siswa di kelas binaan Anda.');
        }

        // Rekap presensi per semester menggunakan Eloquent (tanpa stored procedure)
        $absensiRekap = Absensi::where('id_siswa', $siswa->id_siswa)
            ->where('id_semester', $selectedSemesterId)
            ->selectRaw("
                SUM(CASE WHEN status = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
                SUM(CASE WHEN status = 'Izin'  THEN 1 ELSE 0 END) AS izin,
                SUM(CASE WHEN status = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
                SUM(CASE WHEN status = 'Alpha' THEN 1 ELSE 0 END) AS alpha
            ")
            ->first();

        $presensi = $absensiRekap;

        // Semester aktif
        $semesterObj = Semester::find($selectedSemesterId);
        $semesterLabel = $semesterObj->nama_semester ?? ($selectedSemesterId == 2 ? 'Genap' : 'Ganjil');
        $tahunAjaran   = $siswa->kelas->tahun_ajaran ?? '2026/2027';

        // Wali kelas & NIP
        $waliKelas = $siswa->kelas->waliKelas ?? null;
        $namaWali  = $waliKelas->nama_guru ?? ($user->display_name ?? '-');
        $nipWali   = $waliKelas->nip ?? '-';

        // Tanggal rapor
        $tanggalRapor = \Carbon\Carbon::now()->isoFormat('D MMMM Y');

        // Kepala Sekolah (Dinamis dari Master Guru)
        $kepsek = Guru::where('nip', '19671007 199802 1 001')
            ->orWhere('nama_guru', 'like', '%Teguh Suparyanto%')
            ->first();
        $namaKepsek = $kepsek->nama_guru ?? 'Drs. Teguh Suparyanto, MMPd.';
        $nipKepsek  = $kepsek->nip ?? '19671007 199802 1 001';

        // Status kelengkapan riil siswa
        $totalMapel = \App\Models\MataPelajaran::count() ?: 12;
        $totalNilai = $siswa->nilai->count();
        $completedNilai = $siswa->nilai->filter(fn($n) => $this->isNilaiComplete($n))->count();
        $isLengkap = ($completedNilai >= $totalMapel);

        return view('rapor.cetak', compact(
            'siswa',
            'presensi',
            'semesterLabel',
            'selectedSemesterId',
            'tahunAjaran',
            'namaWali',
            'nipWali',
            'namaKepsek',
            'nipKepsek',
            'tanggalRapor',
            'isLengkap',
            'completedNilai',
            'totalNilai',
            'catatan'
        ));
    }

    /**
     * Memeriksa apakah baris nilai mapel sudah lengkap
     * Syarat: TP1 s/d TP8 terisi, SAS terisi, Nilai Akhir > 0, dan Capaian 1 & 2 terisi
     */
    private function isNilaiComplete($n): bool
    {
        if (!$n) return false;
        for ($i = 1; $i <= 8; $i++) {
            $col = 'tp' . $i;
            if (is_null($n->$col) || $n->$col === '') return false;
        }
        if (is_null($n->sas) || $n->sas === '') return false;
        if (is_null($n->nilai_akhir) || (float)$n->nilai_akhir <= 0) return false;

        $c = $n->capaian;
        if (!$c) return false;
        if (empty(trim($c->deskripsi_1 ?? ''))) return false;
        if (empty(trim($c->deskripsi_2 ?? ''))) return false;

        return true;
    }
}
