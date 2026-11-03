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
        $user = auth()->user();
        if ($user && $user->isWaliKelas()) {
            $user->requireKelasBinaan();
        }

        $siswa = Siswa::with('kelas')->find($siswaId) ?? Siswa::first();

        if ($user && $siswa && !$user->canAccessSiswa($siswa)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk melihat tindak lanjut siswa ini.');
        }

        $rolePrefix = $user && $user->isOperator() ? 'operator' : 'wali';

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

        return view('tindak-lanjut.index', compact('siswa', 'counseling', 'guruList', 'rolePrefix'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_siswa'     => 'required|exists:siswa,id_siswa',
            'tanggal_sesi' => 'required|date',
            'waktu_sesi'   => 'required',
        ]);

        $user = Auth::user();
        $siswa = Siswa::findOrFail($request->id_siswa);

        if ($user && !$user->canAccessSiswa($siswa)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk siswa ini.');
        }

        // Cari analisis aktif milik siswa ini, atau buatkan jika belum ada (tidak hardcode ke id_analisis = 1)
        $analisis = AnalisisSiswa::where('id_siswa', $siswa->id_siswa)
            ->latest('tanggal_analisis')
            ->first();

        if (!$analisis) {
            $analisis = AnalisisSiswa::create([
                'id_siswa'         => $siswa->id_siswa,
                'id_semester'      => $request->input('semester_id', 2),
                'tanggal_analisis' => now()->toDateString(),
                'status'           => 'Perlu Perhatian',
                'ringkasan'        => 'Dibuat otomatis dari sesi tindak lanjut.',
            ]);
        }

        $jenis = $request->input('jenis_konseling', 'Akademik & kehadiran');
        $catatan = $request->input('catatan', '');
        $pj = $request->input('penanggung_jawab', $user->displayName ?? 'Konselor');
        $evaluasi = $request->input('tanggal_evaluasi');

        $tindakanNarrative = "Sesi konseling ({$jenis}) dijadwalkan pada {$request->tanggal_sesi} pukul {$request->waktu_sesi}. ";
        $tindakanNarrative .= "Catatan: " . ($catatan ?: 'Konseling direncanakan.') . " ";
        $tindakanNarrative .= "PJ: {$pj}, Evaluasi: {$evaluasi}.";

        $rencana = $request->input('rencana', []);
        $rencanaStr = is_array($rencana) ? implode(', ', $rencana) : $rencana;

        TindakLanjut::create([
            'id_analisis'       => $analisis->id_analisis,
            'id_user'           => $user->id_user ?? Auth::id(),
            'tanggal'           => $request->tanggal_sesi,
            'waktu'             => $request->waktu_sesi,
            'jenis_konseling'   => $jenis,
            'peserta_diundang'  => $request->input('peserta', 'Siswa & orang tua / wali'),
            'tempat_media'      => $request->input('tempat', 'Ruang BK'),
            'tindakan'          => $tindakanNarrative,
            'catatan_konseling' => $catatan ?: null,
            'rencana_dukungan'  => $rencanaStr ?: null,
            'status'            => 'Sedang Diproses',
            'tanggal_evaluasi'  => $evaluasi ?: null,
        ]);

        $rolePrefix = $user && $user->isOperator() ? 'operator' : 'wali';

        return redirect()->route($rolePrefix . '.early-warning')->with('success', 'Draf konseling dan rencana tindak lanjut berhasil disimpan.');
    }
}
