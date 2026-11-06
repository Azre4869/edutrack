# Panduan untuk AI Coding Agent & Pengembang (AGENTS.md)

Dokumen ini berisi pedoman teknis, arsitektur, dan batasan penting bagi setiap AI coding agent (atau pengembang manusia) yang melanjutkan pemeliharaan dan pengembangan sistem **EduTrack**.

---

## 1. Tumpukan Teknologi (Technology Stack)

- **Bahasa Pemrograman**: PHP 8.2+ (ZTS Visual C++ 2019 x64) & Python 3.13+ (khusus modul AI).
- **Kerangka Kerja Web**: Laravel 12.x (saat ini v12.69.3).
- **Basis Data**: MySQL / MariaDB (melalui XAMPP di `E:\xamp`, database aktif: `sisfortes`, database pengujian: `edutrack_test`).
- **Antarmuka Pengguna (Frontend)**:
  - Blade Templating Engine.
  - Tailwind CSS v4 (`@tailwindcss/vite` ^4.0.0 & `tailwindcss` ^4.0.0).
  - Bundler: Vite v7.x.
  - Ikon: Font Awesome 6.5.1 (CDN).
  - Grafik: Chart.js (CDN).
  - Ekspor Spreadsheet: SheetJS XLSX v0.18.5 (CDN).
- **Layanan AI / Machine Learning**:
  - Python FastAPI microservice di direktori `ai-service/` (port 8001).
  - Library: Scikit-learn, NumPy, Pandas, Pydantic, Uvicorn.
  - Model: K-Means Clustering, Isolation Forest Anomaly Detection, OLS Linear Regression.

---

## 2. Aturan & Batasan Penting (Strict Restrictions)

1. **JANGAN Melakukan Git Commit Tanpa Izin Eksplisit**:
   - Pengguna sangat berhati-hati terhadap riwayat commit git. Jangan pernah menjalankan `git commit` kecuali diminta secara tegas oleh pengguna.
2. **JANGAN Menghardcode ID (No Hardcoded IDs)**:
   - Dilarang keras menuliskan nilai ID statis (seperti `semester_id = 2`, `id_mapel = 1`, `id_guru = 1`, `id_kelas = 1`).
   - Gunakan selalu helper dinamis:
     - Semester aktif: `Semester::aktif()` (berdasarkan kolom `is_active = 1` atau fallback ke semester terbaru).
     - Kelas binaan wali: `$user->requireKelasBinaan()`.
     - Guru terkait user: `$user->guru`.
3. **Absensi Bersifat Harian Per Siswa, BUKAN Per Mapel**:
   - Kolom `id_mapel` pada tabel `absensi` telah **dihapus secara permanen**.
   - Absensi dicatat harian oleh Wali Kelas atau Operator (`id_siswa`, `id_semester`, `tanggal`, `status`). Dilarang menambahkan kembali ketergantungan mapel pada absensi.
4. **Logika Bisnis di Layer Aplikasi, BUKAN di Stored Procedure**:
   - Seluruh stored procedure database lama telah dihapus melalui migration `2026_11_03_000000_drop_stored_procedures.php`.
   - Perhitungan nilai, skor risiko, dan status kenaikan kelas sepenuhnya diproses melalui Model, Service, dan Controller Laravel.
5. **Kontrol Akses Tingkat Data (Data-Level Access Control / DLAC)**:
   - **Operator**: Akses penuh ke seluruh data master, kelas, nilai, dan pengaturan sistem.
   - **Wali Kelas**: Hanya berhak melihat dan memanipulasi siswa di dalam **kelas binaannya sendiri**.
   - **Guru Mapel**: Hanya berhak memasukkan nilai dan capaian pada **mata pelajaran yang diampunya**.
   - Setiap query data pada controller Wali Kelas wajib membatasi ke kelas binaan (`where('id_kelas', $kelasBinaan->id_kelas)`).
6. **Eksekusi Perintah Terminal di Windows**:
   - Perintah `php artisan` dan akses MySQL memerlukan izin lingkungan eksekusi (`BypassSandbox: true` jika perintah melibatkan koneksi basis data atau jaringan).
   - Jangan menyambungkan perintah shell Windows dengan token `&&` jika PowerShell tidak mendukungnya; jalankan satu per satu atau gunakan pemisah `;`.

---

## 3. Alur Kerja Modifikasi, Pengujian, dan Verifikasi

Sebelum dan sesudah mengubah kode, setiap agent wajib mengikuti protokol berikut:

### Langkah 1: Inspeksi Implementasi Riil Terlebih Dahulu
- Jangan berasumsi berdasarkan rencana lama atau perkiraan nama kolom/relasi.
- Periksa berkas model terkait di `app/Models/`, migrasi di `database/migrations/`, atau jalankan query ke database melalui scratch script/tinker.
- Periksa rute yang terdaftar di `routes/web.php`.

### Langkah 2: Lakukan Perubahan Terfokus
- Terapkan perubahan kode secara presisi dan pertahankan komentar/dokumentasi yang sudah ada.
- Jika menambahkan class baru pada Tailwind CSS di berkas Blade, jalankan build frontend:
  ```powershell
  npm run build
  ```
  *Catatan*: Untuk elemen tombol atau badge penting, sertakan inline style cadangan (fallback) untuk mencegah bug visual jika asset belum ter-cache.

### Langkah 3: Verifikasi dengan Automated Tests
EduTrack memiliki rangkaian tes fitur (Feature Tests) yang sangat komprehensif. Pastikan pengujian terkait lulus:
```powershell
# Jalankan tes spesifik fitur yang diubah
php artisan test --filter=NamaFeatureTest

# Jalankan seluruh rangkaian tes
php artisan test
```
Seluruh 80+ pengujian wajib berstatus **PASS (100% green)** sebelum pekerjaan dinyatakan selesai.

### Langkah 4: Jelaskan Perubahan kepada Pengguna
- Sajikan penjelasan dalam **Bahasa Indonesia** yang jelas, terstruktur, dan ramah pengembang.
- Selalu sertakan tautan berkas yang dapat diklik dengan format `[NamaBerkas](file:///path/to/file#L1-L10)`.
- Tunggu konfirmasi pengguna sebelum beralih ke tugas/masalah berikutnya.
