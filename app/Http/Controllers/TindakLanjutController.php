<?php

namespace App\Http\Controllers;

use App\Models\AnalisisSiswa;
use App\Models\Siswa;
use App\Models\TindakLanjut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TindakLanjutController extends Controller
{
    public function index(Request $request, $siswaId = 1)
    {
        $siswa = Siswa::with('kelas')->find($siswaId) ?? Siswa::first();

        // Default consultation session details matching mockup
        $counseling = [
            'tanggal_sesi' => now()->addDays(2)->toDateString(),
            'waktu_sesi' => '09:00',
            'jenis_konseling' => 'Akademik & kehadiran',
            'pendamping' => Auth::user()->displayName ?? 'Endang Sulistian, S.Pd.',
            'peserta' => 'Siswa & orang tua / wali',
            'tempat' => 'Ruang BK / Ruang Konseling',
            'catatan' => 'Siswa bersedia mengikuti bimbingan remedial dan orang tua berkomitmen memantau jam belajar mandiri di rumah.',
            'status' => 'Direncanakan',
            'penanggung_jawab' => Auth::user()->displayName ?? 'Endang Sulistian, S.Pd.',
            'tanggal_evaluasi' => now()->addDays(14)->toDateString(),
            'rencana' => [
                'remedial' => true,
                'kehadiran' => true,
                'motivasi' => true,
            ],
        ];

        $guruList = \App\Models\Guru::orderBy('nama_guru')->get();

        return view('tindak-lanjut.index', compact('siswa', 'counseling', 'guruList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_siswa' => 'required',
            'tanggal_sesi' => 'required|date',
        ]);

        $tindakanNarrative = "Sesi konseling ({$request->jenis_konseling}) dijadwalkan pada {$request->tanggal_sesi} pukul {$request->waktu_sesi}. ";
        $tindakanNarrative .= "Catatan: " . ($request->catatan ?: 'Konseling direncanakan.') . " ";
        $tindakanNarrative .= "PJ: {$request->penanggung_jawab}, Evaluasi: {$request->tanggal_evaluasi}.";

        TindakLanjut::create([
            'id_analisis' => 1,
            'id_user' => Auth::id() ?? 2,
            'tanggal' => $request->tanggal_sesi,
            'tindakan' => $tindakanNarrative,
            'status' => 'Sedang Diproses',
        ]);

        return redirect()->route('wali.early-warning')->with('success', 'Draf konseling dan rencana tindak lanjut berhasil disimpan.');
    }
}
