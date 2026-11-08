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

### D. Sinkronisasi Jumlah Anggota Ekstrakurikuler (Masalah 4 - Baru Diselesaikan)
- **Status**: Selesai & Terverifikasi.
- **Implementasi**:
  - Pada method `DataMasterController::ekskul()` di [`app/Http/Controllers/DataMasterController.php`](file:///e:/tugasprog/edutrack/app/Http/Controllers/DataMasterController.php), ditambahkan agregasi `withCount` dinamis berbasis semester aktif (`Semester::aktif()`):
    ```php
    ->withCount([
        'nilaiEkstrakurikuler as jumlah_anggota' => function ($q) use ($activeSemester) {
            if ($activeSemester) {
                $q->where('id_semester', $activeSemester->id_semester);
            }
            $q->select(DB::raw('COUNT(DISTINCT id_siswa)'));
        }
    ])
    ```
  - Pada [`resources/views/master/ekskul.blade.php`](file:///e:/tugasprog/edutrack/resources/views/master/ekskul.blade.php):
    - Ditambahkan kolom header `Jumlah Anggota` pada tabel utama.
    - Ditambahkan badge jumlah siswa (`{{ $e->jumlah_anggota ?? 0 }} Siswa`) lengkap dengan ikon grup.
    - Ditambahkan info jumlah anggota pada modal pop-up detail kegiatan ekskul (`modalDetailEkskul`).
    - Kolom baru otomatis terbawa saat pengguna menekan tombol **Export Excel**.
  - Ditambahkan automated feature test: [`tests/Feature/EkskulManagementTest.php`](file:///e:/tugasprog/edutrack/tests/Feature/EkskulManagementTest.php) (2 pengujian: verifikasi akurasi jumlah siswa ekskul dan proteksi akses non-operator).

### E. Riwayat Draf & Sesi Konseling pada Tindak Lanjut (Baru Diselesaikan)
- **Status**: Selesai & Terverifikasi (Lulus Pengujian).
- **Implementasi**:
  - Pada method `TindakLanjutController::index()` di [`app/Http/Controllers/TindakLanjutController.php`](file:///e:/tugasprog/edutrack/app/Http/Controllers/TindakLanjutController.php), ditambahkan query riwayat konseling siswa (`$riwayatTindakLanjut`) dengan eager loading relasi `guru`, `user`, dan `analisisSiswa`, diurutkan secara kronologis terbalik.
  - Pada [`resources/views/tindak-lanjut/index.blade.php`](file:///e:/tugasprog/edutrack/resources/views/tindak-lanjut/index.blade.php):
    - Bilah aksi formulir diperbarui dengan indikator dinamis: menginformasikan jika ada sesi/draf tersimpan di database dan form di atas siap digunakan untuk sesi lanjutan.
    - Ditambahkan kartu visual baru **"Riwayat Draf & Sesi Konseling"** di bawah form yang menampilkan daftar sesi: nomor sesi, tanggal & jam, badge status, guru konselor, lokasi, catatan konseling (misal draf teks yang tersimpan), dan rencana dukungan terpilih.
  - Kompilasi ulang aset frontend via Vite (`npm run build`).
  - Verifikasi otomatis: seluruh 6 pengujian di [`tests/Feature/TindakLanjutTest.php`](file:///e:/tugasprog/edutrack/tests/Feature/TindakLanjutTest.php) berstatus PASS.

### F. Fitur Akademik dan Manajemen Inti yang Sudah Berjalan
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

*Saat ini seluruh 4 masalah utama dari roadmap perbaikan telah diselesaikan:*
1. ✅ **Masalah 1**: Fitur Kelola Semester Baru & Penanda Aktif (Operator).
2. ✅ **Masalah 2**: Pembersihan Atribut `id_mapel` pada Entitas Absensi.
3. ✅ **Masalah 3**: Bug Kotak Putih Kosong & Kerapian Tabel Early Warning.
4. ✅ **Masalah 4**: Sinkronisasi Jumlah Anggota Ekstrakurikuler dengan Data Siswa.

*Catatan Non-Kodingan*: Terdapat beberapa revisi redaksional pada naskah buku manual dan gambar ERD (tercatat di analisis temuan dokumentasi).

---

## 3. Catatan Bug yang Pernah Ditemukan & Telah Terselesaikan

| Bug / Masalah | Penyebab Utama | Solusi yang Diterapkan |
|---|---|---|
| Rapor cetak hanya menampilkan 10 dari 12 mapel untuk siswa tertentu | Filter query semester/mapel tidak mencakup mata pelajaran muatan lokal | Diperbaiki pada `resources/views/rapor/cetak.blade.php` sehingga seluruh mata pelajaran terdaftar muncul utuh |
| Hardcoded ID `semester_id = 2` di berbagai modul | Pengembang sebelumnya memakai nilai statis karena belum ada antarmuka semester aktif | Dibuatkan fitur kelola semester dan helper `Semester::aktif()` di semua controller |
| Galat integritas saat input absensi tanpa mapel | Skema mewajibkan `id_mapel NOT NULL` pada tabel `absensi` | Atribut `id_mapel` dihapus permanen dari basis data, model, request, dan controller |
| Kotak putih kosong di bilah filter Early Warning | Class Tailwind `bg-indigo-600` belum masuk asset build CSS | Dilakukan `npm run build` dan penambahan inline style cadangan pada Blade |
| Jumlah anggota ekskul tidak tampil di Data Master | Query ekskul belum menyertakan agregasi counter ke relasi `nilai_ekstrakurikuler` | Ditambahkan query `withCount` dinamis berbasis semester aktif dan kolom tabel baru |
| Tindak Lanjut Operator mengalami 403 Forbidden | Redirect setelah submit mengarah ke rute wali kelas (`wali.early-warning`) | Redirect disesuaikan berdasarkan peran pengguna (`operator.early-warning` vs `wali.early-warning`) |
| Draf konseling tersimpan tidak muncul di antarmuka web | Setelah submit, form me-reset ke draf baru dan belum ada komponen UI untuk menampilkan data riwayat dari tabel `tindak_lanjut` | Ditambahkan query riwayat di `TindakLanjutController` dan kartu Riwayat Sesi Konseling di `index.blade.php` |

---

## 4. Prioritas Pengembangan Selanjutnya

1. **Prioritas Utama**: Memastikan seluruh sistem stabil dan menunggu arahan/permintaan fitur baru dari pengguna.
2. **Prioritas Sekunder**: Menjalankan regresi tes berkala sebelum perilisan atau penyerahan sistem.
