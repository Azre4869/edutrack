<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SimulasiDataSeeder extends Seeder
{
    /**
     * Seed utama simulasi data realistis & terkontrol untuk EduTrack.
     * Digunakan sebagai bahan latih dan uji fitur analisis berbasis machine learning & EWS.
     */
    public function run(): void
    {
        // 1. ENVIRONMENT GUARD
        // Menolak berjalan di production kecuali SIM_ALLOW=true
        if (app()->environment('production') && !in_array(env('SIM_ALLOW'), [true, 'true', '1', 1], true)) {
            throw new \RuntimeException('PENGAMAN KEAMANAN: SimulasiDataSeeder dilarang dijalankan di environment production tanpa SIM_ALLOW=true.');
        }

        $this->command?->info('=== MEMULAI SEEDING DATA SIMULASI EDUTRACK ===');

        // 2. DETERMINISTIC SEED (Mencegah non-deterministik)
        mt_srand(2026);

        // 3. GENERATE KALENDER KERJA (Senin - Jumat)
        // Semester 2: 60 hari kerja terakhir s.d. hari ini
        // Semester 1: 90 hari kerja sebelum awal semester 2
        $sem2Days = $this->generateWorkdays(now(), 60);
        $sem1StartRef = Carbon::parse($sem2Days[0])->subDay();
        $sem1Days = $this->generateWorkdays($sem1StartRef, 90);

        $this->command?->info(sprintf(
            'Kalender Kerja: Sem 1 (%s s.d. %s, %d hari), Sem 2 (%s s.d. %s, %d hari)',
            $sem1Days[0],
            end($sem1Days),
            count($sem1Days),
            $sem2Days[0],
            end($sem2Days),
            count($sem2Days)
        ));

        // 4. GENERATE / SINKRONISASI SISWA
        // Target: 26 siswa per kelas (6 kelas x 26 = 156 siswa)
        // 34 siswa lama dipertahankan, 122 siswa baru fiktif ditambahkan
        $siswaMap = $this->seedSiswa();
        $totalSiswa = count($siswaMap);
        $this->command?->info("Total Siswa terdaftar: {$totalSiswa} siswa di 6 kelas.");

        // 5. STRATIFIED ASSIGNMENT PERSONA SISWA
        // Siswa lama: id 1 -> P6, id 2 -> P4, id 3 -> P1, id 4 -> P5, id 5 -> P4
        $personaAssignments = $this->assignPersonas($siswaMap);

        // 6. EKSPOR LABEL PERSONA KE CSV (Hanya untuk evaluasi model ML)
        $this->exportPersonaCsv($personaAssignments, $siswaMap);

        // 7. SEED ABSENSI HARIAN (Per Siswa per Hari)
        $this->seedAbsensi($siswaMap, $personaAssignments, $sem1Days, $sem2Days);

        // 8. SEED NILAI MATA PELAJARAN (12 Mapel x 2 Semester)
        // Menjaga baris lama dari DummyDataSeeder
        $this->seedNilai($siswaMap, $personaAssignments);

        // 9. SEED CAPAIAN KOMPETENSI (Semester 2 yang nilai_akhir !== null)
        $this->seedCapaianKompetensi();

        // 10. SEED NILAI HARIAN (Mapel 1-4, prefix [SIM])
        $this->seedNilaiHarian($siswaMap, $personaAssignments, $sem1Days, $sem2Days);

        // 11. SEED CATATAN WALI KELAS & SIKAP
        $this->seedCatatanWali($siswaMap, $personaAssignments);

        // 12. SEED NILAI EKSTRAKURIKULER (~80% siswa)
        $this->seedNilaiEkskul($siswaMap, $personaAssignments);

        // 13. SEED PRESTASI SISWA (~15% siswa: P1 & P2)
        $this->seedPrestasi($siswaMap, $personaAssignments);

        // 14. TAMPILKAN RINGKASAN DI CONSOLE
        $this->displaySummary($siswaMap, $personaAssignments);

        $this->command?->info('=== SEEDING DATA SIMULASI SELESAI DENGAN SUKSES ===');
    }

    /**
     * Menghasilkan array tanggal hari kerja (Senin - Jumat) mundur secara deterministik.
     *
     * @return array<string> Format 'Y-m-d' berurutan kronologis (terlama ke terbaru)
     */
    protected function generateWorkdays(Carbon $referenceDate, int $count): array
    {
        $days = [];
        $current = $referenceDate->copy()->startOfDay();

        while ($current->isWeekend()) {
            $current->subDay();
        }

        while (count($days) < $count) {
            if (!$current->isWeekend()) {
                $days[] = $current->toDateString();
            }
            $current->subDay();
        }

        return array_reverse($days);
    }

    /**
     * Sinkronisasi data siswa: mempertahankan 34 siswa lama dan melengkapi tiap kelas menjadi 26 siswa.
     *
     * @return array<int, object> Map id_siswa => data siswa
     */
    protected function seedSiswa(): array
    {
        // Ambil siswa yang sudah ada
        $existing = DB::table('siswa')->orderBy('id_siswa')->get()->keyBy('id_siswa')->toArray();

        // Cari NIS tertinggi saat ini
        $maxNis = 16422;
        foreach ($existing as $s) {
            if (is_numeric($s->nis) && (int)$s->nis > $maxNis) {
                $maxNis = (int)$s->nis;
            }
        }

        // Daftar kelas target: 1=VII A, 2=VII G, 3=VIII A, 4=VIII G, 5=IX A, 6=VIII F
        $targetCountPerKelas = 26;
        $kelasCount = [];
        foreach ($existing as $s) {
            $kelasCount[$s->id_kelas] = ($kelasCount[$s->id_kelas] ?? 0) + 1;
        }

        // Nama-nama khas Madura / Jawa Timur Indonesia fiktif
        $namaLaki = [
            'Mohammad Syafi\'i', 'Achmad Fauzan', 'Zainal Arifin', 'Khoirul Anam', 'Fathur Rahman',
            'Moh. Ilham Maulana', 'Hasan Basri', 'Slamet Riyadi', 'Moh. Rizky Pratama', 'Achmad Wildan',
            'Fahrur Rozi', 'Moh. Ali Ridho', 'Dwi Cahyo', 'Dimas Aditya', 'Bagus Prasetyo',
            'Achmad Dani', 'Moh. Hafidz', 'Farhan Alfarizi', 'M. Syahrul Ramadhan', 'Moh. Wahyu',
            'Achmad Subhan', 'M. Zaki Mubarak', 'Hendra Kurniawan', 'Moh. Taufiq', 'Agus Salim',
            'Moh. Fadli', 'Achmad Naufal', 'Moh. Yusuf', 'Rendy Tri Saputra', 'M. Faris Al-Baqir',
            'Bayu Segara', 'Moh. Fikri', 'Achmad Zaini', 'Choirul Huda', 'Moh. Bahrul Ulum',
            'Irfan Hakim', 'Mohammad Bilal', 'Faris Ihsan', 'Zulham Efendi', 'Bachtiar Aris',
            'Moh. Hasanuddin', 'Imron Rosyadi', 'Dani Firmansyah', 'Samsul Muarif', 'Ilham Habibi',
            'Zulfikar Ali', 'Mohammad Rofi\'i', 'Alfan Farizi', 'Hadi Sucipto', 'Mohammad Ansori',
            'M. Danial Faruq', 'Rifqi Pratama', 'Wildan Firmansyah', 'Syahrul Gunawan', 'Habibur Rahman',
            'Mohammad Alfin', 'Rahmat Hidayatullah', 'Achmad Sulton', 'Mohammad Kholil', 'Faisol Amir',
            'M. Lukman Hakim', 'Arif Munandar', 'Mohammad Idris', 'Badrut Tamam', 'Zainur Ridho'
        ];

        $namaPerempuan = [
            'Nurul Komariyah', 'Siti Nurhaliza', 'Dewi Anggraini', 'Putri Maulida', 'Fatimatuz Zahra',
            'Lailatul Fitriyah', 'Anisa Rahmawati', 'Wardatul Jannah', 'Riska Amalia', 'Uswatun Khasanah',
            'Nur Aini', 'Fitri Handayani', 'Cindy Claudia', 'Diah Ayu Lestari', 'Siti Maimunah',
            'Nur Indah Sari', 'Qurrotul Aini', 'Mar\'atus Sholihah', 'Nadia Safitri', 'Novita Sari',
            'Linda Permatasari', 'Bella Shofia', 'Siti Maryam', 'Farah Diba', 'Khoirun Nisa',
            'Desi Ratnasari', 'Wahyu Ningsih', 'Siti Rohmah', 'Aisyah Putri', 'Khofifah Indah',
            'Zahrotul Jamilah', 'Siti Maghfiroh', 'Intan Permata', 'Nailul Farihah', 'Tri Wahyuni',
            'Siti Fadilah', 'Rina Agustina', 'Mufidah Laili', 'Dian Safira', 'Nurul Hikmah',
            'Fitrotul Hasanah', 'Kurnia Dewi', 'Siti Fatimah', 'Rizka Nur Aulia', 'Fadhilah Nur',
            'Husnul Khotimah', 'Nur Laila', 'Alfi Choirun', 'Siti Khodijah', 'Farida Ariyani',
            'Ratna Sari', 'Ika Nuraini', 'Luluk Mukarromah', 'Siti Aminatun', 'Nurlina Sari',
            'Wulan Dari', 'Siti Zubaidah', 'Indah Kusuma', 'Rani Permatasari', 'Suci Rahmadani'
        ];

        $jalanList = [
            'Jl. Trunojoyo', 'Jl. Rajawali', 'Jl. Jaksa Agung Suprapto', 'Jl. Hasyim Asy\'ari',
            'Jl. Syamsul Arifin', 'Jl. Pemuda Baru', 'Jl. Wachid Hasyim', 'Jl. Imam Bonjol',
            'Jl. Panglima Sudirman', 'Jl. KH. Agus Salim', 'Jl. Diponegoro', 'Jl. Garuda',
            'Jl. Melati', 'Jl. Mawar', 'Jl. Kusuma Bangsa', 'Jl. Kenanga'
        ];

        $sekolahAsalList = [
            'SDN Rongtengah 1 Sampang', 'SDN Rongtengah 2 Sampang', 'SDN Gunung Sekar 1 Sampang',
            'SDN Gunung Sekar 2 Sampang', 'SDN Dalpenang 1 Sampang', 'SDN Banyuanyar 1 Sampang',
            'SDN Karang Dalem 1', 'MI Negeri 1 Sampang', 'SD Plus Darul Ulum Sampang'
        ];

        $pekerjaanOrtu = ['Wiraswasta', 'PNS', 'Petani', 'Pedagang', 'Karyawan Swasta', 'Nelayan', 'Guru Honorer'];
        $pekerjaanIbu = ['Ibu Rumah Tangga', 'Pedagang', 'PNS', 'Wiraswasta', 'Guru', 'Bidan'];

        $newSiswaRecords = [];
        $currentNis = $maxNis;
        $namaLakiIdx = 0;
        $namaPerempuanIdx = 0;

        for ($kelasId = 1; $kelasId <= 6; $kelasId++) {
            $currentCount = $kelasCount[$kelasId] ?? 0;
            $needed = $targetCountPerKelas - $currentCount;

            // Tentukan tingkat berdasarkan kelas
            // Kelas 1 (VII A) & 2 (VII G) = tingkat 7
            // Kelas 3 (VIII A), 4 (VIII G), 6 (VIII F) = tingkat 8
            // Kelas 5 (IX A) = tingkat 9
            $tingkat = match ($kelasId) {
                1, 2 => 7,
                3, 4, 6 => 8,
                5 => 9,
                default => 7,
            };

            $birthYear = match ($tingkat) {
                7 => 2013,
                8 => 2012,
                9 => 2011,
            };

            for ($i = 0; $i < $needed; $i++) {
                $currentNis++;
                $jk = ($i % 2 === 0) ? 'L' : 'P';
                if ($jk === 'L') {
                    $nama = $namaLaki[$namaLakiIdx % count($namaLaki)];
                    $namaLakiIdx++;
                } else {
                    $nama = $namaPerempuan[$namaPerempuanIdx % count($namaPerempuan)];
                    $namaPerempuanIdx++;
                }

                $birthMonth = ($i % 12) + 1;
                $birthDay = ($i % 27) + 1;
                $tglLahir = sprintf('%04d-%02d-%02d', $birthYear, $birthMonth, $birthDay);

                $jl = $jalanList[($currentNis) % count($jalanList)];
                $noRumah = (($currentNis * 3) % 120) + 1;
                $alamat = "{$jl} No. {$noRumah}, Sampang";

                $newSiswaRecords[] = [
                    'nis' => (string)$currentNis,
                    'nisn' => '00' . substr((string)$currentNis, -4) . sprintf('%04d', $currentNis % 10000),
                    'nama' => $nama,
                    'jenis_kelamin' => $jk,
                    'tempat_lahir' => 'Sampang',
                    'tanggal_lahir' => $tglLahir,
                    'agama' => 'Islam',
                    'status_anak' => ($i % 15 === 0) ? 'Anak Angkat' : 'Anak Kandung',
                    'anak_ke' => ($i % 4) + 1,
                    'alamat' => $alamat,
                    'no_telepon' => '08' . sprintf('%010d', 1234000000 + $currentNis),
                    'sekolah_asal' => $sekolahAsalList[($currentNis) % count($sekolahAsalList)],
                    'kelas_diterima' => 'VII',
                    'tanggal_diterima' => '2026-07-14',
                    'nama_ayah' => 'H. ' . $namaLaki[($namaLakiIdx + 5) % count($namaLaki)],
                    'nama_ibu' => 'Hj. ' . $namaPerempuan[($namaPerempuanIdx + 5) % count($namaPerempuan)],
                    'pekerjaan_ayah' => $pekerjaanOrtu[$i % count($pekerjaanOrtu)],
                    'pekerjaan_ibu' => $pekerjaanIbu[$i % count($pekerjaanIbu)],
                    'alamat_ortu' => $alamat,
                    'telp_ortu' => '08' . sprintf('%010d', 2345000000 + $currentNis),
                    'status_siswa' => 'Aktif',
                    'id_kelas' => $kelasId,
                ];
            }
        }

        // Insert bulk per 100 baris
        if (!empty($newSiswaRecords)) {
            foreach (array_chunk($newSiswaRecords, 100) as $chunk) {
                DB::table('siswa')->insert($chunk);
            }
        }

        // Return semua siswa lengkap terurut id_siswa
        return DB::table('siswa')
            ->join('kelas', 'siswa.id_kelas', '=', 'kelas.id_kelas')
            ->select('siswa.*', 'kelas.nama_kelas')
            ->orderBy('siswa.id_siswa')
            ->get()
            ->keyBy('id_siswa')
            ->toArray();
    }

    /**
     * Membagi siswa ke dalam 8 persona secara STRATIFIED per kelas.
     * Mengikuti proporsi target:
     * P1: 20%, P2: 33%, P3: 8%, P4: 10%, P5: 10%, P6: 8%, P7: 7%, P8: 4%
     * Siswa lama yang wajib: id 1->P6, id 2->P4, id 3->P1, id 4->P5, id 5->P4.
     *
     * @param array<int, object> $siswaMap
     * @return array<int, string> Map id_siswa => kode_persona
     */
    protected function assignPersonas(array $siswaMap): array
    {
        $assignments = [];

        // Aturan paten siswa lama
        $mandatory = [
            1 => 'P6',
            2 => 'P4',
            3 => 'P1',
            4 => 'P5',
            5 => 'P4',
        ];

        // Kelompokkan siswa per kelas
        $perKelas = [];
        foreach ($siswaMap as $idSiswa => $s) {
            $perKelas[$s->id_kelas][] = $idSiswa;
        }

        // Kuota stratified per kelas (masing-masing kelas 26 siswa)
        // Total 6 kelas x 26 = 156 siswa:
        // P1: 5,5,5,5,5,6 = 31 (19.9%)
        // P2: 9,9,8,8,9,8 = 51 (32.7%)
        // P3: 2,2,2,2,2,2 = 12 (7.7%)
        // P4: 3,2,3,3,2,3 = 16 (10.3%)
        // P5: 3,3,2,3,3,2 = 16 (10.3%)
        // P6: 2,2,2,2,2,2 = 12 (7.7%)
        // P7: 1,2,3,2,2,1 = 11 (7.1%)
        // P8: 1,1,1,1,1,1 = 7  (4.5%)
        $kelasQuota = [
            1 => ['P1' => 5, 'P2' => 9, 'P3' => 2, 'P4' => 3, 'P5' => 3, 'P6' => 2, 'P7' => 1, 'P8' => 1],
            2 => ['P1' => 5, 'P2' => 9, 'P3' => 2, 'P4' => 2, 'P5' => 3, 'P6' => 2, 'P7' => 2, 'P8' => 1],
            3 => ['P1' => 5, 'P2' => 8, 'P3' => 2, 'P4' => 3, 'P5' => 2, 'P6' => 2, 'P7' => 3, 'P8' => 1],
            4 => ['P1' => 5, 'P2' => 8, 'P3' => 2, 'P4' => 3, 'P5' => 3, 'P6' => 2, 'P7' => 2, 'P8' => 1],
            5 => ['P1' => 5, 'P2' => 9, 'P3' => 2, 'P4' => 2, 'P5' => 3, 'P6' => 2, 'P7' => 2, 'P8' => 1],
            6 => ['P1' => 6, 'P2' => 8, 'P3' => 2, 'P4' => 3, 'P5' => 2, 'P6' => 2, 'P7' => 1, 'P8' => 1],
        ];

        foreach ($perKelas as $kelasId => $siswaIds) {
            $quota = $kelasQuota[$kelasId] ?? ['P1' => 5, 'P2' => 9, 'P3' => 2, 'P4' => 3, 'P5' => 3, 'P6' => 2, 'P7' => 1, 'P8' => 1];

            // 1. Assign mandatory students in this class
            $remainingIds = [];
            foreach ($siswaIds as $idSiswa) {
                if (isset($mandatory[$idSiswa])) {
                    $persona = $mandatory[$idSiswa];
                    $assignments[$idSiswa] = $persona;
                    if (isset($quota[$persona]) && $quota[$persona] > 0) {
                        $quota[$persona]--;
                    }
                } else {
                    $remainingIds[] = $idSiswa;
                }
            }

            // 2. Expand remaining quota pool
            $pool = [];
            foreach ($quota as $p => $count) {
                for ($k = 0; $k < $count; $k++) {
                    $pool[] = $p;
                }
            }

            // Pad pool if needed to match remaining count
            while (count($pool) < count($remainingIds)) {
                $pool[] = 'P2';
            }

            // Assign sequentially (deterministic order)
            foreach ($remainingIds as $idx => $idSiswa) {
                $assignments[$idSiswa] = $pool[$idx];
            }
        }

        return $assignments;
    }

    /**
     * Tulis label persona ke database/seeders/data/persona_label.csv.
     * File ini HANYA untuk evaluasi model data science / ML nantinya, tidak disimpan di DB.
     */
    protected function exportPersonaCsv(array $personaAssignments, array $siswaMap): void
    {
        $dir = database_path('seeders/data');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $filePath = $dir . '/persona_label.csv';
        $handle = fopen($filePath, 'w');

        // Header CSV
        fputcsv($handle, ['id_siswa', 'nis', 'nama', 'kelas', 'persona']);

        foreach ($personaAssignments as $idSiswa => $persona) {
            $siswa = $siswaMap[$idSiswa] ?? null;
            fputcsv($handle, [
                $idSiswa,
                $siswa->nis ?? '',
                $siswa->nama ?? '',
                $siswa->nama_kelas ?? '',
                $persona,
            ]);
        }

        fclose($handle);
        $this->command?->info("Label persona berhasil diekspor ke: {$filePath}");
    }

    /**
     * Mengisi absensi harian (id_mapel = 1) untuk 150 hari kerja (Sem 1: 90 hari, Sem 2: 60 hari).
     * Status Hadir/Izin/Sakit/Alpha diatur presisi sesuai profil persona.
     */
    protected function seedAbsensi(array $siswaMap, array $personaAssignments, array $sem1Days, array $sem2Days): void
    {
        $this->command?->info('Mempersiapkan data absensi harian...');

        $absensiRows = [];
        $p2SubCounter = 0; // Untuk membagi P2 menjadi Sub-A (Normal, 0 alpha) dan Sub-B (Kecil, 1 alpha)
        $p4Counter = 0;    // Untuk membagi P4 (Tinggi vs Sedang)

        foreach ($siswaMap as $idSiswa => $siswa) {
            $persona = $personaAssignments[$idSiswa] ?? 'P2';

            // ==================== SEMESTER 1 (90 hari) ====================
            $sem1AbsenPlan = $this->planAbsensiSem1($idSiswa, $persona, count($sem1Days));
            foreach ($sem1Days as $idx => $tanggal) {
                $status = $sem1AbsenPlan[$idx] ?? 'Hadir';
                $absensiRows[] = [
                    'id_siswa'    => $idSiswa,
                    'id_semester' => 1,
                    'tanggal'     => $tanggal,
                    'status'      => $status,
                ];

                if (count($absensiRows) >= 500) {
                    DB::table('absensi')->upsert($absensiRows, ['id_siswa', 'id_semester', 'tanggal'], ['status']);
                    $absensiRows = [];
                }
            }

            // ==================== SEMESTER 2 (60 hari) ====================
            if ($persona === 'P2') {
                $p2SubCounter++;
            } elseif ($persona === 'P4') {
                $p4Counter++;
            }

            $sem2AbsenPlan = $this->planAbsensiSem2($idSiswa, $persona, count($sem2Days), $p2SubCounter, $p4Counter);
            foreach ($sem2Days as $idx => $tanggal) {
                $status = $sem2AbsenPlan[$idx] ?? 'Hadir';
                $absensiRows[] = [
                    'id_siswa'    => $idSiswa,
                    'id_semester' => 2,
                    'tanggal'     => $tanggal,
                    'status'      => $status,
                ];

                if (count($absensiRows) >= 500) {
                    DB::table('absensi')->upsert($absensiRows, ['id_siswa', 'id_semester', 'tanggal'], ['status']);
                    $absensiRows = [];
                }
            }
        }

        if (!empty($absensiRows)) {
            DB::table('absensi')->upsert($absensiRows, ['id_siswa', 'id_semester', 'tanggal'], ['status']);
        }

        $this->command?->info('Absensi harian berhasil disimpan secara idempoten.');
    }

    /**
     * Rencana absensi Semester 1 (90 hari) per persona.
     */
    protected function planAbsensiSem1(int $idSiswa, string $persona, int $totalDays): array
    {
        $plan = array_fill(0, $totalDays, 'Hadir');

        switch ($persona) {
            case 'P1': // Unggul stabil: 98-100%, 0 alpha
                $plan[($idSiswa * 7) % $totalDays] = 'Sakit';
                break;

            case 'P2': // Menengah stabil: 95-98%, 0-1 alpha
                $plan[($idSiswa * 11) % $totalDays] = 'Sakit';
                $plan[(($idSiswa * 11) + 1) % $totalDays] = 'Sakit';
                if ($idSiswa % 4 === 0) {
                    $plan[($idSiswa * 13) % $totalDays] = 'Izin';
                }
                break;

            case 'P3': // Hadir rajin: 96-99%, 0 alpha
                $plan[($idSiswa * 9) % $totalDays] = 'Sakit';
                $plan[(($idSiswa * 9) + 1) % $totalDays] = 'Sakit';
                break;

            case 'P4': // Sering alpha di sem 2, sem 1 sudah mulai alpha 2-3 hari
                $plan[($idSiswa * 5) % $totalDays] = 'Alpha';
                $plan[($idSiswa * 17) % $totalDays] = 'Alpha';
                $plan[($idSiswa * 23) % $totalDays] = 'Sakit';
                $plan[(($idSiswa * 23) + 1) % $totalDays] = 'Sakit';
                break;

            case 'P5': // Nilai menurun di sem 2, sem 1 hadir baik (0-1 alpha)
                $plan[($idSiswa * 19) % $totalDays] = 'Izin';
                $plan[($idSiswa * 29) % $totalDays] = 'Sakit';
                break;

            case 'P6': // Berisiko ganda: sem 1 sudah ada alpha 3-4 hari
                $plan[($idSiswa * 3) % $totalDays] = 'Alpha';
                $plan[(($idSiswa * 3) + 1) % $totalDays] = 'Alpha';
                $plan[($idSiswa * 31) % $totalDays] = 'Alpha';
                $plan[($idSiswa * 41) % $totalDays] = 'Sakit';
                $plan[(($idSiswa * 41) + 1) % $totalDays] = 'Sakit';
                break;

            case 'P7': // Membaik di sem 2; di sem 1 punya 2-4 hari Alpha
                $plan[($idSiswa * 7) % $totalDays] = 'Alpha';
                $plan[(($idSiswa * 7) + 1) % $totalDays] = 'Alpha';
                $plan[($idSiswa * 37) % $totalDays] = 'Alpha';
                $plan[($idSiswa * 47) % $totalDays] = 'Sakit';
                break;

            case 'P8': // Mendadak jatuh di sem 2; di sem 1 hadir 100% prima
                $plan[($idSiswa * 13) % $totalDays] = 'Izin';
                break;
        }

        return $plan;
    }

    /**
     * Rencana absensi Semester 2 (60 hari) per persona.
     * Mengatur persentase kehadiran dan jumlah Alpha untuk target EWS:
     * - Normal: Alpha = 0, Hadir >= 90%
     * - Kecil: Alpha 1-2, Hadir >= 90%
     * - Sedang: Alpha 3-4
     * - Tinggi: Alpha >= 5
     */
    protected function planAbsensiSem2(int $idSiswa, string $persona, int $totalDays, int $p2Counter, int $p4Counter): array
    {
        $plan = array_fill(0, $totalDays, 'Hadir');

        switch ($persona) {
            case 'P1': // Unggul stabil: Hadir 98-100%, Alpha = 0
                if ($idSiswa % 2 === 0) {
                    $plan[($idSiswa * 5) % $totalDays] = 'Izin';
                }
                break;

            case 'P2': // Menengah stabil: 52 siswa total
                // 43 siswa: Alpha = 0 -> EWS Normal
                // 9 siswa: Alpha = 1 -> EWS Kecil
                $isSubB = ($p2Counter % 6 === 0);
                $plan[($idSiswa * 7) % $totalDays] = 'Sakit';
                if ($isSubB) {
                    $plan[($idSiswa * 17) % $totalDays] = 'Alpha'; // 1 Alpha memicu EWS Kecil
                } else {
                    $plan[($idSiswa * 13) % $totalDays] = 'Izin';
                }
                break;

            case 'P3': // Hadir tapi nilai rendah: Hadir 96-98%, Alpha = 0 (Risiko berasal dari nilai < KKM)
                $plan[($idSiswa * 9) % $totalDays] = 'Sakit';
                $plan[(($idSiswa * 9) + 1) % $totalDays] = 'Sakit';
                break;

            case 'P4': // Sering alpha: 16 siswa
                // 8 siswa: Alpha 5-7 hari -> EWS Tinggi (critical override)
                // 2 siswa: Alpha 3-4 hari -> EWS Sedang (memicu ABSENSI_ALPHA tanpa critical override)
                // 6 siswa: Alpha 1-2 hari -> EWS Kecil
                if ($p4Counter <= 8) {
                    $alphaCount = 5 + ($idSiswa % 3); // 5-7 hari -> Tinggi
                } elseif ($p4Counter <= 10) {
                    $alphaCount = 3 + ($idSiswa % 2); // 3-4 hari -> Sedang
                } else {
                    $alphaCount = 1 + ($idSiswa % 2); // 1-2 hari -> Kecil
                }

                // Buat cluster alpha beruntun (misal 2-3 hari)
                $clusterStart = ($idSiswa * 7) % ($totalDays - 5);
                for ($a = 0; $a < min(3, $alphaCount); $a++) {
                    $plan[$clusterStart + $a] = 'Alpha';
                }
                // Sisa alpha disebar
                for ($a = 3; $a < $alphaCount; $a++) {
                    $pos = ($clusterStart + 10 + ($a * 6)) % $totalDays;
                    $plan[$pos] = 'Alpha';
                }
                $plan[($idSiswa * 11) % $totalDays] = 'Sakit';
                break;

            case 'P5': // Nilai menurun: Hadir 93-97%, Alpha 0-1
                $plan[($idSiswa * 13) % $totalDays] = 'Sakit';
                $plan[(($idSiswa * 13) + 1) % $totalDays] = 'Sakit';
                if ($idSiswa % 4 === 0) {
                    $plan[($idSiswa * 23) % $totalDays] = 'Alpha'; // 4 siswa mendapat 1 Alpha -> EWS Kecil
                }
                break;

            case 'P6': // Berisiko ganda: Alpha 5-8 hari (beruntun Senin/Jumat), Hadir 78-86% -> EWS Tinggi
                $alphaCount = 5 + ($idSiswa % 4); // 5 s.d. 8
                $clusterStart = ($idSiswa * 3) % ($totalDays - 8);
                // Cluster beruntun 3 hari
                for ($a = 0; $a < 3; $a++) {
                    $plan[$clusterStart + $a] = 'Alpha';
                }
                for ($a = 3; $a < $alphaCount; $a++) {
                    $pos = ($clusterStart + 8 + ($a * 5)) % $totalDays;
                    $plan[$pos] = 'Alpha';
                }
                $plan[($idSiswa * 17) % $totalDays] = 'Sakit';
                $plan[(($idSiswa * 17) + 1) % $totalDays] = 'Sakit';
                break;

            case 'P7': // Membaik: di sem 2 Alpha = 0, Hadir 97-100% -> EWS Normal
                if ($idSiswa % 2 === 0) {
                    $plan[($idSiswa * 11) % $totalDays] = 'Izin';
                }
                break;

            case 'P8': // Perubahan mendadak: 50 hari pertama hadir 100%, 10 hari terakhir ada Alpha 1-2 hari -> EWS Kecil
                // 10 hari terakhir: index 50 s.d. 59
                $alphaLast = 1 + ($idSiswa % 2); // 1 atau 2 hari
                for ($a = 0; $a < $alphaLast; $a++) {
                    $plan[50 + (($idSiswa + $a * 2) % 10)] = 'Alpha';
                }
                $plan[55] = 'Sakit';
                break;
        }

        return $plan;
    }

    /**
     * Mengisi nilai mata pelajaran (12 Mapel x 2 Semester).
     * Sesuai ketentuan:
     * - Nilai yang sudah ada dari DummyDataSeeder TIDAK DITIMPA.
     * - ns = rata-rata TP yang terisi (round 2 decimal).
     * - nilai_akhir = round((ns + sas)/2, 2) jika ns & sas keduanya ada.
     * - Sem 1: 6-8 TP terisi, SAS terisi lengkap.
     * - Sem 2: 4-6 TP terisi, SAS terisi ~95% (~5% NULL untuk skenario rapor belum lengkap).
     */
    protected function seedNilai(array $siswaMap, array $personaAssignments): void
    {
        $this->command?->info('Mempersiapkan data nilai 12 mapel...');

        // Bersihkan baris nilai & capaian simulasi terdahulu (id > 18) agar parameter simulasi tersegarkan idempoten,
        // namun TETAP mempertahankan 18 baris nilai asli bawaan DummyDataSeeder (id_nilai 1 s.d. 18).
        DB::table('capaian_kompetensi')->where('id_capaian', '>', 12)->delete();
        DB::table('nilai')->where('id_nilai', '>', 18)->delete();

        // Ambil kombinasi data bawaan DummyDataSeeder yang WAJIB TIDAK DITIMPA
        $existingNilai = DB::table('nilai')
            ->where('id_nilai', '<=', 18)
            ->select('id_siswa', 'id_mapel', 'id_semester')
            ->get()
            ->map(fn($r) => "{$r->id_siswa}_{$r->id_mapel}_{$r->id_semester}")
            ->flip()
            ->toArray();

        $mapelIds = DB::table('mata_pelajaran')->orderBy('id_mapel')->pluck('id_mapel')->toArray();
        if (empty($mapelIds)) {
            $mapelIds = range(1, 12);
        }

        $nilaiRows = [];
        $totalInserted = 0;

        foreach ($siswaMap as $idSiswa => $siswa) {
            $persona = $personaAssignments[$idSiswa] ?? 'P2';

            foreach ([1, 2] as $semesterId) {
                foreach ($mapelIds as $mapelId) {
                    $key = "{$idSiswa}_{$mapelId}_{$semesterId}";
                    if (isset($existingNilai[$key])) {
                        // Jangan timpa data lama!
                        continue;
                    }

                    $row = $this->generateNilaiRow($idSiswa, $mapelId, $semesterId, $persona);
                    $nilaiRows[] = $row;

                    if (count($nilaiRows) >= 500) {
                        DB::table('nilai')->insert($nilaiRows);
                        $totalInserted += count($nilaiRows);
                        $nilaiRows = [];
                    }
                }
            }
        }

        if (!empty($nilaiRows)) {
            DB::table('nilai')->insert($nilaiRows);
            $totalInserted += count($nilaiRows);
        }

        $this->command?->info("Nilai berhasil disimpan: {$totalInserted} baris baru ditambahkan (data lama tetap utuh).");
    }

    /**
     * Menghasilkan record nilai sesuai karakteristik persona dan semester.
     */
    protected function generateNilaiRow(int $idSiswa, int $mapelId, int $semesterId, string $persona): array
    {
        // Variasi mapel acak deterministik (ada mapel kuat / lemah +-5 poin)
        $mapelBias = (($idSiswa * 17 + $mapelId * 31) % 11) - 5;

        // Tentukan jumlah TP
        // Semester 1: 6 s.d. 8 TP
        // Semester 2: 4 s.d. 6 TP
        $tpCount = ($semesterId === 1) ? (6 + ($idSiswa + $mapelId) % 3) : (4 + ($idSiswa + $mapelId) % 3);

        $tpScores = [];
        $sas = null;

        // Base score generator per persona
        switch ($persona) {
            case 'P1': // Unggul stabil: 88-97
                $base = 90 + ($idSiswa % 5) + $mapelBias;
                $base = max(86, min(97, $base));
                for ($k = 1; $k <= $tpCount; $k++) {
                    $tpScores[$k] = max(85, min(98, $base + (($k % 3) - 1)));
                }
                $sas = max(86, min(97, $base + (($idSiswa % 3) - 1)));
                break;

            case 'P2': // Menengah stabil: 76-88
                $base = 80 + ($idSiswa % 6) + $mapelBias;
                $base = max(77, min(88, $base));
                for ($k = 1; $k <= $tpCount; $k++) {
                    $tpScores[$k] = max(76, min(89, $base + (($k % 3) - 1)));
                }
                $sas = max(76, min(88, $base));
                break;

            case 'P3': // Hadir tapi nilai rendah: 2-3 mapel < 75 (misal 68-73), sisanya 76-80 (rata-rata ~73-74)
                $isWeakMapel = in_array($mapelId, [1, 3, 4]); // MTK, BING, IPA di bawah KKM 75
                if ($isWeakMapel) {
                    $base = 70 + ($idSiswa % 3); // 70-72 (< 75)
                } else {
                    $base = 76 + ($idSiswa % 4); // 76-79 (>= 75)
                }
                for ($k = 1; $k <= $tpCount; $k++) {
                    $tpScores[$k] = max(65, min(82, $base + (($k % 3) - 1)));
                }
                $sas = max(65, min(80, $base));
                break;

            case 'P4': // Sering alpha: Sem 1 nilai 80-84, Sem 2 nilai turun 3-5 poin (76-80), tetap tuntas KKM
                if ($semesterId === 1) {
                    $base = 82 + ($idSiswa % 3);
                } else {
                    $base = 78 + ($idSiswa % 3); // Turun 4 poin, semua nilai tetap >= 75
                }
                for ($k = 1; $k <= $tpCount; $k++) {
                    $tpScores[$k] = max(76, min(86, $base + (($k % 3) - 1)));
                }
                $sas = max(76, min(85, $base));
                break;

            case 'P5': // Nilai menurun:
                // 6 siswa: Sem 1 rata 88, Sem 2 turun 13% ke 76 (semua mapel >= 75) -> EWS Sedang (1 kriteria)
                // 10 siswa: Sem 1 rata 86, Sem 2 turun 8% ke 79 -> EWS Normal / Kecil
                if ($semesterId === 1) {
                    $base = 86 + ($idSiswa % 4); // 86-89
                    for ($k = 1; $k <= $tpCount; $k++) {
                        $tpScores[$k] = max(83, min(92, $base + (($k % 3) - 1)));
                    }
                    $sas = max(84, min(90, $base));
                } else {
                    $isDrop13 = in_array($idSiswa, [24, 54, 78, 102, 126, 150]); // Tepat 6 siswa mengalami penurunan >= 10%
                    if ($isDrop13) {
                        // Rata-rata 76 (turun ~13%), semua nilai dijaga >= 75 agar TIDAK memicu mapel < 75
                        $base = 76;
                        for ($k = 1; $k <= $tpCount; $k++) {
                            $tpScores[$k] = 75 + (($k + $idSiswa) % 3); // 75, 76, 77
                        }
                        $sas = 76;
                    } else {
                        // Turun ~8% (di bawah ambang batas 10%)
                        $base = 79 + ($idSiswa % 3);
                        for ($k = 1; $k <= $tpCount; $k++) {
                            $tpScores[$k] = max(76, min(83, $base + (($k % 3) - 1)));
                        }
                        $sas = max(77, min(82, $base));
                    }
                }
                break;

            case 'P6': // Berisiko ganda: Sem 1 67-73, Sem 2 60-69 (rata-rata < 70, 3-5 mapel < 75)
                if ($semesterId === 1) {
                    $base = 70 + ($idSiswa % 4) + ($mapelBias % 3);
                } else {
                    $base = 64 + ($idSiswa % 4) + $mapelBias;
                }
                $base = max(58, min(74, $base));
                for ($k = 1; $k <= $tpCount; $k++) {
                    $tpScores[$k] = max(55, min(75, $base + (($k % 3) - 1)));
                }
                $sas = max(56, min(73, $base));
                break;

            case 'P7': // Membaik: Sem 1 66-72, Sem 2 naik 10-18% (78-85)
                if ($semesterId === 1) {
                    $base = 69 + ($idSiswa % 4) + ($mapelBias % 3);
                } else {
                    $base = 80 + ($idSiswa % 5) + $mapelBias; // Naik signifikan
                }
                $base = max(65, min(88, $base));
                for ($k = 1; $k <= $tpCount; $k++) {
                    $tpScores[$k] = max(64, min(89, $base + (($k % 3) - 1)));
                }
                $sas = max(65, min(88, $base));
                break;

            case 'P8': // Perubahan mendadak: rata-rata semesteran tetap baik (80-88), anjlok di nilai_harian & akhir
                $base = 84 + ($idSiswa % 5) + $mapelBias;
                $base = max(78, min(90, $base));
                for ($k = 1; $k <= $tpCount; $k++) {
                    $tpScores[$k] = max(78, min(90, $base));
                }
                $sas = max(78, min(90, $base));
                break;
        }

        // Hitung NS murni sesuai rumus aplikasi: rata-rata TP yang terisi
        $ns = round(array_sum($tpScores) / count($tpScores), 2);

        // Kasus "Rapor belum lengkap": pada semester 2, ~5% baris sengaja dibuat SAS = null
        if ($semesterId === 2) {
            // Sengaja null untuk siswa dengan id % 19 === 0 pada mapel 10 & 11
            if (($idSiswa % 19 === 0) && in_array($mapelId, [10, 11])) {
                $sas = null;
            }
        }

        // Hitung Nilai Akhir murni: round((ns + sas) / 2, 2)
        $nilaiAkhir = (!is_null($ns) && !is_null($sas)) ? round(($ns + $sas) / 2, 2) : null;

        return [
            'id_siswa'    => $idSiswa,
            'id_mapel'    => $mapelId,
            'id_semester' => $semesterId,
            'tp1'         => $tpScores[1] ?? null,
            'tp2'         => $tpScores[2] ?? null,
            'tp3'         => $tpScores[3] ?? null,
            'tp4'         => $tpScores[4] ?? null,
            'tp5'         => $tpScores[5] ?? null,
            'tp6'         => $tpScores[6] ?? null,
            'tp7'         => $tpScores[7] ?? null,
            'tp8'         => $tpScores[8] ?? null,
            'ns'          => $ns,
            'sas'         => $sas,
            'nilai_akhir' => $nilaiAkhir,
        ];
    }

    /**
     * Mengisi capaian kompetensi 1:1 untuk nilai semester 2 yang sudah terisi nilai_akhir-nya.
     */
    protected function seedCapaianKompetensi(): void
    {
        $this->command?->info('Mempersiapkan capaian kompetensi semester 2...');

        $existingCapaian = DB::table('capaian_kompetensi')->pluck('id_nilai')->flip()->toArray();

        $nilaiList = DB::table('nilai')
            ->join('mata_pelajaran', 'nilai.id_mapel', '=', 'mata_pelajaran.id_mapel')
            ->where('nilai.id_semester', 2)
            ->whereNotNull('nilai.nilai_akhir')
            ->select('nilai.id_nilai', 'nilai.nilai_akhir', 'mata_pelajaran.nama_mapel')
            ->get();

        $capaianRows = [];
        $totalInserted = 0;

        foreach ($nilaiList as $n) {
            if (isset($existingCapaian[$n->id_nilai])) {
                continue;
            }

            $score = (float)$n->nilai_akhir;
            $namaM = $n->nama_mapel;

            if ($score >= 85.0) {
                $d1 = "Menunjukkan penguasaan yang sangat baik dalam memahami konsep utama dan mempraktikkan keterampilan {$namaM}.";
                $d2 = "Perlu mempertahankan konsistensi belajar serta memperluas pengayaan pada materi lanjutan {$namaM}.";
            } elseif ($score >= 75.0) {
                $d1 = "Menunjukkan pemahaman yang baik dalam menguasai capaian pembelajaran esensial {$namaM}.";
                $d2 = "Perlu bimbingan dan latihan lebih intensif pada pemecahan soal analitis {$namaM}.";
            } else {
                $d1 = "Mulai menunjukkan pemahaman dasar pada materi pokok pembelajaran {$namaM}.";
                $d2 = "Perlu pendampingan khusus dan bimbingan remedial terjadwal dalam materi mendasar {$namaM}.";
            }

            $capaianRows[] = [
                'id_nilai'    => $n->id_nilai,
                'deskripsi_1' => $d1,
                'deskripsi_2' => $d2,
            ];

            if (count($capaianRows) >= 500) {
                DB::table('capaian_kompetensi')->insert($capaianRows);
                $totalInserted += count($capaianRows);
                $capaianRows = [];
            }
        }

        if (!empty($capaianRows)) {
            DB::table('capaian_kompetensi')->insert($capaianRows);
            $totalInserted += count($capaianRows);
        }

        $this->command?->info("Capaian kompetensi berhasil disimpan: {$totalInserted} baris baru.");
    }

    /**
     * Mengisi nilai harian untuk mapel 1-4 (MTK, BIND, BING, IPA), 6-8 entri per siswa per semester.
     * Menggunakan awalan '[SIM] ' agar idempoten (dihapus dan di-generate ulang).
     */
    protected function seedNilaiHarian(array $siswaMap, array $personaAssignments, array $sem1Days, array $sem2Days): void
    {
        $this->command?->info('Menyegarkan data nilai harian simulasi [SIM]...');

        // Bersihkan entri simulasi lama agar idempoten tanpa duplikasi
        DB::table('nilai_harian')->where('keterangan', 'like', '[SIM]%')->delete();

        $harianMapel = [1, 2, 3, 4]; // Mapel 1-4
        $harianRows = [];
        $totalInserted = 0;

        $kegiatanList = [
            '[SIM] Kuis 1',
            '[SIM] Tugas Mandiri 1',
            '[SIM] Latihan Terstruktur',
            '[SIM] Ulangan Harian 1',
            '[SIM] Tugas Kelompok',
            '[SIM] Ulangan Harian 2',
            '[SIM] Penilaian Proyek',
        ];

        foreach ($siswaMap as $idSiswa => $siswa) {
            $persona = $personaAssignments[$idSiswa] ?? 'P2';

            foreach ([1, 2] as $semesterId) {
                $workdays = ($semesterId === 1) ? $sem1Days : $sem2Days;
                $dayStep = max(1, (int)(count($workdays) / (count($kegiatanList) + 1)));

                foreach ($harianMapel as $mapelId) {
                    $base = match ($persona) {
                        'P1' => 90 + ($idSiswa % 5),
                        'P2' => 80 + ($idSiswa % 6),
                        'P3' => ($mapelId === 1 || $mapelId === 4) ? 68 + ($idSiswa % 5) : 78,
                        'P4' => ($semesterId === 1) ? 80 : 75,
                        'P5' => ($semesterId === 1) ? 86 : 74,
                        'P6' => ($semesterId === 1) ? 72 : 64,
                        'P7' => ($semesterId === 1) ? 69 : 82,
                        'P8' => 86 + ($idSiswa % 4),
                    };

                    foreach ($kegiatanList as $kIdx => $kegiatan) {
                        $dayIndex = min(count($workdays) - 1, ($kIdx + 1) * $dayStep);
                        $tanggal = $workdays[$dayIndex];

                        $score = $base + (($kIdx % 3) - 1) * 2;

                        // Perilaku khusus P8: 3 nilai harian terakhir di semester 2 turun drastis (selisih >= 20 poin)
                        if ($persona === 'P8' && $semesterId === 2 && $kIdx >= (count($kegiatanList) - 3)) {
                            $score = max(50, $score - 25);
                        }

                        $score = max(50, min(100, $score));

                        $harianRows[] = [
                            'id_siswa'    => $idSiswa,
                            'id_mapel'    => $mapelId,
                            'id_semester' => $semesterId,
                            'tanggal'     => $tanggal,
                            'nilai'       => round($score, 2),
                            'keterangan'  => $kegiatan,
                        ];

                        if (count($harianRows) >= 500) {
                            DB::table('nilai_harian')->insert($harianRows);
                            $totalInserted += count($harianRows);
                            $harianRows = [];
                        }
                    }
                }
            }
        }

        if (!empty($harianRows)) {
            DB::table('nilai_harian')->insert($harianRows);
            $totalInserted += count($harianRows);
        }

        $this->command?->info("Nilai harian berhasil disimpan: {$totalInserted} baris entri simulasi.");
    }

    /**
     * Mengisi catatan wali kelas & sikap untuk 2 semester.
     * Menggunakan minimal 4 variasi kalimat per persona dan konsisten dengan persona.
     */
    protected function seedCatatanWali(array $siswaMap, array $personaAssignments): void
    {
        $this->command?->info('Mempersiapkan catatan wali kelas & sikap...');

        $variasi = [
            'P1' => [
                'spiritual' => [
                    'Selalu taat beribadah, menunjukkan akhlak mulia dan senantiasa bersyukur.',
                    'Sangat rajin dalam menjalankan ibadah harian serta menjadi teladan dalam bertutur kata.',
                    'Konsisten menunjukkan sikap religius, tawadhu, dan menghargai keberagaman.',
                    'Menunjukkan kebiasaan beribadah yang sangat baik serta berakhlak terpuji di sekolah.'
                ],
                'sosial' => [
                    'Sangat santun, aktif bekerja sama dalam kelompok, dan memiliki empati tinggi.',
                    'Menunjukkan kepemimpinan positif, ramah, dan disukai oleh teman-teman sekelas.',
                    'Mampu berkomunikasi dengan santun, toleran, dan selalu siap membantu sesama.',
                    'Menghormati guru, peduli pada kebersihan kelas, serta memiliki integritas tinggi.'
                ],
                'wali' => [
                    'Prestasi akademik luar biasa. Pertahankan kedisiplinan dan semangat belajarmu.',
                    'Pencapaian belajar sangat membanggakan. Teruslah rendah hati dan kembangkan potensimu.',
                    'Hasil evaluasi sangat memuaskan di semua mata pelajaran. Sukses selalu dalam meraih cita-cita.',
                    'Menunjukkan komitmen belajar yang luar biasa sepanjang semester ini. Pertahankan!'
                ],
            ],
            'P2' => [
                'spiritual' => [
                    'Taat menjalankan ibadah sesuai ketentuan dan menunjukkan sikap santun.',
                    'Selalu mematuhi norma agama serta bersikap hormat kepada guru dan teman.',
                    'Menjalankan ibadah dengan tertib serta memiliki kejujuran yang patut diapresiasi.',
                    'Menunjukkan kepribadian religius dan bersikap sopan dalam keseharian.'
                ],
                'sosial' => [
                    'Mampu bersosialisasi dengan baik, ramah, dan mematuhi tata tertib sekolah.',
                    'Kooperatif dalam tugas kelompok dan menjaga kerukunan antar sesama teman.',
                    'Sikap sosial berkembang dengan baik, mampu menghargai pendapat orang lain.',
                    'Memiliki rasa tanggung jawab yang baik terhadap tugas-tugas kelas.'
                ],
                'wali' => [
                    'Prestasi belajar stabil dan memenuhi kriteria tuntas. Tingkatkan partisipasi aktif di kelas.',
                    'Capaian akademik baik dan teratur. Terus asah kemampuan analisis dan penalaran mandiri.',
                    'Hasil belajar semester ini cukup memuaskan. Pertahankan konsistensi dan rajin berlatih.',
                    'Menunjukkan ketekunan yang baik. Lebih aktif dalam diskusi kelompok akan sangat membantu.'
                ],
            ],
            'P3' => [
                'spiritual' => [
                    'Menunjukkan ketaatan beribadah yang baik dan berperilaku sopan santun.',
                    'Selalu mengikuti kegiatan doa bersama dengan khidmat dan patuh pada aturan.',
                    'Berakhlak baik, jujur, serta menunjukkan kepedulian religius yang positif.',
                    'Menjalankan kewajiban ibadah dengan tertib dan menghormati sesama.'
                ],
                'sosial' => [
                    'Hubungan dengan teman sebaya sangat baik, ramah, dan gemar tolong-menolong.',
                    'Dapat berbaur dengan teman secara harmonis dan tidak pernah berselisih paham.',
                    'Sopan kepada bapak/ibu guru dan menunjukkan sikap saling menghargai.',
                    'Mampu bekerja sama dalam kegiatan non-akademik dan gotong royong kelas.'
                ],
                'wali' => [
                    'Kehadiran sangat baik, namun perlu menambah porsi belajar mandiri pada materi hitungan.',
                    'Sikap di kelas sangat tertib, disarankan mengikuti bimbingan tambahan pada mata pelajaran eksak.',
                    'Perlu lebih fokus dan tekun dalam memahami konsep dasar mata pelajaran yang belum tuntas.',
                    'Rajin masuk sekolah, manfaatkan waktu luang untuk membaca ulang catatan pelajaran.'
                ],
            ],
            'P4' => [
                'spiritual' => [
                    'Cukup taat beribadah, perlu terus dibimbing dalam meningkatkan kedisiplinan diri.',
                    'Perilaku ibadah cukup baik, namun perlu konsistensi dalam menjaga ketertiban.',
                    'Menunjukkan sikap santun saat hadir di sekolah, perlu penguatan nilai tanggung jawab.',
                    'Cukup menghormati nilai-nilai keagamaan, tingkatkan kesadaran amanah.'
                ],
                'sosial' => [
                    'Cukup ramah dengan teman, namun perlu meningkatkan kepedulian terhadap aturan sekolah.',
                    'Dapat bergaul wajar di kelas, hindari kebiasaan mengabaikan tata tertib presensi.',
                    'Sikap sosial cukup baik, perlu belajar lebih disiplin dan tepat waktu.',
                    'Komunikasi dengan teman cukup baik, perlu lebih bertanggung jawab atas tugas sekolah.'
                ],
                'wali' => [
                    'Tingkat kehadiran tanpa keterangan perlu perhatian serius dari siswa dan orang tua.',
                    'Kedisiplinan kehadiran adalah kunci utama ketuntasan belajar. Kurangi ketidakhadiran tanpa izin.',
                    'Potensi belajar baik, namun terhambat oleh banyaknya hari absen. Mari perbaiki di semester depan.',
                    'Diharapkan orang tua lebih aktif memantau presensi harian ananda di sekolah.'
                ],
            ],
            'P5' => [
                'spiritual' => [
                    'Menjalankan ibadah dengan baik, senantiasa menjaga sopan santun.',
                    'Taat beribadah dan selalu menghargai teman serta bapak/ibu guru.',
                    'Memiliki dasar keimanan dan akhlak yang baik dalam kehidupan sehari-hari.',
                    'Menunjukkan sikap religius yang stabil dan menghormati lingkungan sekolah.'
                ],
                'sosial' => [
                    'Hubungan dengan teman baik, namun akhir-akhir ini tampak kurang bersemangat.',
                    'Cenderung pasif dalam kegiatan kelas, perlu motivasi untuk lebih percaya diri.',
                    'Sopan dan tidak bermasalah dalam pergaulan, namun interaksi belajar agak menurun.',
                    'Cukup ramah, diharapkan dapat kembali aktif dalam diskusi kelompok.'
                ],
                'wali' => [
                    'Terjadi penurunan hasil belajar dibanding semester lalu. Perlu evaluasi pola belajar di rumah.',
                    'Kemampuan ananda sebenarnya tinggi, jangan biarkan motivasi belajar terus mengendur.',
                    'Dibutuhkan pendampingan belajar yang lebih intensif dari orang tua agar nilai kembali optimal.',
                    'Segera lakukan refleksi dan konsultasi dengan guru mata pelajaran untuk memperbaiki pencapaian.'
                ],
            ],
            'P6' => [
                'spiritual' => [
                    'Perlu bimbingan lebih konsisten dalam pembinaan sikap dan ibadah sehari-hari.',
                    'Diharapkan lebih bersungguh-sungguh dalam mengikuti pembinaan keagamaan di sekolah.',
                    'Perlu bimbingan dalam menumbuhkan kesadaran beribadah dan menjaga adab bicara.',
                    'Tingkatkan ketaatan beribadah agar menjadi rem dalam tindakan sehari-hari.'
                ],
                'sosial' => [
                    'Sering pasif dan kurang memperhatikan tata tertib kelas, perlu pendampingan khusus.',
                    'Perlu pengawasan ekstra dalam pergaulan agar tidak terpengaruh hal-hal negatif.',
                    'Sikap sosial perlu diperbaiki, hindari tindakan yang mengganggu kenyamanan belajar teman.',
                    'Perlu bimbingan guru BK untuk meningkatkan kepatuhan terhadap aturan sekolah.'
                ],
                'wali' => [
                    'Banyaknya ketidakhadiran dan nilai di bawah standar KKM memerlukan penanganan segera bersama orang tua.',
                    'Wajib mengikuti program remedial dan pembinaan intensif agar tidak tertinggal materi kelulusan.',
                    'Perlu perubahan sikap yang signifikan dalam kehadiran dan ketekunan belajar di kelas.',
                    'Perhatian khusus mutlak diperlukan untuk mengejar ketertinggalan nilai dan absensi.'
                ],
            ],
            'P7' => [
                'spiritual' => [
                    'Menunjukkan peningkatan ketaatan beribadah dan sikap yang semakin terpuji.',
                    'Alhamdulillah ada kemajuan pesat dalam kedisiplinan ibadah dan kesantunan bertutur kata.',
                    'Makin rajin dan khusyuk dalam kegiatan keagamaan di sekolah.',
                    'Menunjukkan kematangan karakter religius yang sangat menggembirakan.'
                ],
                'sosial' => [
                    'Semakin percaya diri, aktif, dan mampu beradaptasi positif di lingkungan kelas.',
                    'Menunjukkan antusiasme tinggi dalam bekerja sama dan makin peduli dengan teman.',
                    'Perubahan perilaku yang sangat baik, lebih komunikatif dan bertanggung jawab.',
                    'Menjadi contoh positif bagi teman dalam memperbaiki sikap dan interaksi sosial.'
                ],
                'wali' => [
                    'Perkembangan yang luar biasa membanggakan! Nilai dan kehadiran meningkat pesat.',
                    'Kerja keras dan motivasi belajarmu membuahkan hasil nyata. Pertahankan prestasi ini!',
                    'Lompatan capaian yang sangat positif sepanjang semester ini. Terus maju dan pantang menyerah.',
                    'Sangat mengapresiasi kesungguhan ananda dalam mengejar ketertinggalan. Luar biasa!'
                ],
            ],
            'P8' => [
                'spiritual' => [
                    'Menjalankan ibadah dengan tertib dan memelihara perilaku santun.',
                    'Secara umum taat beribadah, perlu terus didoakan agar selalu istiqomah.',
                    'Memiliki sikap dasar yang religius dan menghargai nilai-nilai kebaikan.',
                    'Taat beribadah, diharapkan nilai-nilai ibadah memberi ketenangan saat menghadapi masalah.'
                ],
                'sosial' => [
                    'Menunjukkan perubahan sikap yang cukup drastis di akhir semester, cenderung menarik diri.',
                    'Awalnya sangat aktif, namun akhir-akhir ini tampak memiliki beban pikiran dan kurang fokus.',
                    'Hubungan dengan teman cukup baik, namun interaksi sosial di penghujung semester menurun.',
                    'Perlu pendekatan personal yang hangat dari wali kelas dan guru BK untuk memahami kondisinya.'
                ],
                'wali' => [
                    'Prestasi awal sangat baik namun mengalami penurunan mendadak di akhir semester. Perlu komunikasi intensif.',
                    'Mohon kerja sama orang tua untuk mencari solusi bersama terkait penurunan konsentrasi ananda.',
                    'Potensi akademik sangat tinggi, segera selesaikan kendala yang dihadapi agar tidak larut.',
                    'Tetap semangat, jangan putus asa menghadapi kesulitan belajar di penghujung semester.'
                ],
            ],
        ];

        foreach ($siswaMap as $idSiswa => $siswa) {
            $persona = $personaAssignments[$idSiswa] ?? 'P2';
            $pData = $variasi[$persona] ?? $variasi['P2'];

            foreach ([1, 2] as $semesterId) {
                $idxSpiritual = ($idSiswa + $semesterId) % count($pData['spiritual']);
                $idxSosial    = ($idSiswa * 2 + $semesterId) % count($pData['sosial']);
                $idxWali      = ($idSiswa * 3 + $semesterId) % count($pData['wali']);

                // Status kenaikan: hampir semua 'Naik', P6 boleh sesekali 'Tinggal' di semester 2
                $statusKenaikan = 'Naik';
                if ($persona === 'P6' && $semesterId === 2 && ($idSiswa % 7 === 0)) {
                    $statusKenaikan = 'Tinggal';
                }

                DB::table('catatan_wali')->updateOrInsert(
                    [
                        'id_siswa'    => $idSiswa,
                        'id_semester' => $semesterId,
                    ],
                    [
                        'sikap_spiritual' => $pData['spiritual'][$idxSpiritual],
                        'sikap_sosial'    => $pData['sosial'][$idxSosial],
                        'catatan_wali'    => $pData['wali'][$idxWali],
                        'status_kenaikan' => $statusKenaikan,
                    ]
                );
            }
        }

        $this->command?->info('Catatan wali kelas & sikap berhasil disimpan.');
    }

    /**
     * Mengisi nilai ekstrakurikuler (~80% siswa mengikuti 1-2 ekskul).
     */
    protected function seedNilaiEkskul(array $siswaMap, array $personaAssignments): void
    {
        $this->command?->info('Mempersiapkan nilai ekstrakurikuler...');

        $ekskulIds = DB::table('ekstrakurikuler')->pluck('id_ekskul')->toArray();
        if (empty($ekskulIds)) {
            $ekskulIds = [1, 2, 3, 4, 5, 6];
        }

        $deskripsiMap = [
            'Sangat Baik' => 'Menunjukkan dedikasi tinggi, kepemimpinan aktif, dan penguasaan teknik yang sangat memuaskan.',
            'Baik'        => 'Aktif mengikuti kegiatan rutin dan mampu mempraktikkan keterampilan dasar dengan baik.',
            'Cukup'       => 'Cukup berpartisipasi dalam latihan dan memahami materi dasar kegiatan.',
        ];

        foreach ($siswaMap as $idSiswa => $siswa) {
            // ~80% siswa memiliki ekskul
            if ($idSiswa % 5 === 0) {
                continue;
            }

            $persona = $personaAssignments[$idSiswa] ?? 'P2';
            $ekskul1 = $ekskulIds[($idSiswa * 3) % count($ekskulIds)];
            $ekskul2 = ($idSiswa % 3 === 0) ? $ekskulIds[(($idSiswa * 3) + 2) % count($ekskulIds)] : null;

            $chosenEkskuls = array_filter([$ekskul1, $ekskul2]);

            foreach ([1, 2] as $semesterId) {
                foreach ($chosenEkskuls as $idEkskul) {
                    $predikat = match ($persona) {
                        'P1' => 'Sangat Baik',
                        'P2', 'P7' => ($idSiswa % 2 === 0) ? 'Sangat Baik' : 'Baik',
                        'P3', 'P5', 'P8' => 'Baik',
                        'P4', 'P6' => ($idSiswa % 2 === 0) ? 'Baik' : 'Cukup',
                    };

                    DB::table('nilai_ekstrakurikuler')->updateOrInsert(
                        [
                            'id_siswa'    => $idSiswa,
                            'id_ekskul'   => $idEkskul,
                            'id_semester' => $semesterId,
                        ],
                        [
                            'predikat'  => $predikat,
                            'deskripsi' => $deskripsiMap[$predikat] ?? 'Berpartisipasi aktif dalam kegiatan.',
                        ]
                    );
                }
            }
        }

        $this->command?->info('Nilai ekstrakurikuler berhasil disimpan.');
    }

    /**
     * Mengisi data prestasi (~15% siswa: terutama persona P1 dan sebagian P2).
     */
    protected function seedPrestasi(array $siswaMap, array $personaAssignments): void
    {
        $this->command?->info('Mempersiapkan data prestasi siswa...');

        $prestasiList = [
            ['nama' => 'Juara 1 Olimpiade Sains Nasional (OSN) IPA', 'jenis' => 'Akademik', 'ket' => 'Tingkat Kabupaten Sampang'],
            ['nama' => 'Juara 2 OSN Matematika', 'jenis' => 'Akademik', 'ket' => 'Tingkat Kabupaten Sampang'],
            ['nama' => 'Juara 1 FLS2N Tari Tradisional Madura', 'jenis' => 'Non-Akademik', 'ket' => 'Tingkat Provinsi Jawa Timur'],
            ['nama' => 'Juara Harapan 1 Lomba Story Telling Bahasa Inggris', 'jenis' => 'Akademik', 'ket' => 'Tingkat Kabupaten'],
            ['nama' => 'Juara 1 O2SN Pencak Silat Putra', 'jenis' => 'Non-Akademik', 'ket' => 'Tingkat Kabupaten'],
            ['nama' => 'Juara 2 Lomba Cerdas Cermat PAI', 'jenis' => 'Akademik', 'ket' => 'Tingkat Kecamatan'],
            ['nama' => 'Juara 3 Lomba Lukis Kaligrafi Islam', 'jenis' => 'Non-Akademik', 'ket' => 'Tingkat Kabupaten'],
            ['nama' => 'Juara 1 Kejuaraan Futsal Pelajar SMP', 'jenis' => 'Non-Akademik', 'ket' => 'Tingkat Madura'],
        ];

        $pIdx = 0;
        foreach ($siswaMap as $idSiswa => $siswa) {
            $persona = $personaAssignments[$idSiswa] ?? 'P2';

            // Siswa lama id 3 sudah punya prestasi dari DummyDataSeeder
            if ($idSiswa === 3) {
                continue;
            }

            // Diberikan hanya untuk P1 (sebagian besar) dan sedikit P2 (~15% total siswa)
            $getsPrestasi = ($persona === 'P1' && ($idSiswa % 2 === 0)) || ($persona === 'P2' && ($idSiswa % 8 === 0));

            if ($getsPrestasi) {
                $pData = $prestasiList[$pIdx % count($prestasiList)];
                $pIdx++;

                DB::table('prestasi')->updateOrInsert(
                    [
                        'id_siswa'      => $idSiswa,
                        'id_semester'   => 2,
                        'nama_prestasi' => $pData['nama'],
                    ],
                    [
                        'jenis'      => $pData['jenis'],
                        'keterangan' => $pData['ket'],
                    ]
                );
            }
        }

        $this->command?->info('Data prestasi siswa berhasil disimpan.');
    }

    /**
     * Menampilkan ringkasan data di console setelah seeding selesai.
     */
    protected function displaySummary(array $siswaMap, array $personaAssignments): void
    {
        $this->command?->newLine();
        $this->command?->info('------------------------------------------------------------');
        $this->command?->info('               RINGKASAN SEEDING DATA SIMULASI              ');
        $this->command?->info('------------------------------------------------------------');

        // 1. Jumlah baris per tabel
        $tables = [
            'siswa',
            'absensi',
            'nilai',
            'nilai_harian',
            'catatan_wali',
            'nilai_ekstrakurikuler',
            'prestasi',
            'capaian_kompetensi',
        ];

        $this->command?->info('1. JUMLAH BARIS PER TABEL:');
        foreach ($tables as $tbl) {
            $count = DB::table($tbl)->count();
            $this->command?->line(sprintf('   - %-25s : %d baris', $tbl, $count));
        }

        // 2. Siswa per kelas
        $this->command?->newLine();
        $this->command?->info('2. JUMLAH SISWA PER KELAS:');
        $kelasList = DB::table('kelas')
            ->leftJoin('siswa', 'kelas.id_kelas', '=', 'siswa.id_kelas')
            ->select('kelas.id_kelas', 'kelas.nama_kelas', DB::raw('count(siswa.id_siswa) as total_siswa'))
            ->groupBy('kelas.id_kelas', 'kelas.nama_kelas')
            ->orderBy('kelas.id_kelas')
            ->get();

        foreach ($kelasList as $k) {
            $this->command?->line(sprintf('   - Kelas %-8s (ID: %d) : %d siswa', $k->nama_kelas, $k->id_kelas, $k->total_siswa));
        }

        // 3. Distribusi Persona
        $this->command?->newLine();
        $this->command?->info('3. DISTRIBUSI PERSONA SISWA:');
        $personaCounts = array_count_values($personaAssignments);
        ksort($personaCounts);
        $totalSiswa = count($personaAssignments);

        $personaNames = [
            'P1' => 'Unggul stabil',
            'P2' => 'Menengah stabil',
            'P3' => 'Hadir tapi nilai rendah',
            'P4' => 'Sering alpha',
            'P5' => 'Nilai menurun',
            'P6' => 'Berisiko ganda',
            'P7' => 'Membaik',
            'P8' => 'Perubahan mendadak',
        ];

        foreach ($personaCounts as $pCode => $count) {
            $pct = round(($count / $totalSiswa) * 100, 2);
            $pName = $personaNames[$pCode] ?? 'Persona';
            $this->command?->line(sprintf('   - %-4s (%-25s) : %2d siswa (%5.2f%%)', $pCode, $pName, $count, $pct));
        }
        $this->command?->info('------------------------------------------------------------');
    }
}
