<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use App\Models\AnalisisSiswa;
use App\Models\Guru;
use App\Models\Nilai;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\TindakLanjut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TindakLanjutController extends Controller
{
    /**
     * Halaman rekap semua riwayat konseling (tanpa id siswa).
     * Ketika id siswa diberikan, tampilkan form input sesi baru untuk siswa tersebut.
     */
    public function index(Request $request, $siswaId = null)
    {
        $user = auth()->user();
        if ($user && $user->isWaliKelas()) {
            $user->requireKelasBinaan();
        }

        $rolePrefix = $user && $user->isOperator() ? 'operator' : 'wali';

        // Tanpa id siswa → halaman rekap semua riwayat konseling
        if (!$siswaId) {
            return $this->rekapRiwayat($request, $user, $rolePrefix);
        }

        // Dengan id siswa → form input sesi baru + riwayat siswa tersebut
        return $this->formSesiSiswa($request, $user, $rolePrefix, $siswaId);
    }

    /**
     * Halaman rekap: semua riwayat konseling lintas siswa.
     */
    protected function rekapRiwayat(Request $request, $user, string $rolePrefix)
    {
        $activeSemester = Semester::aktif();
        $semesterId = (int) $request->query('semester_id', $activeSemester?->id_semester ?? 1);
        $semesterList = Semester::orderByDesc('id_semester')->get();
        $search = trim($request->query('search', ''));

        $query = TindakLanjut::with([
            'analisisSiswa.siswa.kelas',
            'guru',
            'user',
        ])->orderByDesc('tanggal')->orderByDesc('id_tindak_lanjut');

        // Batasi ke kelas binaan jika wali kelas (DLAC)
        if ($user && $user->isWaliKelas()) {
            $kelasBinaan = $user->requireKelasBinaan();
            $query->whereHas('analisisSiswa.siswa', function ($q) use ($kelasBinaan) {
                $q->where('id_kelas', $kelasBinaan->id_kelas);
            });
        }

        // Filter semester
        if ($semesterId) {
            $query->whereHas('analisisSiswa', function ($q) use ($semesterId) {
                $q->where('id_semester', $semesterId);
            });
        }

        // Filter pencarian nama siswa
        if ($search !== '') {
            $query->whereHas('analisisSiswa.siswa', function ($q) use ($search) {
                $q->where('nama', 'like', '%' . $search . '%')
                  ->orWhere('nis', 'like', '%' . $search . '%')
                  ->orWhere('nisn', 'like', '%' . $search . '%');
            });
        }

        $semuaRiwayat = $query->get();
        $semesterObj  = Semester::find($semesterId) ?? $activeSemester;

        return view('tindak-lanjut.rekap', compact(
            'semuaRiwayat',
            'rolePrefix',
            'semesterId',
            'semesterObj',
            'semesterList',
            'search'
        ));
    }

    /**
     * Form input sesi baru untuk siswa tertentu + riwayat sesi siswa tersebut.
     */
    protected function formSesiSiswa(Request $request, $user, string $rolePrefix, $siswaId)
    {
        $siswa = Siswa::with('kelas')->findOrFail($siswaId);

        if ($user && !$user->canAccessSiswa($siswa)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk melihat tindak lanjut siswa ini.');
        }

        $activeSemester = Semester::aktif();
        $semesterId = (int) $request->query('semester_id', $activeSemester?->id_semester ?? 1);
        $semesterObj = Semester::find($semesterId) ?? $activeSemester;

        // Hitung metrik analisis riil siswa dari database
        $nilaiCurrent = Nilai::where('id_siswa', $siswa->id_siswa)
            ->where('id_semester', $semesterId)
            ->whereNotNull('nilai_akhir')
            ->get();

        $hasNilai = $nilaiCurrent->isNotEmpty();
        $avgNilai = $hasNilai ? round($nilaiCurrent->avg('nilai_akhir'), 1) : null;
        $countUnderKkm = $hasNilai ? $nilaiCurrent->where('nilai_akhir', '<', 75.0)->count() : null;

        // Absensi semester ini
        $absensiCurrent = Absensi::where('id_siswa', $siswa->id_siswa)
            ->where('id_semester', $semesterId)
            ->get();
        $totalAbsen = $absensiCurrent->count();
        $totalHadir = $absensiCurrent->where('status', 'Hadir')->count();
        $kehadiranPct = $totalAbsen > 0 ? round(($totalHadir / $totalAbsen) * 100) : null;

        // Semester sebelumnya untuk perbandingan nilai
        $semesterSebelumnya = Semester::where('id_semester', '<', $semesterId)
            ->orderBy('id_semester', 'desc')
            ->first();
        $prevAvg = null;
        if ($semesterSebelumnya) {
            $prevAvg = Nilai::where('id_siswa', $siswa->id_siswa)
                ->where('id_semester', $semesterSebelumnya->id_semester)
                ->whereNotNull('nilai_akhir')
                ->avg('nilai_akhir');
        }

        $perubahanTeks = 'Tidak ada data pembanding';
        $arahPerubahan = 'none';
        if ($prevAvg !== null && $prevAvg > 0 && $avgNilai !== null && $avgNilai > 0) {
            $prevAvg = round($prevAvg, 1);
            if ($avgNilai < $prevAvg) {
                $persen = round((($prevAvg - $avgNilai) / $prevAvg) * 100, 1);
                $perubahanTeks = number_format($persen, 1, ',', '.') . '% dari periode sebelumnya';
                $arahPerubahan = 'turun';
            } elseif ($avgNilai > $prevAvg) {
                $persen = round((($avgNilai - $prevAvg) / $prevAvg) * 100, 1);
                $perubahanTeks = number_format($persen, 1, ',', '.') . '% dari periode sebelumnya';
                $arahPerubahan = 'naik';
            } else {
                $perubahanTeks = '0% dari periode sebelumnya';
                $arahPerubahan = 'tetap';
            }
        }

        $analisis = (object) [
            'rata_nilai'     => $avgNilai !== null ? number_format($avgNilai, 1, ',', '.') : null,
            'perubahan_teks' => $perubahanTeks,
            'arah'           => $arahPerubahan,
            'kehadiran_pct'  => $kehadiranPct,
            'di_bawah_kkm'   => $countUnderKkm,
        ];

        // Cari analisis siswa dari database
        $analisisRecord = AnalisisSiswa::where('id_siswa', $siswa->id_siswa)
            ->where('id_semester', $semesterId)
            ->latest('tanggal_analisis')
            ->first()
            ?? AnalisisSiswa::where('id_siswa', $siswa->id_siswa)
            ->latest('tanggal_analisis')
            ->first();

        $guruList = Guru::orderBy('nama_guru')->get();

        $defaultNamaGuru = $user?->guru?->nama_guru ?? ($guruList->first()?->nama_guru ?? '-');
        $counseling = [
            'tanggal_sesi'     => now()->addDays(2)->toDateString(),
            'waktu_sesi'       => '09:00',
            'jenis_konseling'  => 'Akademik & kehadiran',
            'pendamping'       => $defaultNamaGuru,
            'peserta'          => 'Siswa & orang tua / wali',
            'tempat'           => 'Ruang BK / Ruang Konseling',
            'catatan'          => '',
            'status'           => 'Direncanakan',
            'penanggung_jawab' => $defaultNamaGuru,
            'tanggal_evaluasi' => now()->addDays(14)->toDateString(),
            'rencana'          => [
                'remedial'  => true,
                'kehadiran' => true,
                'motivasi'  => true,
            ],
        ];

        // Riwayat sesi konseling siswa ini
        $riwayatTindakLanjut = TindakLanjut::with(['guru', 'user', 'analisisSiswa'])
            ->whereHas('analisisSiswa', function ($q) use ($siswa) {
                $q->where('id_siswa', $siswa->id_siswa);
            })
            ->orderByDesc('tanggal')
            ->orderByDesc('id_tindak_lanjut')
            ->get();

        return view('tindak-lanjut.index', compact(
            'siswa',
            'counseling',
            'guruList',
            'rolePrefix',
            'analisis',
            'analisisRecord',
            'semesterId',
            'semesterObj',
            'riwayatTindakLanjut'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_siswa'         => 'required|exists:siswa,id_siswa',
            'id_analisis'      => 'nullable|exists:analisis_siswa,id_analisis',
            'semester_id'      => 'nullable|exists:semester,id_semester',
            'tanggal_sesi'     => 'required|date',
            'waktu_sesi'       => 'nullable',
            'jenis_konseling'  => 'nullable|string|max:100',
            'pendamping'       => 'nullable',
            'penanggung_jawab' => 'nullable',
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

        // Ambil analisis riil milik siswa atau lookup berdasarkan siswa + semester
        $analisis = null;
        if (!empty($data['id_analisis'])) {
            $analisis = AnalisisSiswa::where('id_analisis', $data['id_analisis'])
                ->where('id_siswa', $siswa->id_siswa)
                ->first();

            if (!$analisis) {
                return back()->withErrors(['id_analisis' => 'Analisis tidak valid atau tidak sesuai dengan siswa yang dipilih.'])->withInput();
            }
        }

        if (!$analisis) {
            $semesterId = !empty($data['semester_id'])
                ? (int) $data['semester_id']
                : (Semester::aktif()?->id_semester ?? 1);

            $analisis = AnalisisSiswa::where('id_siswa', $siswa->id_siswa)
                ->where('id_semester', $semesterId)
                ->latest('tanggal_analisis')
                ->first();

            if (!$analisis) {
                $analisis = AnalisisSiswa::where('id_siswa', $siswa->id_siswa)
                    ->latest('tanggal_analisis')
                    ->first();
            }

            if (!$analisis) {
                $analisis = AnalisisSiswa::create([
                    'id_siswa'         => $siswa->id_siswa,
                    'id_semester'      => $semesterId,
                    'tanggal_analisis' => now()->toDateString(),
                    'status'           => 'Perlu Perhatian',
                    'ringkasan'        => 'Dibuat otomatis dari sesi tindak lanjut.',
                ]);
            }
        }

        // Cari ID guru jika dipilih dari dropdown
        $idGuru = null;
        if (!empty($data['pendamping']) && is_numeric($data['pendamping'])) {
            $idGuru = (int) $data['pendamping'];
        } elseif (!empty($data['penanggung_jawab']) && is_numeric($data['penanggung_jawab'])) {
            $idGuru = (int) $data['penanggung_jawab'];
        }

        $rencanaStr = !empty($data['rencana']) ? (is_array($data['rencana']) ? implode(', ', $data['rencana']) : $data['rencana']) : null;

        TindakLanjut::create([
            'id_analisis'       => $analisis->id_analisis,
            'id_user'           => Auth::id(),
            'id_guru'           => $idGuru ?? ($user?->id_guru ?? null),
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

    /**
     * Tandai sesi konseling sebagai selesai.
     */
    public function markCompleted(Request $request, $id)
    {
        $tl = TindakLanjut::with('analisisSiswa.siswa')->findOrFail($id);
        $user = Auth::user();
        $siswa = $tl->analisisSiswa?->siswa;

        if ($user && $siswa && !$user->canAccessSiswa($siswa)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk siswa ini.');
        }

        $validated = $request->validate([
            'hasil_evaluasi'   => 'nullable|string|max:1000',
            'skor_efektivitas' => 'nullable|in:Membaik,Tetap,Memburuk,Belum Dievaluasi',
        ]);

        $updateData = [
            'status' => 'Selesai',
        ];

        if ($request->filled('hasil_evaluasi')) {
            $updateData['hasil_evaluasi'] = $validated['hasil_evaluasi'];
        }
        if ($request->filled('skor_efektivitas')) {
            $updateData['skor_efektivitas'] = $validated['skor_efektivitas'];
        }
        if (!$tl->tanggal_evaluasi) {
            $updateData['tanggal_evaluasi'] = now()->toDateString();
        }

        $tl->update($updateData);

        return back()->with('success', 'Sesi konseling berhasil ditandai selesai.');
    }

    /**
     * Hapus riwayat / draf sesi konseling.
     */
    public function destroy($id)
    {
        $tl = TindakLanjut::with('analisisSiswa.siswa')->findOrFail($id);
        $user = Auth::user();
        $siswa = $tl->analisisSiswa?->siswa;

        if ($user && $siswa && !$user->canAccessSiswa($siswa)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk siswa ini.');
        }

        $tl->delete();

        return back()->with('success', 'Draf / sesi konseling berhasil dihapus.');
    }
}
