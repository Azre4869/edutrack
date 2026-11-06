# Status Terkini Proyek (CURRENT_STATUS.md)

Dokumen ini mencatat fitur yang sudah selesai diimplementasikan, fitur yang sedang berlangsung/tertunda, perbaikan bug terkini, serta prioritas pengembangan sistem **EduTrack**.

Status ini diverifikasi langsung terhadap kode sumber pada branch `testing-zaidan` per **November 2026**.

---

## 1. Fitur yang Telah Terimplementasi Penuh (Confirmed Implementation)

### A. Modul Semester & Tahun Ajaran Aktif (Baru Ditambahkan)
- **Status**: Selesai & Terverifikasi (Lulus Pengujian).
- **Implementasi**:
  - Penambahan kolom `is_active` (boolean) pada tabel `semester`.
  - Helper model dinamis: `Semester::aktif()` di [`app/Models/Semester.php`](file:///e:/tugasprog/edutrack/app/Models/Semester.php) yang mengembalikan semester berstatus aktif (atau fallback ke semester terbaru jika belum ada yang diset).
  - Halaman antarmuka Operator di [`resources/views/master/semester.blade.php`](file:///e:/tugasprog/edutrack/resources/views/master/semester.blade.php) untuk melihat daftar semester, menambah semester/tahun ajaran baru, mengaktifkan semester aktif via tombol satu-klik, serta menghapus semester berstatus arsip.
  - Rute: `operator.semester`, `operator.semester.store`, `operator.semester.set-active`, `operator.semester.destroy`.
  - Otomatisasi pengujian: [`tests/Feature/SemesterManagementTest.php`](file:///e:/tugasprog/edutrack/tests/Feature/SemesterManagementTest.php) (4 tes: akses admin, proteksi role lain, aktivasi satu semester aktif, dan proteksi hapus semester aktif).

### B. Pembersihan `id_mapel` pada Entitas Absensi (Baru Diselesaikan)
- **Status**: Selesai & Terverifikasi.
- **Implementasi**:
  - Kolom `id_mapel` dihapus secara menyeluruh dari skema basis data melalui migrasi [`database/migrations/2026_11_06_000003_remove_id_mapel_from_absensi_table.php`](file:///e:/tugasprog/edutrack/database/migrations/2026_11_06_000003_remove_id_mapel_from_absensi_table.php).
  - Menghapus Foreign Key `fk_absensi_mapel` dan indeks lama, lalu menetapkan kunci unik baru `uq_absensi_siswa_semester_tanggal` (`id_siswa`, `id_semester`, `tanggal`).
  - View basis data `vw_detail_absensi` diperbarui tanpa ketergantungan tabel `mata_pelajaran`.
  - Menghapus relasi `mataPelajaran()` dan atribut `id_mapel` dari `$fillable` di model [`app/Models/Absensi.php`](file:///e:/tugasprog/edutrack/app/Models/Absensi.php).
  - Menghapus pengisian otomatis mapel pertama pada [`app/Http/Controllers/PresensiController.php`](file:///e:/tugasprog/edutrack/app/Http/Controllers/PresensiController.php) dan validasi `mapel_id` pada [`StorePresensiRequest.php`](file:///e:/tugasprog/edutrack/app/Http/Requests/StorePresensiRequest.php).
  - Mengupdate seluruh seeder data (`DummyDataSeeder`, `SimulasiDataSeeder`) dan 4 berkas unit/feature test.

### C. Antarmuka Early Warning System (EWS) & Perbaikan Visual
- **Status**: Selesai & Terverifikasi.
- **Implementasi**:
  - Perbaikan bug visual tombol "Analisis AI" yang sebelumnya tampak sebagai kotak putih kosong (karena class `bg-indigo-600` belum masuk build CSS Vite).
  - Pemberian styling fallback eksplisit pada tombol bilah filter di [`resources/views/early-warning/index.blade.php`](file:///e:/tugasprog/edutrack/resources/views/early-warning/index.blade.php).
  - Penambahan lebar minimum tabel `min-w-[720px]` agar kolom AI dan Aksi tidak terpotong pada resolusi layar 1024px/1366px.
  - Kompilasi ulang seluruh bundle asset melalui `npm run build`.

### D. Fitur Akademik dan Manajemen Inti yang Sudah Berjalan
1. **Autentikasi & Otorisasi Berbasis Peran**:
   - Multi-role: `Operator`, `Wali Kelas`, `Guru Mapel`.
   - Proteksi sesi, proteksi rute via `RoleMiddleware`, dan redirect dashboard otomatis.
2. **Master Data Management (Operator)**:
   - CRUD Siswa, Guru, Kelas, Mata Pelajaran, Ekstrakurikuler, dan Semester.
3. **Penilaian Kurikulum Merdeka**:
   - Penginputan nilai sumatif TP1–TP8, Nilai Sumatif (NS), dan Sumatif Akhir Semester (SAS).
   - Perhitungan Nilai Akhir (NA) otomatis.
   - Perumusan deskripsi Capaian Kompetensi tertinggi & terendah.
4. **Presensi Harian & Nilai Ekstrakurikuler**:
   - Pencatatan absensi harian kelas (Hadir, Izin, Sakit, Alpha).
   - Pencatatan nilai predikat ekstrakurikuler (Sangat Baik, Baik, Cukup, Kurang) dan catatan deskripsi via AJAX.
5. **Catatan Sikap Wali Kelas & Status Kenaikan**:
   - Catatan sikap spiritual, sosial, catatan wali, serta penetapan status kenaikan kelas (`Naik` / `Tinggal`).
6. **Rapor Siswa Kurikulum Merdeka**:
   - Monitoring capaian rapor per kelas.
   - Halaman detail rapor siswa.
   - Halaman cetak rapor siap cetak lengkap dengan data sekolah, kepala sekolah dinamis, dan daftar seluruh mata pelajaran.
7. **Sistem Peringatan Dini (EWS) Rule-Based**:
   - Analisis berbasis kriteria bobot (nilai di bawah KKM, tren penurunan nilai, akumulasi alpha, catatan perilaku).
   - Eksekusi manual via tombol web atau terjadwal via command console `php artisan ews:run`.
   - Pengelompokan 4 status risiko: Tinggi, Sedang, Kecil, Normal.
   - Pembuatan notifikasi otomatis kepada Wali Kelas dan Operator.
8. **Dukungan Keputusan Berbasis AI (Machine Learning)**:
   - Integrasi HTTP dengan FastAPI microservice di port 8001.
   - K-Means Clustering, Isolation Forest Anomaly Detection, dan OLS Regression.
   - Visualisasi scatter plot klaster PCA 2D dan badge anomali pada tabel EWS.
   - Halaman evaluasi model berbasis persona kontrol (`persona_label.csv`).
9. **Tindak Lanjut Konseling**:
   - Pencatatan rencana bimbingan konseling per siswa berisiko.
   - Penugasan guru pendamping/konselor melalui foreign key `id_guru`.
   - Pelacakan status: `Belum Ditindak`, `Sedang Diproses`, `Selesai`.

---

## 2. Fitur yang Belum Selesai / Sedang Berlangsung (Pending Tasks)

### Masalah 4: Jumlah Anggota Ekstrakurikuler pada Data Master Masih Kosong
- **Lokasi Permasalahan**:
  - Halaman antarmuka [`resources/views/master/ekskul.blade.php`](file:///e:/tugasprog/edutrack/resources/views/master/ekskul.blade.php).
  - Controller method `DataMasterController::ekskul()` di [`app/Http/Controllers/DataMasterController.php`](file:///e:/tugasprog/edutrack/app/Http/Controllers/DataMasterController.php).
- **Kondisi Saat Ini**:
  - Tabel master ekstrakurikuler menampilkan kolom: `No.`, `Nama Ekstrakurikuler`, `Guru Pembina`, `Jadwal Latihan`, `Status`, dan `Aksi`.
  - **Belum ada kolom "Jumlah Anggota"** yang menampilkan total siswa yang terdaftar mengikuti kegiatan ekskul tersebut.
- **Rencana Solusi yang Ditunda untuk Konfirmasi Pengguna**:
  1. Pada `DataMasterController::ekskul()`, tambahkan agregasi hitung siswa unik dari tabel relasi `nilai_ekstrakurikuler`:
     ```php
     withCount(['nilaiEkstrakurikuler as jumlah_anggota' => function($q) use ($activeSemester) {
         $q->where('id_semester', $activeSemester?->id_semester)
           ->distinct('id_siswa');
     }])
     ```
  2. Pada berkas view `master/ekskul.blade.php`, tambahkan header kolom `JUMLAH ANGGOTA` dan sel `{{ $e->jumlah_anggota ?? 0 }} Siswa`.

---

## 3. Catatan Bug yang Pernah Ditemukan & Telah Terselesaikan

| Bug / Masalah | Penyebab Utama | Solusi yang Diterapkan |
|---|---|---|
| Rapor cetak hanya menampilkan 10 dari 12 mapel untuk siswa tertentu | Filter query semester/mapel tidak mencakup mata pelajaran muatan lokal | Diperbaiki pada `resources/views/rapor/cetak.blade.php` sehingga seluruh mata pelajaran terdaftar muncul utuh |
| Hardcoded ID `semester_id = 2` di berbagai modul | Pengembang sebelumnya memakai nilai statis karena belum ada antarmuka semester aktif | Dibuatkan fitur kelola semester dan helper `Semester::aktif()` di semua controller |
| Galat integritas saat input absensi tanpa mapel | Skema mewajibkan `id_mapel NOT NULL` pada tabel `absensi` | Atribut `id_mapel` dihapus permanen dari basis data, model, request, dan controller |
| Kotak putih kosong di bilah filter Early Warning | Class Tailwind `bg-indigo-600` belum masuk asset build CSS | Dilakukan `npm run build` dan penambahan inline style cadangan pada Blade |
| Tindak Lanjut Operator mengalami 403 Forbidden | Redirect setelah submit mengarah ke rute wali kelas (`wali.early-warning`) | Redirect disesuaikan berdasarkan peran pengguna (`operator.early-warning` vs `wali.early-warning`) |

---

## 4. Prioritas Pengembangan Selanjutnya

1. **Prioritas Utama**: Menyelesaikan **Masalah 4** (Menampilkan Jumlah Anggota Ekstrakurikuler yang tersinkronisasi dengan data siswa di semester aktif).
2. **Prioritas Sekunder**: Menjalankan regresi tes penuh (`php artisan test`) untuk memastikan seluruh 80+ pengujian tetap 100% lulus.
