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
        $data = $request->validate([
            'id_siswa'         => 'required|exists:siswa,id_siswa',
            'tanggal_sesi'     => 'required|date',
            'waktu_sesi'       => 'nullable',
            'jenis_konseling'  => 'nullable|string|max:100',
            'peserta'          => 'nullable|string|max:100',
            'tempat'           => 'nullable|string|max:100',
            'catatan'          => 'nullable|string|max:2000',
            'tanggal_evaluasi' => 'nullable|date|after_or_equal:tanggal_sesi',
            'rencana'          => 'nullable|array',
        ]);

        $user = Auth::user();
        $siswa = Siswa::findOrFail($data['id_siswa']);

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

        $rencanaStr = !empty($data['rencana']) ? (is_array($data['rencana']) ? implode(', ', $data['rencana']) : $data['rencana']) : null;

        TindakLanjut::create([
            'id_analisis'       => $analisis->id_analisis,
            'id_user'           => Auth::id(),
            'tanggal'           => $data['tanggal_sesi'],
            'waktu'             => $data['waktu_sesi'] ?? null,
            'jenis_konseling'   => $data['jenis_konseling'] ?? 'Akademik & kehadiran',
            'peserta_diundang'  => $data['peserta'] ?? 'Siswa & orang tua / wali',
            'tempat_media'      => $data['tempat'] ?? 'Ruang BK',
            'tindakan'          => 'Sesi konseling dijadwalkan',
            'catatan_konseling' => $data['catatan'] ?? null,
            'rencana_dukungan'  => $rencanaStr,
            'status'            => 'Sedang Diproses',
            'tanggal_evaluasi'  => $data['tanggal_evaluasi'] ?? null,
        ]);

        $rolePrefix = $user && $user->isOperator() ? 'operator' : 'wali';

        return redirect()->route($rolePrefix . '.early-warning')->with('success', 'Draf konseling dan rencana tindak lanjut berhasil disimpan.');
    }
}
