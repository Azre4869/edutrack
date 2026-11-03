<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EduTrackSeeder extends Seeder
{
    public function run(): void
    {
        // 1. GURU
        $guruData = [
            ['id_guru' => 1, 'nip' => '198001012005011001', 'nama_guru' => 'Endang Sulistian, S.Pd.'],
            ['id_guru' => 2, 'nip' => '198205122008012003', 'nama_guru' => 'Siti Aminah, M.Pd.'],
            ['id_guru' => 3, 'nip' => '197803152002121002', 'nama_guru' => 'Budi Santoso, S.Pd.'],
            ['id_guru' => 4, 'nip' => '197511232001011005', 'nama_guru' => 'Agus Supriyanto, S.Pd.'],
            ['id_guru' => 5, 'nip' => '198506142010012008', 'nama_guru' => 'Indah Parniwi, S.Pd.'],
            ['id_guru' => 6, 'nip' => '198701012006042001', 'nama_guru' => 'Erni Safna, S.Pd.'],
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
            ['id_mapel' => 1, 'nama_mapel' => 'Matematika', 'id_guru' => 3],
            ['id_mapel' => 2, 'nama_mapel' => 'Bahasa Indonesia', 'id_guru' => 1],
            ['id_mapel' => 3, 'nama_mapel' => 'Bahasa Inggris', 'id_guru' => 2],
            ['id_mapel' => 4, 'nama_mapel' => 'Ilmu Pengetahuan Alam (IPA)', 'id_guru' => 4],
            ['id_mapel' => 5, 'nama_mapel' => 'Ilmu Pengetahuan Sosial (IPS)', 'id_guru' => 5],
            ['id_mapel' => 6, 'nama_mapel' => 'Pendidikan Kewarganegaraan (PKn)', 'id_guru' => 6],
        ];
        foreach ($mapelData as $m) {
            DB::table('mata_pelajaran')->updateOrInsert(['id_mapel' => $m['id_mapel']], $m);
        }

        // 5. EKSTRAKURIKULER
        $ekskulData = [
            ['id_ekskul' => 1, 'nama_ekskul' => 'Pramuka'],
            ['id_ekskul' => 2, 'nama_ekskul' => 'PMR'],
            ['id_ekskul' => 3, 'nama_ekskul' => 'Tari Tradisional'],
            ['id_ekskul' => 4, 'nama_ekskul' => 'Futsal'],
            ['id_ekskul' => 5, 'nama_ekskul' => 'Paskibra'],
            ['id_ekskul' => 6, 'nama_ekskul' => 'Bola Basket'],
        ];
        foreach ($ekskulData as $e) {
            DB::table('ekstrakurikuler')->updateOrInsert(['id_ekskul' => $e['id_ekskul']], $e);
        }

        // 6. USERS
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
                'id_guru' => 1, // Endang Sulistian, S.Pd.
            ],
            [
                'id_user' => 3,
                'username' => 'gurumapel',
                'password' => Hash::make('password'),
                'role' => 'Guru Mapel',
                'id_guru' => 2, // Siti Aminah, M.Pd.
            ],
        ];
        foreach ($usersData as $u) {
            DB::table('users')->updateOrInsert(['id_user' => $u['id_user']], $u);
        }

        // 7. SISWA
        $siswaData = [
            ['id_siswa' => 1, 'nis' => '16389', 'nama' => 'Firdausi Anam', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 2, 'nis' => '16390', 'nama' => 'Iqbal Maulana', 'jenis_kelamin' => 'L', 'id_kelas' => 6],
            ['id_siswa' => 3, 'nis' => '16391', 'nama' => 'Rani Arisanti Najma', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 4, 'nis' => '16392', 'nama' => 'Fahar Alif', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 5, 'nis' => '16393', 'nama' => 'Lukman Hilman', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 6, 'nis' => '16394', 'nama' => 'Sintya Mariska J.', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 7, 'nis' => '16395', 'nama' => 'Putri Yasmin', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 8, 'nis' => '16396', 'nama' => 'Putri Irma', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 9, 'nis' => '16397', 'nama' => 'Novitasari', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 10, 'nis' => '16398', 'nama' => 'Ardiansyah Feru', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 11, 'nis' => '16399', 'nama' => 'Robby Naufal', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 12, 'nis' => '16400', 'nama' => 'Kiki Nur Alvan', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 13, 'nis' => '16401', 'nama' => 'Revita Sari', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 14, 'nis' => '16402', 'nama' => 'Indira Aiyun', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 15, 'nis' => '16403', 'nama' => 'Naila Amelia', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 16, 'nis' => '16404', 'nama' => 'Bintang Eva Cahyono', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 17, 'nis' => '16405', 'nama' => 'Faris Oktavia', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 18, 'nis' => '16406', 'nama' => 'Rurrfa Izzah', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 19, 'nis' => '16407', 'nama' => 'Kultum Fariz', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 20, 'nis' => '16408', 'nama' => 'Iqbal Susanto', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 21, 'nis' => '16409', 'nama' => 'Asegaf Ikmal Firas', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 22, 'nis' => '16410', 'nama' => 'Ruben Cutami', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 23, 'nis' => '16411', 'nama' => 'Dedi Muyoadi', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 24, 'nis' => '16412', 'nama' => 'Ahmad Fauzi', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 25, 'nis' => '16413', 'nama' => 'Siti Aisyah', 'jenis_kelamin' => 'P', 'id_kelas' => 1],
            ['id_siswa' => 26, 'nis' => '16414', 'nama' => 'Rizky Pratama', 'jenis_kelamin' => 'L', 'id_kelas' => 3],
            ['id_siswa' => 27, 'nis' => '16415', 'nama' => 'Dewi Lestari', 'jenis_kelamin' => 'P', 'id_kelas' => 3],
            ['id_siswa' => 28, 'nis' => '16416', 'nama' => 'Bima Sakti', 'jenis_kelamin' => 'L', 'id_kelas' => 5],
            ['id_siswa' => 29, 'nis' => '16417', 'nama' => 'Nabila Putri', 'jenis_kelamin' => 'P', 'id_kelas' => 5],
            ['id_siswa' => 30, 'nis' => '16418', 'nama' => 'Fahri Ramadhan', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
            ['id_siswa' => 31, 'nis' => '16419', 'nama' => 'Putri Handayani', 'jenis_kelamin' => 'P', 'id_kelas' => 4],
            ['id_siswa' => 32, 'nis' => '16420', 'nama' => 'Agus Setiawan', 'jenis_kelamin' => 'L', 'id_kelas' => 5],
            ['id_siswa' => 33, 'nis' => '16421', 'nama' => 'Laila Sari', 'jenis_kelamin' => 'P', 'id_kelas' => 5],
            ['id_siswa' => 34, 'nis' => '16422', 'nama' => 'Muhammad Rizqi', 'jenis_kelamin' => 'L', 'id_kelas' => 1],
        ];
        foreach ($siswaData as $s) {
            DB::table('siswa')->updateOrInsert(['id_siswa' => $s['id_siswa']], $s);
        }

        // 8. KRITERIA ANALISIS
        $kriteriaData = [
            ['id_kriteria' => 1, 'nama_kriteria' => 'Nilai Turun Signifikan', 'jenis' => 'Nilai', 'parameter' => 'Rata-rata nilai turun > 10%', 'nilai_ambang' => 10.00, 'aktif' => 1],
            ['id_kriteria' => 2, 'nama_kriteria' => 'Kehadiran Alpha Berlebih', 'jenis' => 'Kehadiran', 'parameter' => '> 5 hari tanpa keterangan', 'nilai_ambang' => 3.00, 'aktif' => 1],
            ['id_kriteria' => 3, 'nama_kriteria' => 'Di Bawah KKM Minimal', 'jenis' => 'Nilai', 'parameter' => 'Nilai di bawah KKM pada >= 3 mapel', 'nilai_ambang' => 75.00, 'aktif' => 1],
        ];
        foreach ($kriteriaData as $kr) {
            DB::table('kriteria_analisis')->updateOrInsert(['id_kriteria' => $kr['id_kriteria']], $kr);
        }

        // 9. LOG & ANALISIS SISWA (EARLY WARNING)
        DB::table('analisis_log')->updateOrInsert(
            ['id_log' => 1],
            ['waktu_jalan' => now(), 'pemicu' => 'Manual', 'jumlah_siswa_diproses' => 34]
        );

        $analisisData = [
            [
                'id_analisis' => 1,
                'id_siswa' => 1, // Firdausi Anam
                'id_semester' => 2,
                'id_log' => 1,
                'tanggal_analisis' => now()->toDateString(),
                'status' => 'Perlu Perhatian',
                'ringkasan' => 'Rata-rata nilai turun > 10%, Masih dibawah KKM pada 3 mapel',
            ],
            [
                'id_analisis' => 2,
                'id_siswa' => 2, // Iqbal Maulana
                'id_semester' => 2,
                'id_log' => 1,
                'tanggal_analisis' => now()->toDateString(),
                'status' => 'Perlu Perhatian',
                'ringkasan' => '> 5 hari tanpa keterangan',
            ],
            [
                'id_analisis' => 3,
                'id_siswa' => 4, // Fahar Alif
                'id_semester' => 2,
                'id_log' => 1,
                'tanggal_analisis' => now()->toDateString(),
                'status' => 'Perlu Perhatian',
                'ringkasan' => 'Kehadiran 84%, Nilai Matematika di bawah KKM',
            ],
            [
                'id_analisis' => 4,
                'id_siswa' => 5, // Lukman Hilman
                'id_semester' => 2,
                'id_log' => 1,
                'tanggal_analisis' => now()->toDateString(),
                'status' => 'Perlu Perhatian',
                'ringkasan' => 'Kehadiran kurang dari 85%',
            ],
        ];
        foreach ($analisisData as $ad) {
            DB::table('analisis_siswa')->updateOrInsert(['id_analisis' => $ad['id_analisis']], $ad);
        }

        // Link Analisis Kriteria
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 1, 'id_kriteria' => 1]);
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 1, 'id_kriteria' => 3]);
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 2, 'id_kriteria' => 2]);
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 3, 'id_kriteria' => 1]);
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 4, 'id_kriteria' => 2]);

        // 10. TINDAK LANJUT
        DB::table('tindak_lanjut')->updateOrInsert(
            ['id_tindak_lanjut' => 1],
            [
                'id_analisis' => 1,
                'id_user' => 2,
                'tanggal' => now()->toDateString(),
                'tindakan' => 'Dijadwalkan sesi konseling dengan siswa dan orang tua. Pendampingan belajar dan remedial pada 3 mapel di bawah KKM.',
                'status' => 'Sedang Diproses',
            ]
        );

        // 11. PRESTASI
        $prestasiData = [
            [
                'id_prestasi' => 1,
                'id_siswa' => 3, // Rani Arisanti Najma
                'id_semester' => 2,
                'nama_prestasi' => 'OSN IPA',
                'jenis' => 'Akademik',
                'keterangan' => 'Provinsi',
            ],
            [
                'id_prestasi' => 2,
                'id_siswa' => 3,
                'id_semester' => 2,
                'nama_prestasi' => 'Juara 2 FLS2N Tari Kreasi',
                'jenis' => 'Non-Akademik',
                'keterangan' => 'Kabupaten',
            ],
        ];
        foreach ($prestasiData as $p) {
            DB::table('prestasi')->updateOrInsert(['id_prestasi' => $p['id_prestasi']], $p);
        }

        // 12. NILAI EKSTRAKURIKULER
        $neData = [
            ['id_nilai_ekskul' => 1, 'id_siswa' => 3, 'id_ekskul' => 1, 'id_semester' => 2, 'predikat' => 'Sangat Baik', 'deskripsi' => 'Sangat aktif dalam kegiatan perkemahan dan kepemimpinan regu.'],
            ['id_nilai_ekskul' => 2, 'id_siswa' => 3, 'id_ekskul' => 3, 'id_semester' => 2, 'predikat' => 'Baik', 'deskripsi' => 'Menguasai gerak dasar tari tradisional dengan baik.'],
            ['id_nilai_ekskul' => 3, 'id_siswa' => 3, 'id_ekskul' => 2, 'id_semester' => 2, 'predikat' => 'Cukup', 'deskripsi' => 'Cukup menguasai materi pertolongan pertama.'],
        ];
        foreach ($neData as $ne) {
            DB::table('nilai_ekstrakurikuler')->updateOrInsert(['id_nilai_ekskul' => $ne['id_nilai_ekskul']], $ne);
        }

        // 13. NILAI & CAPAIAN KOMPETENSI
        $nilaiData = [
            ['id_nilai' => 1, 'id_siswa' => 3, 'id_mapel' => 3, 'id_semester' => 2, 'nilai_akhir' => 90.00],
            ['id_nilai' => 2, 'id_siswa' => 1, 'id_mapel' => 3, 'id_semester' => 2, 'nilai_akhir' => 68.20],
            ['id_nilai' => 3, 'id_siswa' => 6, 'id_mapel' => 3, 'id_semester' => 2, 'nilai_akhir' => 88.00],
            ['id_nilai' => 4, 'id_siswa' => 7, 'id_mapel' => 3, 'id_semester' => 2, 'nilai_akhir' => 92.00],
        ];
        foreach ($nilaiData as $nd) {
            DB::table('nilai')->updateOrInsert(['id_nilai' => $nd['id_nilai']], $nd);
        }

        DB::table('capaian_kompetensi')->updateOrInsert(
            ['id_capaian' => 1],
            [
                'id_nilai' => 1,
                'deskripsi_1' => 'Menunjukkan penguasaan yang sangat baik dalam memahami teks laporan hasil observasi dan menyusun teks deskriptif.',
                'deskripsi_2' => 'Perlu bimbingan lebih lanjut dalam pengayaan kosakata bahasa Inggris tingkat lanjut.',
            ]
        );

        // 14. NOTIFIKASI
        $notifData = [
            [
                'id_notifikasi' => 1,
                'id_analisis' => 1,
                'id_user' => 2,
                'pesan' => 'Peringatan ada 5 siswa terdeteksi early warning',
                'sudah_dibaca' => 0,
                'tanggal_kirim' => now(),
            ],
            [
                'id_notifikasi' => 2,
                'id_analisis' => 1,
                'id_user' => 2,
                'pesan' => 'Ujian tengah semester untuk kelas VII & VIII akan dilaksanakan pada 5-10 Oktober 2026.',
                'sudah_dibaca' => 0,
                'tanggal_kirim' => now(),
            ],
            [
                'id_notifikasi' => 3,
                'id_analisis' => 1,
                'id_user' => 2,
                'pesan' => 'Input nilai belum berhasil untuk beberapa mapel pilihan.',
                'sudah_dibaca' => 1,
                'tanggal_kirim' => now()->subDay(),
            ],
            [
                'id_notifikasi' => 4,
                'id_analisis' => 1,
                'id_user' => 2,
                'pesan' => 'Input nilai sedang di proses oleh guru pengampu.',
                'sudah_dibaca' => 1,
                'tanggal_kirim' => now()->subDay(),
            ],
        ];
        foreach ($notifData as $nt) {
            DB::table('notifikasi')->updateOrInsert(['id_notifikasi' => $nt['id_notifikasi']], $nt);
        }

        // 15. ABSENSI TODAY
        $today = now()->toDateString();
        for ($sId = 1; $sId <= 23; $sId++) {
            $status = ($sId == 1 || $sId == 2) ? 'Alpha' : (($sId == 4) ? 'Sakit' : 'Hadir');
            DB::table('absensi')->updateOrInsert(
                [
                    'id_siswa' => $sId,
                    'id_mapel' => 3,
                    'id_semester' => 2,
                    'tanggal' => $today,
                ],
                [
                    'status' => $status,
                ]
            );
        }
    }
}
