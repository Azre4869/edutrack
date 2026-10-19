<?php

namespace App\Http\Controllers;

use App\Models\AnalisisSiswa;
use App\Models\Kelas;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;

class EarlyWarningController extends Controller
{
    public function index(Request $request)
    {
        $kelasList = Kelas::orderBy('nama_kelas')->get();
        $semesterList = Semester::orderBy('id_semester')->get();

        $selectedKelasId = $request->query('kelas_id', 1);
        $selectedSemesterId = $request->query('semester_id', 2);
        $search = $request->query('search');

        // Risk summary stats matching Figma mockup
        $stats = [
            'tinggi' => ['count' => 5, 'pct' => '15,6%'],
            'sedang' => ['count' => 9, 'pct' => '28,1%'],
            'kecil' => ['count' => 12, 'pct' => '37,5%'],
            'normal' => ['count' => 6, 'pct' => '18,8%'],
        ];

        // List of students with early warning status
        $siswaEarlyWarning = [
            [
                'id_siswa' => 1,
                'nis' => '16389',
                'nama' => 'Firdausi Anam',
                'kelas' => 'VII A',
                'jenis_peringatan' => 'Rendahnya Nilai',
                'indikator' => 'Rata-rata nilai turun > 10%',
                'tingkat_risiko' => 'Tinggi',
                'rata_nilai' => '68,2',
                'penurunan_nilai' => '12%',
                'kehadiran' => '87%',
                'alpha' => '2 hari tanpa keterangan',
                'penurunan_kehadiran' => '13%',
                'ekskul_status' => 'Aktif',
                'kkm_info' => 'Masih dibawah KKM pada 3 mapel',
                'perilaku' => 'Tidak ada catatan pelanggaran terbaru',
            ],
            [
                'id_siswa' => 2,
                'nis' => '16390',
                'nama' => 'Iqbal Maulana',
                'kelas' => 'XI F',
                'jenis_peringatan' => 'Ketidakhadiran',
                'indikator' => '> 5 hari tanpa keterangan',
                'tingkat_risiko' => 'Sedang',
                'rata_nilai' => '74,5',
                'penurunan_nilai' => '5%',
                'kehadiran' => '80%',
                'alpha' => '5 hari tanpa keterangan',
                'penurunan_kehadiran' => '20%',
                'ekskul_status' => 'Cukup Aktif',
                'kkm_info' => 'Di bawah KKM pada 1 mapel',
                'perilaku' => 'Perlu teguran keterlambatan',
            ],
            [
                'id_siswa' => 4,
                'nis' => '16392',
                'nama' => 'Fahar Alif',
                'kelas' => 'VII G',
                'jenis_peringatan' => 'Rendahnya Nilai & Kehadiran',
                'indikator' => 'Kehadiran 84% & Nilai Matematika 65',
                'tingkat_risiko' => 'Tinggi',
                'rata_nilai' => '70,0',
                'penurunan_nilai' => '8%',
                'kehadiran' => '84%',
                'alpha' => '3 hari tanpa keterangan',
                'penurunan_kehadiran' => '16%',
                'ekskul_status' => 'Aktif',
                'kkm_info' => 'Di bawah KKM pada Matematika dan IPA',
                'perilaku' => 'Tidak ada catatan pelanggaran',
            ],
            [
                'id_siswa' => 5,
                'nis' => '16393',
                'nama' => 'Lukman Hilman',
                'kelas' => 'VI H',
                'jenis_peringatan' => 'Ketidakhadiran',
                'indikator' => 'Kehadiran di bawah 85%',
                'tingkat_risiko' => 'Sedang',
                'rata_nilai' => '76,2',
                'penurunan_nilai' => '4%',
                'kehadiran' => '82%',
                'alpha' => '4 hari tanpa keterangan',
                'penurunan_kehadiran' => '18%',
                'ekskul_status' => 'Aktif',
                'kkm_info' => 'Sesuai KKM',
                'perilaku' => 'Tidak ada catatan pelanggaran',
            ],
        ];

        // Filter if search query exists
        if ($search) {
            $siswaEarlyWarning = array_filter($siswaEarlyWarning, function ($item) use ($search) {
                return stripos($item['nama'], $search) !== false ||
                       stripos($item['nis'], $search) !== false ||
                       stripos($item['kelas'], $search) !== false;
            });
        }

        // Active selected student for side panel (default to Firdausi Anam)
        $selectedId = $request->query('selected_id', 1);
        $selectedSiswa = collect($siswaEarlyWarning)->firstWhere('id_siswa', $selectedId) ?? $siswaEarlyWarning[0];

        return view('early-warning.index', compact(
            'kelasList',
            'semesterList',
            'selectedKelasId',
            'selectedSemesterId',
            'search',
            'stats',
            'siswaEarlyWarning',
            'selectedSiswa'
        ));
    }
}
