<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi Master Data inti yang WAJIB ada di sistem (Guru, Kelas, Semester, Mapel, Ekskul, Kriteria EWS, dan Akun Default).
     */
    public function run(): void
    {
        // 1. GURU
        $guruData = [
            ['id_guru' => 1, 'nip' => '198001012005011001', 'nama_guru' => 'Endang Sulistian, S.Pd.', 'jenis_guru' => 'Guru Tetap', 'jabatan' => 'Guru Mata Pelajaran', 'status' => 'Aktif'],
            ['id_guru' => 2, 'nip' => '198205122008012003', 'nama_guru' => 'Siti Aminah, M.Pd.', 'jenis_guru' => 'Guru Tetap', 'jabatan' => 'Guru Mata Pelajaran', 'status' => 'Aktif'],
            ['id_guru' => 3, 'nip' => '197803152002121002', 'nama_guru' => 'Budi Santoso, S.Pd.', 'jenis_guru' => 'Guru Tetap', 'jabatan' => 'Guru Mata Pelajaran', 'status' => 'Aktif'],
            ['id_guru' => 4, 'nip' => '197511232001011005', 'nama_guru' => 'Agus Supriyanto, S.Pd.', 'jenis_guru' => 'Guru Tetap', 'jabatan' => 'Guru Mata Pelajaran', 'status' => 'Aktif'],
            ['id_guru' => 5, 'nip' => '198506142010012008', 'nama_guru' => 'Indah Parniwi, S.Pd.', 'jenis_guru' => 'Guru Tetap', 'jabatan' => 'Guru Mata Pelajaran', 'status' => 'Aktif'],
            ['id_guru' => 6, 'nip' => '198701012006042001', 'nama_guru' => 'Erni Safna, S.Pd.', 'jenis_guru' => 'Guru Tetap', 'jabatan' => 'Guru Mata Pelajaran', 'status' => 'Aktif'],
        ];
        foreach ($guruData as $g) {
            DB::table('guru')->updateOrInsert(['id_guru' => $g['id_guru']], $g);
        }

        // 2. KELAS
        $kelasData = [
            ['id_kelas' => 1, 'nama_kelas' => 'VII A', 'tingkat' => 7, 'tahun_ajaran' => '2026/2027', 'id_wali_kelas' => 1],
            ['id_kelas' => 2, 'nama_kelas' => 'VII G', 'tingkat' => 7, 'tahun_ajaran' => '2026/2027', 'id_wali_kelas' => 3],
            ['id_kelas' => 3, 'nama_kelas' => 'VIII A', 'tingkat' => 8, 'tahun_ajaran' => '2026/2027', 'id_wali_kelas' => 4],
            ['id_kelas' => 4, 'nama_kelas' => 'VIII G', 'tingkat' => 8, 'tahun_ajaran' => '2026/2027', 'id_wali_kelas' => 5],
            ['id_kelas' => 5, 'nama_kelas' => 'IX A', 'tingkat' => 9, 'tahun_ajaran' => '2026/2027', 'id_wali_kelas' => 6],
            ['id_kelas' => 6, 'nama_kelas' => 'XI F', 'tingkat' => 8, 'tahun_ajaran' => '2026/2027', 'id_wali_kelas' => 2],
        ];
        foreach ($kelasData as $k) {
            DB::table('kelas')->updateOrInsert(['id_kelas' => $k['id_kelas']], $k);
        }

        // 3. SEMESTER
        $semesterData = [
            ['id_semester' => 1, 'nama_semester' => 'Ganjil', 'tahun_ajaran' => '2026/2027'],
            ['id_semester' => 2, 'nama_semester' => 'Genap', 'tahun_ajaran' => '2026/2027'],
        ];
        foreach ($semesterData as $s) {
            DB::table('semester')->updateOrInsert(['id_semester' => $s['id_semester']], $s);
        }

        // 4. MATA PELAJARAN
        $mapelData = [
            ['id_mapel' => 1, 'kode_mapel' => null, 'nama_mapel' => 'Matematika', 'kategori' => 'Muatan Nasional', 'tingkat' => 'VII, VIII, IX', 'id_guru' => 3],
            ['id_mapel' => 2, 'kode_mapel' => null, 'nama_mapel' => 'Bahasa Indonesia', 'kategori' => 'Muatan Nasional', 'tingkat' => 'VII, VIII, IX', 'id_guru' => 1],
            ['id_mapel' => 3, 'kode_mapel' => null, 'nama_mapel' => 'Bahasa Inggris', 'kategori' => 'Muatan Nasional', 'tingkat' => 'VII, VIII, IX', 'id_guru' => 2],
            ['id_mapel' => 4, 'kode_mapel' => null, 'nama_mapel' => 'Ilmu Pengetahuan Alam (IPA)', 'kategori' => 'Muatan Nasional', 'tingkat' => 'VII, VIII, IX', 'id_guru' => 4],
            ['id_mapel' => 5, 'kode_mapel' => null, 'nama_mapel' => 'Ilmu Pengetahuan Sosial (IPS)', 'kategori' => 'Muatan Nasional', 'tingkat' => 'VII, VIII, IX', 'id_guru' => 5],
            ['id_mapel' => 6, 'kode_mapel' => null, 'nama_mapel' => 'Pendidikan Kewarganegaraan (PKn)', 'kategori' => 'Muatan Nasional', 'tingkat' => 'VII, VIII, IX', 'id_guru' => 6],
        ];
        foreach ($mapelData as $m) {
            DB::table('mata_pelajaran')->updateOrInsert(['id_mapel' => $m['id_mapel']], $m);
        }

        // 5. EKSTRAKURIKULER
        $ekskulData = [
            ['id_ekskul' => 1, 'nama_ekskul' => 'Pramuka', 'id_guru' => null, 'jadwal' => null, 'status' => 'Aktif'],
            ['id_ekskul' => 2, 'nama_ekskul' => 'PMR', 'id_guru' => null, 'jadwal' => null, 'status' => 'Aktif'],
            ['id_ekskul' => 3, 'nama_ekskul' => 'Tari Tradisional', 'id_guru' => null, 'jadwal' => null, 'status' => 'Aktif'],
            ['id_ekskul' => 4, 'nama_ekskul' => 'Futsal', 'id_guru' => null, 'jadwal' => null, 'status' => 'Aktif'],
            ['id_ekskul' => 5, 'nama_ekskul' => 'Paskibra', 'id_guru' => null, 'jadwal' => null, 'status' => 'Aktif'],
            ['id_ekskul' => 6, 'nama_ekskul' => 'Bola Basket', 'id_guru' => null, 'jadwal' => null, 'status' => 'Aktif'],
        ];
        foreach ($ekskulData as $e) {
            DB::table('ekstrakurikuler')->updateOrInsert(['id_ekskul' => $e['id_ekskul']], $e);
        }

        // 6. USERS DEFAULT
        $usersData = [
            [
                'id_user' => 1,
                'username' => 'operator',
                'password' => Hash::make('password'),
                'role' => 'Operator',
                'id_guru' => null,
            ],
            [
                'id_user' => 2,
                'username' => 'walikelas',
                'password' => Hash::make('password'),
                'role' => 'Wali Kelas',
                'id_guru' => 1, // Endang Sulistian, S.Pd. (Wali Kelas VII A)
            ],
            [
                'id_user' => 3,
                'username' => 'gurumapel',
                'password' => Hash::make('password'),
                'role' => 'Guru Mapel',
                'id_guru' => 2, // Siti Aminah, M.Pd. (Pengampu Bahasa Inggris)
            ],
        ];
        foreach ($usersData as $u) {
            DB::table('users')->updateOrInsert(['id_user' => $u['id_user']], $u);
        }

        // 7. KRITERIA ANALISIS (EWS)
        $kriteriaData = [
            ['id_kriteria' => 1, 'nama_kriteria' => 'Nilai Turun Signifikan', 'jenis' => 'Nilai', 'parameter' => 'Rata-rata nilai turun > 10%', 'nilai_ambang' => 10.00, 'aktif' => 1],
            ['id_kriteria' => 2, 'nama_kriteria' => 'Kehadiran Alpha Berlebih', 'jenis' => 'Kehadiran', 'parameter' => '> 5 hari tanpa keterangan', 'nilai_ambang' => 3.00, 'aktif' => 1],
            ['id_kriteria' => 3, 'nama_kriteria' => 'Di Bawah KKM Minimal', 'jenis' => 'Nilai', 'parameter' => 'Nilai di bawah KKM pada >= 3 mapel', 'nilai_ambang' => 75.00, 'aktif' => 1],
        ];
        foreach ($kriteriaData as $kr) {
            DB::table('kriteria_analisis')->updateOrInsert(['id_kriteria' => $kr['id_kriteria']], $kr);
        }
    }
}
