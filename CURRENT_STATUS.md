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

### E. Rekap Terpadu Riwayat Konseling & Penanganan Tindak Lanjut (Baru Diselesaikan)
- **Status**: Selesai & Terverifikasi (Lulus Pengujian — 11 pengujian, 33 assertions; seluruh 87 tes sistem 100% PASS).
- **Implementasi**:
  - **Pemisahan Alur Rekap vs Form Siswa**:
    - Menu sidebar **Tindak Lanjut** (`/operator/tindak-lanjut` atau `/wali/tindak-lanjut`) kini menjadi **Halaman Rekap Terpadu Riwayat Konseling** lintas siswa (menggunakan view [`resources/views/tindak-lanjut/rekap.blade.php`](file:///e:/tugasprog/edutrack/resources/views/tindak-lanjut/rekap.blade.php)).
    - Memperbaiki kejanggalan sistem lama di mana halaman tanpa parameter siswa otomatis mengambil siswa pertama di database (`Siswa::first()` alias Firdausi Anam).
    - Halaman form input konseling baru kini spesifik diakses per siswa melalui URL `/tindak-lanjut/{id_siswa}` (misal saat menekan tombol "Tindak Lanjut Siswa Ini" dari Early Warning atau tombol "Tambah Sesi" dari rekap).
  - **Fitur Halaman Rekap Konseling**:
    - Menampilkan kartu sesi konseling untuk semua siswa lengkap dengan status badge (*Sedang Diproses*, *Selesai*), tanggal/jam sesi, guru pendamping, lokasi, catatan konseling, dan hasil evaluasi.
    - Filter berdasarkan semester aktif/arsip serta kolom pencarian instan nama/NIS siswa.
    - Tombol aksi langsung pada setiap kartu: **"Tandai Selesai"**, **"Hapus"**, dan tautan **"Tambah Sesi"**.
    - Kontrol Akses Data (DLAC): Operator dapat memantau seluruh sesi sekolah, sedangkan Wali Kelas otomatis hanya melihat sesi konseling siswa kelas binaannya.
  - **Aksi Siklus Konseling di Controller**:
    - Method `markCompleted($id)` di [`TindakLanjutController.php`](file:///e:/tugasprog/edutrack/app/Http/Controllers/TindakLanjutController.php): menandai sesi menjadi `'Selesai'`, mengisi `tanggal_evaluasi`, serta menerima catatan evaluasi.
    - Method `destroy($id)`: menghapus draf/sesi dengan pengecekan wewenang DLAC.
  - **Rute Web**:
    - `GET /tindak-lanjut` → Rekap Riwayat Konseling (`tindak-lanjut`).
    - `GET /tindak-lanjut/{id}` → Form input sesi baru per siswa (`tindak-lanjut.siswa`).
    - `PATCH /tindak-lanjut/{id}/selesai` → Tandai selesai (`tindak-lanjut.selesai`).
    - `DELETE /tindak-lanjut/{id}` → Hapus sesi (`tindak-lanjut.destroy`).
  - **Pengujian Otomatis**:
    - 11 pengujian di [`tests/Feature/TindakLanjutTest.php`](file:///e:/tugasprog/edutrack/tests/Feature/TindakLanjutTest.php) lulus (termasuk verifikasi rekap global operator dan isolasi kelas binaan wali).
    - Seluruh 87 feature test proyek berstatus **100% green**.

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
   - Riwayat seluruh sesi tersimpan tampil di kartu "Riwayat Draf & Sesi Konseling".
   - Tombol **Tandai Selesai** (mengisi `hasil_evaluasi`, `skor_efektivitas`, `tanggal_evaluasi`) dan **Hapus** draf tersedia di tiap kartu riwayat dengan proteksi DLAC.
   - *Catatan teknis*: Kolom `tindakan` di tabel `tindak_lanjut` diisi hardcode `'Sesi konseling dijadwalkan'` karena kolom bersifat NOT NULL — tidak kritis, karena catatan isi sebenarnya disimpan di kolom `catatan_konseling`. Ini adalah peninggalan desain lama yang **sengaja dibiarkan** (tidak memerlukan perbaikan kritis).
   - *Catatan desain*: Tabel `nilai_harian` ada di skema database dan model sudah dibuat, namun **sengaja ditinggalkan** tanpa UI input (keputusan desain produk). Kolom ini hanya diisi oleh seeder simulasi dan dikonsumsi AI — bukan fitur yang perlu diimplementasikan.

### G. Optimasi Performa Basis Data & In-Memory Caching (Baru Diselesaikan)
- **Status**: Selesai & Terverifikasi (Commit `f03475e`).
- **Implementasi**:
  - **Model-Level Caching**:
    - [`app/Models/Semester.php`](file:///e:/tugasprog/edutrack/app/Models/Semester.php): Menambahkan cache in-memory/file pada `Semester::aktif()` (TTL 10 menit), `Semester::cachedAll()` (TTL 30 menit), dan `Semester::getTahunAjaranList()` (TTL 30 menit). Seluruh cache otomatis ter-invalidate saat event `saved` atau `deleted`.
    - [`app/Models/Kelas.php`](file:///e:/tugasprog/edutrack/app/Models/Kelas.php), [`app/Models/MataPelajaran.php`](file:///e:/tugasprog/edutrack/app/Models/MataPelajaran.php), [`app/Models/Ekstrakurikuler.php`](file:///e:/tugasprog/edutrack/app/Models/Ekstrakurikuler.php): Menambahkan method `cachedAll()` (TTL 30 menit) dengan invalidasi otomatis.
  - **Eliminasi N+1 Query Dashboard**:
    - Pada `DashboardController::getGrafikKehadiran()`, mengubah pola loop 5 hari $\times$ 4 query status (sebelumnya 20 query terpisah) menjadi **1 query agregat** menggunakan `GROUP BY tanggal, status` lalu dikelompokkan pada layer PHP.
    - Pada `DashboardController::wali()`, menggabungkan query `count()` dan `pluck()` menjadi satu kali eksekusi data siswa.
  - **Pemadatan Query Rekapitulasi Laporan**:
    - Pada `LaporanController::index()`, perhitungan siswa per tingkat 7, 8, dan 9 yang sebelumnya memicu 3 query terpisah dipadatkan menjadi **1 query tunggal** berbasis `GROUP BY kelas.tingkat`.
  - **Pengurangan Beban Database Cloud**:
    - Pengaturan default `CACHE_STORE` di `.env.example` disesuaikan menjadi `file` guna menghindari round-trip query ke tabel `cache` di remote database.

### H. Perbaikan Fatal Timeout & Arsitektur Early Warning System (Baru Diselesaikan)
- **Status**: Selesai & Terverifikasi (Commit `b7047f5`).
- **Implementasi**:
  - Menghapus pemanggilan tak bersyarat `runAnalysis()` pada setiap HTTP GET di [`EarlyWarningService.php`](file:///e:/tugasprog/edutrack/app/Services/EarlyWarning/EarlyWarningService.php). Analisis penuh hanya dijalankan jika data analisis untuk kelas & semester tersebut belum ada sama sekali, memangkas waktu pemuatan halaman dari >35 detik (yang sebelumnya fatal error) menjadi di bawah 1 detik.
  - Mengimplementasikan in-memory caching untuk pencarian Operator dan Wali Kelas di [`NotificationBuilder.php`](file:///e:/tugasprog/edutrack/app/Services/EarlyWarning/NotificationBuilder.php) guna mengeliminasi N+1 query notifikasi per siswa berisiko.

### I. Perbaikan Ergonomi Antarmuka: Scrollable Sidebar Navigation
- **Status**: Selesai & Terverifikasi.
- **Implementasi**:
  - Pada [`resources/views/layouts/app.blade.php`](file:///e:/tugasprog/edutrack/resources/views/layouts/app.blade.php#L70-L82), elemen navigasi sidebar `<aside>` dirombak dengan struktur Flexbox modern:
    - **Logo Header**: Menggunakan `shrink-0` agar tetap sticky di bagian atas.
    - **Menu Navigasi**: Diberikan container `flex-1 overflow-y-auto px-3 py-4 space-y-1` sehingga seluruh menu dapat di-scroll dengan sangat halus pada zoom 100% atau layar beresolusi laptop rendah tanpa ada menu yang terpotong.
    - **User Profile Footer**: Diberikan `shrink-0` di bagian bawah sidebar agar tetap tampak rapi dan tidak tertutup konten menu.

### J. Modul Evaluasi & Validasi Model AI (Machine Learning Validation)
- **Status**: Selesai, Terintegrasi Penuh, & Terverifikasi di Lingkungan Produksi Railway.
- **Implementasi**:
  - **Arsitektur Pengujian Ground-Truth**:
    - Validasi performa model Machine Learning lokal (K-Means, Isolation Forest, dan Regresi OLS) terhadap dataset persona terkontrol (`persona_label.csv` - 156 siswa).
    - Memetakan NIS unik siswa secara dinamis ke ID siswa aktual di basis data saat runtime untuk mengatasi inkonsistensi auto-increment basis data cloud.
  - **Metrik Terukur**:
    - **Adjusted Rand Index (ARI) & Cluster Purity**: Mengukur keselarasan pengelompokan alami tanpa supervisi (*unsupervised*) K-Means terhadap profil persona perilaku siswa.
    - **Deteksi Anomali Persona P8**: Menguji keandalan deteksi siswa berprestasi yang mengalami penurunan tajam mendadak (*sudden drop*) di pekan-pekan akhir.
    - **Akurasi Prediksi Tren (MAE - Mean Absolute Error)**: Menguji ketepatan regresi linier leave-last-out dibandingkan baseline naive.
  - **Ketahanan & Integrasi Cloud**:
    - Proteksi crash HTTP 500 Scikit-learn (penyeragaman panjang sampel evaluasi satu-loop).
    - Mekanisme fallback pembuatan record `AiRun` mandiri serta penyimpanan metrik evaluasi pada atribut JSON `metrik['eval']` di database.
    - Flash notification informatif pada antarmuka web saat evaluasi berhasil dijalankan atau dilewati.

### K. Pembatasan Hak Akses Operator: Read-Only pada Modul Nilai & Presensi (Baru Diselesaikan)
- **Status**: Selesai & Terverifikasi.
- **Implementasi**:
  - **Prinsip Pembagian Wewenang (Separation of Concerns)**:
    - Operator bertindak murni sebagai pengawas/pemantau data akademik sekolah. Hak mutasi/input nilai dipegang oleh **Guru Mapel** pengampu, dan mutasi/input presensi dipegang oleh **Wali Kelas**.
    - Operator tetap memiliki fleksibilitas penuh untuk melihat (read-only) seluruh data nilai (lintas kelas, mapel, semester) dan seluruh data presensi harian/ekskul tanpa batasan filter kelas binaan.
  - **Proteksi Tingkat Controller & FormRequest**:
    - [`StoreNilaiRequest.php`](file:///e:/tugasprog/edutrack/app/Http/Requests/StoreNilaiRequest.php) & [`StorePresensiRequest.php`](file:///e:/tugasprog/edutrack/app/Http/Requests/StorePresensiRequest.php): Menolak request dari Operator pada method `authorize()` (`return false` -> otomatis HTTP 403 Forbidden).
    - [`NilaiController.php`](file:///e:/tugasprog/edutrack/app/Http/Controllers/NilaiController.php): Guard eksplisit pada `storeNilai()` dan `storeCapaian()` yang mengembalikan `abort(403)` jika user adalah Operator.
    - [`PresensiController.php`](file:///e:/tugasprog/edutrack/app/Http/Controllers/PresensiController.php): Guard eksplisit pada `storePresensiKelas()`, `storePresensiEkskul()`, dan `storeDeskripsiAjax()` yang mengembalikan `abort(403)` jika user adalah Operator.
  - **Penyesuaian Antarmuka Pengguna (Blade Views)**:
    - [`resources/views/nilai/input-nilai.blade.php`](file:///e:/tugasprog/edutrack/resources/views/nilai/input-nilai.blade.php): Tombol "Simpan Semua" diganti dengan badge `Mode Lihat Saja (Read-Only)`. Seluruh input TP1–TP8 dan SAS berstatus `readonly disabled` dengan styling visual `bg-gray-50/70 cursor-not-allowed`. Modal capaian kompetensi dikunci read-only dan tombol simpan dihilangkan.
    - [`resources/views/presensi/kelas.blade.php`](file:///e:/tugasprog/edutrack/resources/views/presensi/kelas.blade.php): Tombol "Simpan Semua" diganti badge `Mode Lihat Saja (Read-Only)`. Radio button kehadiran (Hadir/Sakit/Izin/Alpha) serta input keterangan berstatus `disabled` / `readonly`.
    - [`resources/views/presensi/ekskul.blade.php`](file:///e:/tugasprog/edutrack/resources/views/presensi/ekskul.blade.php): Tombol "Simpan Semua" diganti badge `Mode Lihat Saja (Read-Only)`. Pilihan ekskul, predikat, dan modal capaian/deskripsi dikunci read-only bagi Operator.
  - **Pembaruan Rangkaian Pengujian (Feature Tests)**:
    - [`tests/Feature/StoreNilaiValidationTest.php`](file:///e:/tugasprog/edutrack/tests/Feature/StoreNilaiValidationTest.php) & [`tests/Feature/StorePresensiValidationTest.php`](file:///e:/tugasprog/edutrack/tests/Feature/StorePresensiValidationTest.php): Menambahkan uji 403 Forbidden saat Operator mencoba menyimpan nilai atau presensi, serta memastikan Guru Mapel dan Wali Kelas tetap dapat menginput data secara normal.

### L. Implementasi Tampilan Responsif Mobile & Smartphone (Mobile-First Ergonomics)
- **Status**: Selesai, Terintegrasi Penuh, & Terverifikasi (`npm run build` sukses).
- **Implementasi**:
  - **Navigasi Off-Canvas Drawer Modern**:
    - Layout induk [`layouts/app.blade.php`](file:///e:/tugasprog/edutrack/resources/views/layouts/app.blade.php): Sidebar desktop (`lg:w-64`) diubah menjadi off-canvas drawer pada layar ponsel/tablet (`-translate-x-full lg:translate-x-0`) lengkap dengan animasi slide-in halus (`duration-300`).
    - Ditambahkan **Top Bar Mobile** sticky di atas layar ponsel dengan tombol hamburger (`fa-bars`), logo EduTrack mini, role badge, dan tombol logout cepat.
    - Ditambahkan **Backdrop Gelap** (`#sidebarBackdrop`) dengan efek blur transparan yang otomatis menutup drawer saat disentuh di luar atau saat menekan tombol `Escape`.
    - Ditambahkan tombol silang (`fa-xmark`) di bagian header sidebar untuk menutup menu secara manual di ponsel.
  - **Adaptasi Kisi Metrik & Kartu Statistik**:
    - Seluruh kisi kartu metrik (Dashboard Operator, Wali, Guru, Notifikasi, Laporan, Early Warning) diubah dari layout desktop kaku menjadi adaptif 1 kolom di HP, 2 kolom di tablet, dan 4 kolom di desktop (`grid-cols-1 sm:grid-cols-2 lg:grid-cols-4`).
  - **Bilah Filter & Pencarian Fleksibel**:
    - Seluruh form filter (Nilai, Presensi Kelas, Presensi Ekskul, Data Master Siswa/Guru/Kelas/Mapel/Ekskul/Semester, Rapor, dan Catatan Sikap) kini tersusun rapi menggunakan Flexbox/Grid adaptif (`grid grid-cols-2 sm:grid-cols-3 lg:flex` atau `flex flex-col sm:flex-row`).
    - Tombol aksi (Export Excel, Tambah Data, Simpan Semua, Reset) kini mengambil lebar penuh atau sejajar di layar kecil sehingga nyaman disentuh jari (*touch-friendly*).
  - **Penanganan Tabel Lebar (Table Horizontal Scroll & Indikator)**:
    - Kontainer seluruh tabel data dibungkus dengan wrapper `overflow-x-auto custom-scrollbar` yang aman dari *layout break*.
    - Ditambahkan petunjuk visual geser (*scroll hint*) di atas tabel khusus pada layar mobile: *"Geser tabel untuk melihat kolom lainnya →"*.
  - **Optimalisasi Modal Dialog & Toast**:
    - Modal Capaian Kompetensi dan Deskripsi Ekskul diberikan batasan tinggi `max-h-[90vh] overflow-y-auto` dengan padding ramah layar kecil (`p-4 sm:p-6`) agar tidak terpotong saat keyboard virtual aktif.
    - Notifikasi toast disesuaikan posisinya (`bottom-4 right-4 left-4 sm:left-auto sm:right-5`) agar pas di layar ponsel.

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
| Draf konseling tersimpan tidak muncul di antarmuka web | Setelah submit, form me-reset ke draf baru dan belum ada komponen UI untuk menampilkan data riwayat dari tabel `tindak_lanjut` | Ditambahkan query riwayat di `TindakLanjutController`, kartu Riwayat Sesi Konseling di `index.blade.php`, serta tombol Tandai Selesai dan Hapus dengan proteksi DLAC |
| Menu Tindak Lanjut otomatis membuka siswa "Firdausi Anam" | Controller lama melakukan fallback `$siswa = Siswa::first()` jika parameter ID siswa kosong | Halaman utama `/tindak-lanjut` diubah menjadi Rekap Terpadu seluruh riwayat konseling sekolah, form konseling baru dipindah ke `/tindak-lanjut/{id_siswa}` |
| Fatal error timeout 30 detik pada GET `/wali/early-warning` | Method `getAnalysisViewData()` memanggil `runAnalysis()` penuh secara sinkron di setiap HTTP request ke remote cloud database | `runAnalysis()` dibungkus pemeriksaan status `!$hasAnalysis`; jika data analisis sudah ada, langsung baca hasil tersimpan |
| Tampilan CSS rusak (Mixed Content) pada deploy Railway HTTPS | Reverse proxy Railway mengakhiri SSL tanpa header proxy dipercaya oleh Laravel, sehingga asset dipanggil via HTTP | Ditambahkan `$middleware->trustProxies(at: '*')` di `bootstrap/app.php` dan `URL::forceScheme('https')` di `AppServiceProvider.php` |
| Sidebar terpotong dan tidak bisa di-scroll pada zoom 100% | Elemen `<aside>` berposisi `fixed` tanpa container berkemampuan `overflow-y-auto` | Struktur sidebar diubah menjadi 3 zona: Logo (shrink-0), Menu (flex-1 overflow-y-auto), Profil (shrink-0) |
| Gagal menghubungi service AI (cURL error 7: 127.0.0.1:8001) | Service Python FastAPI belum dijalankan di Railway, sementara Laravel mencari AI di localhost container | Microservice `ai-service` dideploy sebagai service terpisah di Railway dan dihubungkan via variabel `AI_SERVICE_URL` |
| Evaluasi Model AI "Hasil Belum Tersedia" & angka 0,000 | Path `persona_label.csv` tidak ditemukan di container Python terpisah & mismatch ID auto-increment basis data | Laravel mengirimkan mapping persona via payload HTTP berbasis NIS unik siswa, controller melakukan flattening data JSON, dan menyimpan evaluasi mandiri di `AiRun` |
| Service AI merespons HTTP 500 saat evaluasi model | Inkonsistensi panjang array sampel evaluasi anomali pada Scikit-learn (`ValueError: inconsistent numbers of samples`) | Sinkronisasi pembentukan array `matched_true`, `matched_pred`, dan `matched_is_anomaly` dalam satu loop yang sama di `evaluator.py` |
| Operator memiliki wewenang input & ubah nilai serta presensi | Belum ada pemisahan wewenang mutasi antara Operator dengan Guru Mapel dan Wali Kelas pada controller dan form request | Modul Nilai dan Presensi diubah menjadi mode Read-Only untuk Operator dengan proteksi 403 Forbidden di controller/request dan penguncian input di antarmuka Blade |

---

## 4. Keputusan Desain yang Disengaja (Intentional Design Decisions)

| Komponen | Keputusan | Alasan |
|---|---|---|
| Kolom `tindak_lanjut.tindakan` | Diisi hardcode `'Sesi konseling dijadwalkan'` | Kolom NOT NULL, pengembang lama tidak membuat field input; catatan sebenarnya ada di `catatan_konseling` yang sudah dipakai |
| Tabel `nilai_harian` | Tidak ada UI input, hanya diisi seeder simulasi | Fitur ditinggalkan secara sadar; data nilai formatif tidak diinput secara terpisah di sistem ini |

---

## 5. Prioritas Pengembangan Selanjutnya

1. **Prioritas Utama**: Memastikan seluruh sistem stabil dan menunggu arahan/permintaan fitur baru dari pengguna.
2. **Prioritas Sekunder**: Menjalankan regresi tes berkala sebelum perilisan atau penyerahan sistem.
