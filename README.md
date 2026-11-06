# EduTrack — Sistem Informasi Akademik & Early Warning System (EWS)
SMP Negeri 1 Sampang

EduTrack adalah platform tata kelola akademik terintegrasi yang memfasilitasi pencatatan nilai sumatif, capaian kompetensi, presensi kelas & ekstrakurikuler, evaluasi rapor, hingga deteksi dini siswa berisiko (*Early Warning System*) berbasis kriteria objektif.

---

## 🚀 Fitur Utama

1. **Tata Kelola Data Master (Operator)**
   - Manajemen terstruktur Siswa, Guru, Kelas, Mata Pelajaran, dan Ekstrakurikuler.
   - Proteksi integritas data (*Foreign Key RESTRICT Delete*) untuk mencegah kehilangan data historis secara tidak sengaja.
   - Pembatasan aman paginasi data (`per_page` dibatasi maksimum 100 entri).

2. **Penilaian & Capaian Kompetensi (Guru Mapel)**
   - Input nilai TP1–TP8, SAS, serta kalkulasi otomatis Nilai Akhir (NA).
   - Pengisian deskripsi Capaian Kompetensi yang tercapai maupun yang perlu ditingkatkan secara terpisah per semester dan mapel.
   - Guard otorisasi ketat: Guru hanya dapat mengisi nilai mapel yang sah diampunya.

3. **Presensi, Catatan, & Evaluasi Rapor (Wali Kelas)**
   - Presensi harian kelas dan catatan ekstrakurikuler serta catatan sikap wali kelas.
   - **Data-Level Access Control (DLAC)**: Wali Kelas hanya berhak mengakses dan memanipulasi siswa di kelas binaannya sendiri.
   - **Aturan Kelengkapan Cetak Rapor**: Server menolak proses cetak rapor apabila nilai 12 mata pelajaran belum memenuhi standar kelengkapan (minimal 1 TP terisi + SAS terisi + Nilai Akhir > 0 + minimal 1 Capaian Kompetensi terisi).

4. **Early Warning System (EWS) & Tindak Lanjut**
   - Analisis otomatis berbasis kriteria riil dari tabel `kriteria_analisis` (Kehadiran Alpha berlebih, Nilai di bawah KKM minimal, dan Penurunan nilai signifikan).
   - Klasifikasi tingkat risiko: **Tinggi**, **Sedang**, **Kecil**, dan **Normal**.
   - Perencanaan sesi konseling dan modul pencatatan tindak lanjut yang tersimpan ke database.

5. **Notifikasi & Laporan Eksekutif**
   - Notifikasi riil per akun dengan penanda terbaca (*mark as read*).
   - Laporan rekapitulasi data siswa per tingkat, tren kehadiran, dan daftar 10 siswa dengan nilai terendah berbasis agregasi database riil.

---

## 🛠️ Prasyarat Sistem

- **PHP**: ^8.2 (ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`)
- **Web Server / Database**: MySQL 8.0+ atau MariaDB 10.4+ (misal via XAMPP)
- **Composer**: ^2.x
- **Node.js & npm**: Node.js v18+ / v20+

---

## 📦 Panduan Instalasi

### 1. Kloning Repositori & Masuk Direktori
```bash
git clone <url-repositori-anda>
cd edutrack
```

### 2. Pasang Dependensi Backend (Composer)
```bash
composer install
```

### 3. Konfigurasi Environment (`.env`)
Salin file konfigurasi environment:
```bash
cp .env.example .env
```
Generate application key:
```bash
php artisan key:generate
```

Sesuaikan konfigurasi koneksi database MySQL pada file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sisfortes
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Migrasi & Seeder Database
Jalankan migrasi skema tabel beserta master data dan data demo:
```bash
php artisan migrate --seed
```

> **Catatan Seeder Bawaan**:
> - `MasterDataSeeder`: Mengisi data pokok sekolah (semester, kriteria analisis EWS, mapel, guru, kelas, dan akun sistem).
> - `DummyDataSeeder`: Mengisi siswa awal, presensi harian dasar, nilai sumatif, capaian kompetensi, riwayat ekskul, serta data demo lengkap 12 mapel untuk siswa **Rani Arisanti Najma (id_siswa = 3)** yang siap dicetak rapornya.

### 5. Seeder Data Simulasi Skala Penuh (Machine Learning & Analytics Dataset)
Untuk melatih dan menguji algoritma analitik prediktif / *Machine Learning* dan *Early Warning System (EWS)* secara komprehensif, jalankan seeder simulasi:
```bash
php artisan db:seed --class=SimulasiDataSeeder
```

> ⚠️ **PENTING: CATATAN DATA SIMULASI**
> - Data yang dihasilkan oleh `SimulasiDataSeeder` adalah **DATA SIMULASI FIKTIF** terkontrol dan realistis untuk keperluan riset, pengujian beban, dan pelatihan model data science/ML.
> - **Skala & Cakupan**: 156 siswa (26 siswa per kelas di 6 kelas), 23.400+ baris absensi harian (150 hari kerja: 90 hari Sem 1 & 60 hari Sem 2), 3.744 baris nilai 12 mapel, 8.700+ nilai harian bertanda `[SIM]`, 312 catatan wali kelas, 330+ nilai ekskul, dan capaian kompetensi.
> - **Distribusi 8 Persona Siswa**: Terbagi secara *stratified* (P1: Unggul stabil ~20%, P2: Menengah stabil ~33%, P3: Hadir tapi nilai rendah ~8%, P4: Sering alpha ~10%, P5: Nilai menurun ~10%, P6: Berisiko ganda ~8%, P7: Membaik ~7%, P8: Perubahan mendadak ~4%).
> - **Evaluasi Model ML**: Label persona tersimpan terpisah pada file `database/seeders/data/persona_label.csv` dan **tidak** mencemari skema kolom basis data.
> - **Idempoten & Deterministik**: Menggunakan seed acak terkunci (`2026`) dan dapat dijalankan berulang kali tanpa membuat duplikasi baris.
> - **Guard Produksi**: Menolak dieksekusi di *environment* `production` kecuali diberikan variabel `SIM_ALLOW=true`.

### 6. Kompilasi Aset Frontend (Vite)
Aplikasi menggunakan Tailwind CSS terintegrasi Vite (tanpa ketergantungan CDN runtime):
```bash
npm install
npm run build
```
*(Untuk mode pengembangan langsung aktif: jalankan `npm run dev`)*

### 6. Menjalankan Server Lokal
```bash
php artisan serve
```
Akses aplikasi melalui browser di: `http://127.0.0.1:8000`

---

## 👥 Akun Demo & Peran Pengguna

Semua akun demo di bawah ini menggunakan kata sandi standar: **`password`**

| Username | Peran (Role) | Keterangan / Hak Akses |
| :--- | :--- | :--- |
| `operator` | **Operator** | Administrator sistem. Memiliki akses penuh ke Data Master, Monitoring Rapor semua kelas, Laporan Eksekutif, dan EWS sekolah. |
| `walikelas` | **Wali Kelas** | Wali Kelas VII A. Hanya dapat mengelola siswa kelas VII A, input catatan, presensi ekskul, dan cetak rapor siswa kelasnya. |
| `gurumapel` | **Guru Mapel** | Guru Pengampu Bahasa Inggris (Siti Aminah, M.Pd.). Hanya berwenang menginput nilai sumatif dan capaian kompetensi untuk mata pelajarannya. |

---

## 🧪 Pengujian Otomatis (Automated Tests)

EduTrack dilengkapi rangkaian unit & feature test komprehensif untuk memastikan keamanan otorisasi, integritas data, aturan bisnis rapor, dan keakuratan kalkulasi riil.

Untuk menjalankan seluruh test suite:
```bash
php artisan test
```

Rincian cakupan pengujian:
- `AuthorizationTest`: Validasi strict separation peran, blokir manipulasi lintas kelas oleh wali kelas, proteksi guru tanpa mapel, dan pencegahan loop redirect peran tak dikenal.
- `DataLossGuardsTest`: Validasi penolakan penghapusan kelas berpenghuni (*RESTRICT FK*) dan pembersihan nilai yatim (*ghost grades*).
- `RaporRulesTest`: Validasi penolakan cetak rapor yang belum lengkap oleh server dan pembersihan kontrol antarmuka palsu.
- `DashboardRealDataTest`: Validasi keakuratan metrik agregasi seluruh sekolah dan isolasi kelas binaan wali kelas.
- `NotifikasiRealDataTest`: Validasi isolasi data notifikasi per akun dan fungsi *mark as read*.
- `LaporanRealDataTest`: Validasi agregasi riil jumlah siswa, guru, rata-rata nilai, dan 10 siswa nilai terendah.
- `EarlyWarningRealDataTest`: Validasi deteksi otomatis kriteria EWS riil dan penyimpanan rencana tindak lanjut ke database.
- `SimulasiDataSeederTest`: Validasi integritas data simulasi, rentang nilai 0–100, rumus NS & Nilai Akhir, toleransi distribusi persona (±3%), kapasitas kelas (24–28 siswa), dan sebaran Early Warning Semester 2.

---

## 📄 Lisensi
Proyek EduTrack dikembangkan untuk keperluan akademik SMP Negeri 1 Sampang.
Hak cipta dilindungi undang-undang.
