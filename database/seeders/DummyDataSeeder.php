<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Mengisi data simulasi/dummy untuk demo dan pengujian akademik
     * (Siswa fiktif, riwayat presensi harian, nilai sumatif, capaian kompetensi, ekskul, prestasi, EWS & tindak lanjut).
     */
    public function run(): void
    {
        // 1. DATA SISWA (34 Siswa SMPN 1 Sampang)
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

        // 2. LOG & ANALISIS SISWA (EARLY WARNING SYSTEM)
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

        // Link Analisis Kriteria (Pivot)
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 1, 'id_kriteria' => 1]);
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 1, 'id_kriteria' => 3]);
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 2, 'id_kriteria' => 2]);
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 3, 'id_kriteria' => 1]);
        DB::table('analisis_kriteria')->updateOrInsert(['id_analisis' => 4, 'id_kriteria' => 2]);

        // 3. TINDAK LANJUT
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

        // 4. PRESTASI SISWA
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

        // 5. NILAI EKSTRAKURIKULER
        $neData = [
            ['id_nilai_ekskul' => 1, 'id_siswa' => 3, 'id_ekskul' => 1, 'id_semester' => 2, 'predikat' => 'Sangat Baik', 'deskripsi' => 'Sangat aktif dalam kegiatan perkemahan dan kepemimpinan regu.'],
            ['id_nilai_ekskul' => 2, 'id_siswa' => 3, 'id_ekskul' => 3, 'id_semester' => 2, 'predikat' => 'Baik', 'deskripsi' => 'Menguasai gerak dasar tari tradisional dengan baik.'],
            ['id_nilai_ekskul' => 3, 'id_siswa' => 3, 'id_ekskul' => 2, 'id_semester' => 2, 'predikat' => 'Cukup', 'deskripsi' => 'Cukup menguasai materi pertolongan pertama.'],
        ];
        foreach ($neData as $ne) {
            DB::table('nilai_ekstrakurikuler')->updateOrInsert(['id_nilai_ekskul' => $ne['id_nilai_ekskul']], $ne);
        }

        // 6. NILAI & CAPAIAN KOMPETENSI
        $nilaiData = [
            ['id_nilai' => 1, 'id_siswa' => 3, 'id_mapel' => 3, 'id_semester' => 2, 'nilai_akhir' => 90.00],
            ['id_nilai' => 2, 'id_siswa' => 1, 'id_mapel' => 3, 'id_semester' => 2, 'nilai_akhir' => 68.20],
            ['id_nilai' => 3, 'id_siswa' => 6, 'id_mapel' => 3, 'id_semester' => 2, 'nilai_akhir' => 88.00],
            ['id_nilai' => 4, 'id_siswa' => 7, 'id_mapel' => 3, 'id_semester' => 2, 'nilai_akhir' => 92.00],
            ['id_nilai' => 5, 'id_siswa' => 4, 'id_mapel' => 4, 'id_semester' => 1, 'nilai_akhir' => 92.50],
            ['id_nilai' => 6, 'id_siswa' => 5, 'id_mapel' => 3, 'id_semester' => 1, 'nilai_akhir' => 80.00],
            ['id_nilai' => 7, 'id_siswa' => 5, 'id_mapel' => 4, 'id_semester' => 1, 'nilai_akhir' => 85.00],
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

        // 7. NOTIFIKASI
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

        // 8. ABSENSI TODAY (Siswa Kelas VII A)
        $today = now()->toDateString();
        for ($sId = 1; $sId <= 25; $sId++) {
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
