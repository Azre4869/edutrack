<?php

namespace App\Http\Controllers;

use App\Models\Ekstrakurikuler;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use Illuminate\Http\Request;

class DataMasterController extends Controller
{
    // ==================== SISWA ====================
    public function siswa(Request $request)
    {
        $user = auth()->user();
        $search = $request->query('search');
        $perPage = max(5, min(100, (int) $request->query('per_page', 21)));

        $query = Siswa::with('kelas');

        // Data-Level Access Control untuk Wali Kelas
        if ($user && $user->isWaliKelas()) {
            $kelasBinaan = $user->requireKelasBinaan();
            $query->where('id_kelas', $kelasBinaan->id_kelas);
            $kelasList = collect([$kelasBinaan]);
        } else {
            $kelasList = Kelas::orderBy('nama_kelas')->get();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhereHas('kelas', function ($kq) use ($search) {
                      $kq->where('nama_kelas', 'like', "%{$search}%");
                  });
            });
        }

        $siswa = $query->paginate($perPage)->withQueryString();

        return view('master.siswa', compact('siswa', 'kelasList', 'search', 'perPage'));
    }

    public function storeSiswa(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|unique:siswa,nis',
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'id_kelas' => 'required|exists:kelas,id_kelas',
            'nisn' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string|max:50',
            'tanggal_lahir' => 'nullable|date',
            'agama' => 'nullable|string|max:30',
            'status_anak' => 'nullable|in:Anak Kandung,Anak Angkat,Anak Tiri',
            'status_dalam_keluarga' => 'nullable|string|max:50',
            'anak_ke' => 'nullable|integer',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:25',
            'sekolah_asal' => 'nullable|string|max:100',
            'kelas_diterima' => 'nullable|string|max:20',
            'tanggal_diterima' => 'nullable|date',
            'nama_ayah' => 'nullable|string|max:100',
            'nama_ibu' => 'nullable|string|max:100',
            'pekerjaan_ayah' => 'nullable|string|max:50',
            'pekerjaan_ibu' => 'nullable|string|max:50',
            'alamat_ortu' => 'nullable|string',
            'telp_ortu' => 'nullable|string|max:25',
            'nama_wali' => 'nullable|string|max:100',
            'pekerjaan_wali' => 'nullable|string|max:50',
            'alamat_wali' => 'nullable|string',
            'telp_wali' => 'nullable|string|max:25',
        ]);

        if (empty($validated['status_anak']) && !empty($validated['status_dalam_keluarga'])) {
            $validated['status_anak'] = $validated['status_dalam_keluarga'];
        }
        if (empty($validated['no_telepon']) && !empty($validated['telp_ortu'])) {
            $validated['no_telepon'] = $validated['telp_ortu'];
        }

        Siswa::create($validated);

        return back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function updateSiswa(Request $request, $id)
    {
        $siswa = Siswa::findOrFail($id);
        $validated = $request->validate([
            'nis' => 'required|unique:siswa,nis,' . $siswa->id_siswa . ',id_siswa',
            'nama' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'id_kelas' => 'required|exists:kelas,id_kelas',
            'nisn' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string|max:50',
            'tanggal_lahir' => 'nullable|date',
            'agama' => 'nullable|string|max:30',
            'status_anak' => 'nullable|in:Anak Kandung,Anak Angkat,Anak Tiri',
            'status_dalam_keluarga' => 'nullable|string|max:50',
            'anak_ke' => 'nullable|integer',
            'alamat' => 'nullable|string',
            'no_telepon' => 'nullable|string|max:25',
            'sekolah_asal' => 'nullable|string|max:100',
            'kelas_diterima' => 'nullable|string|max:20',
            'tanggal_diterima' => 'nullable|date',
            'nama_ayah' => 'nullable|string|max:100',
            'nama_ibu' => 'nullable|string|max:100',
            'pekerjaan_ayah' => 'nullable|string|max:50',
            'pekerjaan_ibu' => 'nullable|string|max:50',
            'alamat_ortu' => 'nullable|string',
            'telp_ortu' => 'nullable|string|max:25',
            'nama_wali' => 'nullable|string|max:100',
            'pekerjaan_wali' => 'nullable|string|max:50',
            'alamat_wali' => 'nullable|string',
            'telp_wali' => 'nullable|string|max:25',
        ]);

        if (empty($validated['status_anak']) && !empty($validated['status_dalam_keluarga'])) {
            $validated['status_anak'] = $validated['status_dalam_keluarga'];
        }
        if (empty($validated['no_telepon']) && !empty($validated['telp_ortu'])) {
            $validated['no_telepon'] = $validated['telp_ortu'];
        }

        $siswa->update($validated);

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function destroySiswa($id)
    {
        $siswa = Siswa::findOrFail($id);
        if ($siswa->nilai()->exists() 
            || $siswa->absensi()->exists() 
            || $siswa->prestasi()->exists() 
            || $siswa->nilaiEkstrakurikuler()->exists()
            || $siswa->catatanWali()->exists()
            || $siswa->analisisSiswa()->exists()
            || $siswa->nilaiHarian()->exists()
        ) {
            return back()->with('error', 'Data siswa tidak dapat dihapus karena masih memiliki riwayat akademik/nilai/absensi.');
        }

        $siswa->delete();

        return back()->with('success', 'Data siswa berhasil dihapus.');
    }

    // ==================== GURU ====================
    public function guru(Request $request)
    {
        $search = $request->query('search');
        $perPage = max(5, min(100, (int) $request->query('per_page', 50)));

        $query = Guru::with('mataPelajaran', 'kelasWali');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_guru', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $guru = $query->paginate($perPage)->withQueryString();

        return view('master.guru', compact('guru', 'search', 'perPage'));
    }

    public function storeGuru(Request $request)
    {
        $request->validate([
            'nip' => 'required|unique:guru,nip',
            'nama_guru' => 'required|string|max:100',
        ]);

        Guru::create($request->all());

        return back()->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function updateGuru(Request $request, $id)
    {
        $guru = Guru::findOrFail($id);
        $request->validate([
            'nip' => 'required|unique:guru,nip,' . $guru->id_guru . ',id_guru',
            'nama_guru' => 'required|string|max:100',
        ]);

        $guru->update($request->all());

        return back()->with('success', 'Data guru berhasil diperbarui.');
    }

    public function destroyGuru($id)
    {
        $guru = Guru::findOrFail($id);
        if ($guru->kelasWali()->exists()) {
            return back()->with('error', 'Guru tidak dapat dihapus karena masih ditugaskan sebagai wali kelas.');
        }
        if ($guru->mataPelajaran()->exists()) {
            return back()->with('error', 'Guru tidak dapat dihapus karena masih mengampu mata pelajaran.');
        }
        if ($guru->user()->exists()) {
            return back()->with('error', 'Guru tidak dapat dihapus karena masih memiliki akun pengguna terkait.');
        }

        $guru->delete();

        return back()->with('success', 'Data guru berhasil dihapus.');
    }

    // ==================== KELAS ====================
    public function kelas(Request $request)
    {
        $search = $request->query('search');
        $perPage = max(5, min(100, (int) $request->query('per_page', 50)));

        $query = Kelas::with('waliKelas')->withCount('siswa');
        if ($search) {
            $query->where('nama_kelas', 'like', "%{$search}%")
                  ->orWhere('tahun_ajaran', 'like', "%{$search}%");
        }

        $kelas = $query->paginate($perPage)->withQueryString();
        $guruList = Guru::orderBy('nama_guru')->get();

        return view('master.kelas', compact('kelas', 'guruList', 'search', 'perPage'));
    }

    public function storeKelas(Request $request)
    {
        $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'tingkat' => 'required|integer',
            'tahun_ajaran' => 'required|string|max:20',
            'id_wali_kelas' => 'nullable|exists:guru,id_guru',
        ]);

        Kelas::create($request->all());

        return back()->with('success', 'Data kelas berhasil ditambahkan.');
    }

    public function updateKelas(Request $request, $id)
    {
        $kelas = Kelas::findOrFail($id);
        $request->validate([
            'nama_kelas' => 'required|string|max:50',
            'tingkat' => 'required|integer',
            'tahun_ajaran' => 'required|string|max:20',
            'id_wali_kelas' => 'nullable|exists:guru,id_guru',
        ]);

        $kelas->update($request->all());

        return back()->with('success', 'Data kelas berhasil diperbarui.');
    }

    public function destroyKelas($id)
    {
        $kelas = Kelas::findOrFail($id);
        if ($kelas->siswa()->exists()) {
            return back()->with('error', 'Kelas tidak dapat dihapus karena masih memiliki data siswa di dalamnya.');
        }

        $kelas->delete();

        return back()->with('success', 'Data kelas berhasil dihapus.');
    }

    // ==================== MATA PELAJARAN ====================
    public function mapel(Request $request)
    {
        $search = $request->query('search');
        $perPage = max(5, min(100, (int) $request->query('per_page', 50)));

        $query = MataPelajaran::with('guru');
        if ($search) {
            $query->where('nama_mapel', 'like', "%{$search}%");
        }

        $mapel = $query->paginate($perPage)->withQueryString();
        $guruList = Guru::orderBy('nama_guru')->get();

        return view('master.mapel', compact('mapel', 'guruList', 'search', 'perPage'));
    }

    public function storeMapel(Request $request)
    {
        $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'id_guru' => 'required|exists:guru,id_guru',
        ]);

        MataPelajaran::create($request->all());

        return back()->with('success', 'Data mata pelajaran berhasil ditambahkan.');
    }

    public function updateMapel(Request $request, $id)
    {
        $mapel = MataPelajaran::findOrFail($id);
        $request->validate([
            'nama_mapel' => 'required|string|max:100',
            'id_guru' => 'required|exists:guru,id_guru',
        ]);

        $mapel->update($request->all());

        return back()->with('success', 'Data mata pelajaran berhasil diperbarui.');
    }

    public function destroyMapel($id)
    {
        $mapel = MataPelajaran::findOrFail($id);
        if ($mapel->nilai()->exists() || \App\Models\NilaiHarian::where('id_mapel', $id)->exists() || \App\Models\Absensi::where('id_mapel', $id)->exists()) {
            return back()->with('error', 'Mata pelajaran tidak dapat dihapus karena masih memiliki riwayat nilai atau absensi.');
        }

        $mapel->delete();

        return back()->with('success', 'Data mata pelajaran berhasil dihapus.');
    }

    // ==================== EKSTRAKURIKULER ====================
    public function ekskul(Request $request)
    {
        $search = $request->query('search');
        $perPage = max(5, min(100, (int) $request->query('per_page', 50)));

        $query = Ekstrakurikuler::with('guru');
        if ($search) {
            $query->where('nama_ekskul', 'like', "%{$search}%");
        }

        $ekskul = $query->paginate($perPage)->withQueryString();
        $guruList = Guru::orderBy('nama_guru')->get();

        return view('master.ekskul', compact('ekskul', 'guruList', 'search', 'perPage'));
    }

    public function storeEkskul(Request $request)
    {
        $request->validate([
            'nama_ekskul' => 'required|string|max:100',
            'id_guru' => 'nullable|exists:guru,id_guru',
            'jadwal' => 'nullable|string|max:100',
            'status' => 'nullable|in:Aktif,Nonaktif',
        ]);

        Ekstrakurikuler::create([
            'nama_ekskul' => $request->nama_ekskul,
            'id_guru' => $request->id_guru ?: null,
            'jadwal' => $request->jadwal,
            'status' => $request->status ?: 'Aktif',
        ]);

        return back()->with('success', 'Data ekstrakurikuler berhasil ditambahkan.');
    }

    public function updateEkskul(Request $request, $id)
    {
        $ekskul = Ekstrakurikuler::findOrFail($id);
        $request->validate([
            'nama_ekskul' => 'required|string|max:100',
            'id_guru' => 'nullable|exists:guru,id_guru',
            'jadwal' => 'nullable|string|max:100',
            'status' => 'nullable|in:Aktif,Nonaktif',
        ]);

        $ekskul->update([
            'nama_ekskul' => $request->nama_ekskul,
            'id_guru' => $request->id_guru ?: null,
            'jadwal' => $request->jadwal,
            'status' => $request->status ?: 'Aktif',
        ]);

        return back()->with('success', 'Data ekstrakurikuler berhasil diperbarui.');
    }

    public function destroyEkskul($id)
    {
        $ekskul = Ekstrakurikuler::findOrFail($id);
        if ($ekskul->nilaiEkstrakurikuler()->exists()) {
            return back()->with('error', 'Ekstrakurikuler tidak dapat dihapus karena sudah memiliki data nilai siswa terkait.');
        }

        $ekskul->delete();

        return back()->with('success', 'Data ekstrakurikuler berhasil dihapus.');
    }
}
