# Skema Basis Data EduTrack (DATABASE_SCHEMA.md)

Dokumen ini mendokumentasikan struktur lengkap basis data MySQL (`sisfortes`), mencakup tabel domain, kolom, tipe data, kunci primer (PK), kunci asing (FK), batasan unik (UNIQUE), nilai ENUM, dan view basis data.

---

## 1. Ikhtisar Arsitektur Basis Data

- **Nama Basis Data**: `sisfortes` (basis data pengujian: `edutrack_test`).
- **Mesin Penyimpanan**: InnoDB (MySQL / MariaDB).
- **Aturan Kunci Asing (Foreign Keys)**: Seluruh relasi menggunakan `ON DELETE RESTRICT` dan `ON UPDATE RESTRICT` (diterapkan melalui migrasi `2026_11_03_000001_change_foreign_keys_to_restrict_delete.php`) untuk mencegah penghapusan kaskade yang dapat merusak integritas riwayat akademik siswa.
- **Stored Procedures / Functions**: **Tidak ada** (seluruh prosedur lama telah dihapus melalui migrasi `2026_11_03_000000_drop_stored_procedures.php`; logika perhitungan nilai dan analisis risiko sepenuhnya dikelola di layer aplikasi Laravel).
- **View Basis Data**: Terdapat 4 view aktif (`vw_detail_absensi`, `vw_guru_mapel`, `vw_siswa_kelas`, `vw_transkrip_nilai`).

---

## 2. Struktur Tabel Domain

### A. Pengguna & Kepegawaian

#### 1. `users`
Menyimpan akun login pengguna aplikasi.
- `id_user` (INT, PK, AUTO_INCREMENT)
- `username` (VARCHAR(50), NOT NULL, UNIQUE)
- `password` (VARCHAR(255), NOT NULL) - Hash bcrypt
- `role` (ENUM('Operator', 'Guru Mapel', 'Wali Kelas'), NOT NULL)
- `id_guru` (INT, NULL, FK -> `guru.id_guru`) - Terisi jika akun adalah guru/wali kelas

#### 2. `guru`
Menyimpan profil tenaga pendidik dan kependidikan.
- `id_guru` (INT, PK, AUTO_INCREMENT)
- `nip` (VARCHAR(30), NOT NULL, UNIQUE)
- `nama_guru` (VARCHAR(100), NOT NULL)
- `jenis_guru` (ENUM('Guru Tetap', 'Guru Honorer'), DEFAULT 'Guru Tetap')
- `jabatan` (VARCHAR(100), DEFAULT 'Guru Mata Pelajaran')
- `status` (ENUM('Aktif', 'Tidak Aktif'), DEFAULT 'Aktif')

---

### B. Struktur Akademik & Siswa

#### 3. `semester`
Menyimpan periode semester dan tahun ajaran akademik.
- `id_semester` (INT, PK, AUTO_INCREMENT)
- `nama_semester` (VARCHAR(20), NOT NULL) - Contoh: `'Ganjil'`, `'Genap'`
- `tahun_ajaran` (VARCHAR(20), NOT NULL) - Contoh: `'2026/2027'`
- `is_active` (TINYINT(1), NOT NULL, DEFAULT 0) - Penanda semester aktif yang sedang berjalan

#### 4. `kelas`
Menyimpan data rombongan belajar (rombel).
- `id_kelas` (INT, PK, AUTO_INCREMENT)
- `nama_kelas` (VARCHAR(50), NOT NULL) - Contoh: `'VII A'`, `'VIII B'`
- `tingkat` (INT, NOT NULL) - Contoh: `7`, `8`, `9`
- `tahun_ajaran` (VARCHAR(20), NOT NULL)
- `id_wali_kelas` (INT, NULL, FK -> `guru.id_guru`)

#### 5. `siswa`
Menyimpan data profil lengkap peserta didik.
- `id_siswa` (INT, PK, AUTO_INCREMENT)
- `nis` (VARCHAR(30), NOT NULL, UNIQUE)
- `nisn` (VARCHAR(30), NULL)
- `nama` (VARCHAR(100), NOT NULL)
- `jenis_kelamin` (ENUM('L', 'P'), NOT NULL)
- `tempat_lahir` (VARCHAR(50), NULL)
- `tanggal_lahir` (DATE, NULL)
- `agama` (VARCHAR(20), NULL)
- `status_anak` (ENUM('Anak Kandung', 'Anak Angkat', 'Anak Tiri'), DEFAULT 'Anak Kandung')
- `anak_ke` (INT, DEFAULT 1)
- `alamat` (TEXT, NULL)
- `no_telepon` (VARCHAR(20), NULL)
- `sekolah_asal` (VARCHAR(100), NULL)
- `kelas_diterima` (VARCHAR(30), NULL)
- `tanggal_diterima` (DATE, NULL)
- `nama_ayah`, `nama_ibu` (VARCHAR(100), NULL)
- `pekerjaan_ayah`, `pekerjaan_ibu` (VARCHAR(100), NULL)
- `alamat_ortu` (TEXT, NULL), `telp_ortu` (VARCHAR(30), NULL)
- `nama_wali`, `pekerjaan_wali` (VARCHAR(100), NULL)
- `alamat_wali` (TEXT, NULL), `telp_wali` (VARCHAR(30), NULL)
- `status_siswa` (ENUM('Aktif', 'Nonaktif'), DEFAULT 'Aktif')
- `id_kelas` (INT, NOT NULL, FK -> `kelas.id_kelas`)

#### 6. `mata_pelajaran`
Menyimpan daftar mata pelajaran kurikulum.
- `id_mapel` (INT, PK, AUTO_INCREMENT)
- `kode_mapel` (VARCHAR(20), NULL)
- `nama_mapel` (VARCHAR(100), NOT NULL)
- `kategori` (ENUM('Muatan Nasional', 'Muatan Lokal', 'Pilihan/Mulok'), DEFAULT 'Muatan Nasional')
- `tingkat` (VARCHAR(50), DEFAULT 'VII, VIII, IX')
- `id_guru` (INT, NULL, FK -> `guru.id_guru`) - Guru pengampu default

---

### C. Penilaian & Capaian Kurikulum Merdeka

#### 7. `nilai`
Menyimpan nilai sumatif rapor semester per siswa per mata pelajaran.
- `id_nilai` (INT, PK, AUTO_INCREMENT)
- `id_siswa` (INT, NOT NULL, FK -> `siswa.id_siswa`)
- `id_mapel` (INT, NOT NULL, FK -> `mata_pelajaran.id_mapel`)
- `id_semester` (INT, NOT NULL, FK -> `semester.id_semester`)
- `tp1` s/d `tp8` (DECIMAL(5,2), NULL) - Nilai Tujuan Pembelajaran 1–8
- `ns` (DECIMAL(5,2), NULL) - Rata-rata Nilai Sumatif
- `sas` (DECIMAL(5,2), NULL) - Sumatif Akhir Semester
- `nilai_akhir` (DECIMAL(5,2), NOT NULL) - Nilai Akhir Rapor (kombinasi NS dan SAS)
- **Kunci Unik**: `UNIQUE KEY unique_nilai (id_siswa, id_mapel, id_semester)`

#### 8. `capaian_kompetensi`
Deskripsi naratif pencapaian kompetensi pada rapor.
- `id_capaian` (INT, PK, AUTO_INCREMENT)
- `id_nilai` (INT, NOT NULL, UNIQUE, FK -> `nilai.id_nilai`)
- `deskripsi_1` (TEXT, NOT NULL) - Menunjukkan penguasaan yang sangat baik
- `deskripsi_2` (TEXT, NOT NULL) - Menunjukkan penguasaan yang perlu pendampingan

#### 9. `nilai_harian`
Pencatatan nilai tugas/formatif berkala di luar nilai sumatif akhir.
- `id_nilai_harian` (INT, PK, AUTO_INCREMENT)
- `id_siswa` (INT, NOT NULL, FK -> `siswa.id_siswa`)
- `id_mapel` (INT, NOT NULL, FK -> `mata_pelajaran.id_mapel`)
- `id_semester` (INT, NOT NULL, FK -> `semester.id_semester`)
- `tanggal` (DATE, NOT NULL)
- `nilai` (DECIMAL(5,2), NOT NULL)
- `keterangan` (VARCHAR(255), NULL)

---

### D. Kehadiran, Ekstrakurikuler, & Catatan Wali

#### 10. `absensi`
Pencatatan presensi harian siswa oleh wali kelas.
> *Catatan Arsitektur*: Kolom `id_mapel` telah **dihapus**. Presensi murni dicatat per siswa per hari.
- `id_absensi` (INT, PK, AUTO_INCREMENT)
- `id_siswa` (INT, NOT NULL, FK -> `siswa.id_siswa`)
- `id_semester` (INT, NOT NULL, FK -> `semester.id_semester`)
- `tanggal` (DATE, NOT NULL)
- `status` (ENUM('Hadir', 'Izin', 'Sakit', 'Alpha'), NOT NULL)
- **Kunci Unik**: `UNIQUE KEY uq_absensi_siswa_semester_tanggal (id_siswa, id_semester, tanggal)`

#### 11. `ekstrakurikuler`
Daftar kegiatan pengembangan diri/ekskul di sekolah.
- `id_ekskul` (INT, PK, AUTO_INCREMENT)
- `nama_ekskul` (VARCHAR(100), NOT NULL)
- `id_guru` (INT, NULL, FK -> `guru.id_guru`) - Pembina ekskul
- `jadwal` (VARCHAR(100), NULL)
- `status` (ENUM('Aktif', 'Nonaktif'), DEFAULT 'Aktif')

#### 12. `nilai_ekstrakurikuler`
Catatan keikutsertaan dan predikat nilai ekskul siswa per semester.
- `id_nilai_ekskul` (INT, PK, AUTO_INCREMENT)
- `id_siswa` (INT, NOT NULL, FK -> `siswa.id_siswa`)
- `id_ekskul` (INT, NOT NULL, FK -> `ekstrakurikuler.id_ekskul`)
- `id_semester` (INT, NOT NULL, FK -> `semester.id_semester`)
- `predikat` (ENUM('Sangat Baik', 'Baik', 'Cukup', 'Kurang'), NOT NULL)
- `deskripsi` (VARCHAR(255), NULL)

#### 13. `prestasi`
Catatan rekam jejak prestasi akademik maupun non-akademik siswa.
- `id_prestasi` (INT, PK, AUTO_INCREMENT)
- `id_siswa` (INT, NOT NULL, FK -> `siswa.id_siswa`)
- `id_semester` (INT, NOT NULL, FK -> `semester.id_semester`)
- `nama_prestasi` (VARCHAR(100), NOT NULL)
- `jenis` (ENUM('Akademik', 'Non-Akademik'), NOT NULL)
- `keterangan` (VARCHAR(255), NULL)

#### 14. `catatan_wali`
Catatan sikap kepribadian dan rekomendasi kenaikan kelas oleh wali kelas.
- `id_catatan` (INT, PK, AUTO_INCREMENT)
- `id_siswa` (INT, NOT NULL, FK -> `siswa.id_siswa`)
- `id_semester` (INT, NOT NULL, FK -> `semester.id_semester`)
- `sikap_spiritual` (TEXT, NULL)
- `sikap_sosial` (TEXT, NULL)
- `catatan_wali` (TEXT, NULL)
- `status_kenaikan` (ENUM('Naik', 'Tinggal'), DEFAULT 'Naik')
- **Kunci Unik**: `UNIQUE KEY uq_siswa_semester_catatan (id_siswa, id_semester)`

---

### E. Early Warning System (EWS) & Bimbingan Konseling

#### 15. `kriteria_analisis`
Konfigurasi aturan ambang batas dan bobot untuk mendeteksi siswa berisiko.
- `id_kriteria` (INT, PK, AUTO_INCREMENT)
- `kode` (VARCHAR(50), NULL, UNIQUE) - Contoh: `'NILAI_KKM'`, `'ABSENSI_ALPHA'`
- `nama_kriteria` (VARCHAR(100), NOT NULL)
- `jenis` (ENUM('Nilai', 'Kehadiran'), NOT NULL)
- `parameter` (VARCHAR(255), NULL)
- `nilai_ambang` (DECIMAL(5,2), NULL)
- `bobot` (DECIMAL(4,2), NOT NULL, DEFAULT 1.00)
- `operator_perbandingan` (VARCHAR(10), DEFAULT '>=')
- `satuan` (VARCHAR(20), NULL)
- `aktif` (TINYINT(1), NOT NULL, DEFAULT 1)

#### 16. `analisis_log`
Riwayat setiap siklus eksekusi mesin analisis EWS.
- `id_log` (INT, PK, AUTO_INCREMENT)
- `waktu_jalan` (DATETIME, NOT NULL)
- `waktu_selesai` (DATETIME, NULL)
- `pemicu` (ENUM('Manual', 'Terjadwal'), DEFAULT 'Manual')
- `jumlah_siswa_diproses` (INT, DEFAULT 0)
- `total_berisiko` (INT, DEFAULT 0)
- `status` (ENUM('Running', 'Success', 'Failed'), DEFAULT 'Success')
- `pesan_error` (TEXT, NULL)
- `id_user` (INT, NULL, FK -> `users.id_user`)

#### 17. `analisis_siswa`
Hasil klasifikasi risiko per siswa dari suatu siklus log EWS.
- `id_analisis` (INT, PK, AUTO_INCREMENT)
- `id_log` (INT, NOT NULL, FK -> `analisis_log.id_log`)
- `id_siswa` (INT, NOT NULL, FK -> `siswa.id_siswa`)
- `id_semester` (INT, NOT NULL, FK -> `semester.id_semester`)
- `status` (ENUM('Normal', 'Perlu Perhatian'), DEFAULT 'Normal')
- `tingkat_risiko` (ENUM('Tinggi', 'Sedang', 'Kecil', 'Normal'), DEFAULT 'Normal')
- `skor_risiko` (DECIMAL(5,2), DEFAULT 0.00)
- `ringkasan` (VARCHAR(500), NULL)
- `data_snapshot` (LONGTEXT, NULL) - Snapshot JSON saat analisis berlangsung

#### 18. `analisis_kriteria`
Tabel penghubung Many-to-Many antara hasil analisis siswa dengan kriteria yang terpicu.
- `id_analisis` (INT, NOT NULL, FK -> `analisis_siswa.id_analisis`)
- `id_kriteria` (INT, NOT NULL, FK -> `kriteria_analisis.id_kriteria`)
- **Kunci Primer**: `PRIMARY KEY (id_analisis, id_kriteria)`

#### 19. `tindak_lanjut`
Pencatatan sesi bimbingan konseling dan pendampingan siswa berisiko.
- `id_tindak_lanjut` (INT, PK, AUTO_INCREMENT)
- `id_analisis` (INT, NOT NULL, FK -> `analisis_siswa.id_analisis`)
- `id_user` (INT, NOT NULL, FK -> `users.id_user`)
- `id_guru` (INT, NULL, FK -> `guru.id_guru`) - Guru pendamping/konselor
- `tanggal` (DATE, NOT NULL)
- `waktu` (TIME, NULL)
- `jenis_konseling` (VARCHAR(100), NULL)
- `peserta_diundang` (VARCHAR(100), NULL)
- `tempat_media` (VARCHAR(100), NULL)
- `tindakan` (VARCHAR(500), NOT NULL)
- `catatan_konseling` (TEXT, NULL)
- `rencana_dukungan` (TEXT, NULL)
- `status` (ENUM('Belum Ditindak', 'Sedang Diproses', 'Selesai'), DEFAULT 'Belum Ditindak')
- `tanggal_evaluasi` (DATE, NULL)
- `hasil_evaluasi` (TEXT, NULL)
- `skor_efektivitas` (ENUM('Membaik', 'Tetap', 'Memburuk', 'Belum Dievaluasi'), DEFAULT 'Belum Dievaluasi')

#### 20. `notifikasi`
Pemberitahuan otomatis kepada wali kelas dan operator terkait status risiko siswa.
- `id_notifikasi` (INT, PK, AUTO_INCREMENT)
- `id_analisis` (INT, NOT NULL, FK -> `analisis_siswa.id_analisis`)
- `id_user` (INT, NOT NULL, FK -> `users.id_user`)
- `tipe` (VARCHAR(50), DEFAULT 'EWS_ALERT')
- `pesan` (VARCHAR(500), NOT NULL)
- `metadata` (LONGTEXT, NULL)
- `sudah_dibaca` (TINYINT(1), DEFAULT 0)
- `tanggal_kirim` (DATETIME, DEFAULT CURRENT_TIMESTAMP)

---

### F. Integrasi Layanan Kecerdasan Buatan (AI Microservice)

#### 21. `ai_run`
Log eksekusi model machine learning (FastAPI) per semester.
- `id` (INT UNSIGNED, PK, AUTO_INCREMENT)
- `id_log` (INT, NULL, FK -> `analisis_log.id_log`)
- `id_semester` (INT, NOT NULL, FK -> `semester.id_semester`)
- `model_version` (VARCHAR(50), DEFAULT '1.0.0-kmeans-iforest')
- `parameter` (LONGTEXT, NULL) - JSON parameter (k optimal, contamination rate, dll.)
- `metrik` (LONGTEXT, NULL) - JSON metrik evaluasi (silhouette score, ARI, completeness)
- `status` (ENUM('Running', 'Success', 'Failed'), DEFAULT 'Success')
- `dibuat_pada` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

#### 22. `ai_insight`
Wawasan analitik prediktif dan klaster perilaku belajar per siswa.
- `id` (INT UNSIGNED, PK, AUTO_INCREMENT)
- `id_run` (INT UNSIGNED, NOT NULL, FK -> `ai_run.id`)
- `id_siswa` (INT, NOT NULL, FK -> `siswa.id_siswa`)
- `id_semester` (INT, NOT NULL, FK -> `semester.id_semester`)
- `cluster_id` (INT, DEFAULT 0)
- `cluster_label` (VARCHAR(100), NOT NULL) - Contoh: `'Siswa Berprestasi Stabil'`
- `cluster_distance` (DECIMAL(8,4), NULL)
- `pca_x`, `pca_y` (DECIMAL(8,4), NULL) - Titik koordinat proyeksi 2D untuk scatter plot
- `anomaly_score` (DECIMAL(5,4), DEFAULT 0.0000)
- `is_anomaly` (TINYINT(1), DEFAULT 0) - Penanda status anomali
- `alasan` (LONGTEXT, NULL) - JSON teks penjelasan faktor anomali
- `slope_nilai`, `prediksi_nilai` (DECIMAL) - Tren & proyeksi nilai masa depan
- `slope_kehadiran`, `prediksi_kehadiran` (DECIMAL) - Tren & proyeksi kehadiran masa depan
- `berpotensi_turun` (TINYINT(1), DEFAULT 0)
- `data_cukup` (TINYINT(1), DEFAULT 1)
- `dihitung_pada` (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

---

## 3. Tampilan Basis Data (Database Views)

Terdapat 4 view aktif yang dikonfigurasi pada skema basis data:

1. **`vw_detail_absensi`**:
   Menggabungkan presensi harian siswa dengan data nama siswa dan semester.
   ```sql
   SELECT a.tanggal, s.nis, s.nama AS nama_siswa, sm.nama_semester, a.status
   FROM absensi a
   JOIN siswa s ON a.id_siswa = s.id_siswa
   JOIN semester sm ON a.id_semester = sm.id_semester;
   ```
2. **`vw_guru_mapel`**:
   Menampilkan pemetaan guru dengan mata pelajaran yang diampu.
   ```sql
   SELECT g.nip, g.nama_guru, mp.nama_mapel
   FROM mata_pelajaran mp
   JOIN guru g ON mp.id_guru = g.id_guru;
   ```
3. **`vw_siswa_kelas`**:
   Menampilkan daftar siswa beserta kelas, tingkat, dan tahun ajaran rombelnya.
   ```sql
   SELECT s.nis, s.nama AS nama_siswa, s.jenis_kelamin, k.nama_kelas, k.tingkat, k.tahun_ajaran
   FROM siswa s
   JOIN kelas k ON s.id_kelas = k.id_kelas;
   ```
4. **`vw_transkrip_nilai`**:
   Menampilkan rekapitulasi nilai akhir siswa per mata pelajaran dan semester.
   ```sql
   SELECT s.nis, s.nama AS nama_siswa, mp.nama_mapel, sm.nama_semester, sm.tahun_ajaran, n.nilai_akhir
   FROM nilai n
   JOIN siswa s ON n.id_siswa = s.id_siswa
   JOIN mata_pelajaran mp ON n.id_mapel = mp.id_mapel
   JOIN semester sm ON n.id_semester = sm.id_semester;
   ```
