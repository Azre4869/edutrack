# Konteks dan Arsitektur Proyek (PROJECT_CONTEXT.md)

Dokumen ini menjelaskan latar belakang, tujuan bisnis, arsitektur perangkat lunak, peran pengguna, dan hubungan antar-komponen dalam sistem **EduTrack**.

---

## 1. Tujuan dan Ruang Lingkup Sistem

**EduTrack** adalah Sistem Informasi Manajemen Akademik dan *Early Warning System* (EWS) berbasis web yang dirancang untuk jenjang Sekolah Menengah Pertama (SMP), dengan studi kasus di SMPN 1 Sampang.

Tujuan utama sistem meliputi:
1. **Pengelolaan Data Akademik Terpadu**: Manajemen data induk siswa, guru, kelas, mata pelajaran, ekstrakurikuler, dan tahun ajaran/semester.
2. **Penilaian Kurikulum Merdeka**: Pencatatan nilai Tujuan Pembelajaran (TP1–TP8), Nilai Sumatif (NS), Sumatif Akhir Semester (SAS), nilai akhir terhitung otomatis, dan perumusan deskripsi capaian kompetensi.
3. **Pencatatan Presensi Harian & Ekstrakurikuler**: Monitoring kehadiran siswa harian (Hadir, Izin, Sakit, Alpha) per kelas binaan serta keterlibatan kegiatan ekstrakurikuler.
4. **Sistem Peringatan Dini (Early Warning System / EWS)**:
   - Mendeteksi secara dini siswa yang berisiko mengalami masalah akademik (nilai di bawah KKM atau tren penurunan nilai drastis) maupun masalah kehadiran (akumulasi alpha tinggi).
   - Mengkategorikan siswa ke dalam 4 tingkat risiko: **Tinggi**, **Sedang**, **Kecil**, dan **Normal**.
   - Menyediakan fitur pembuatan rencana bimbingan dan konseling (*Tindak Lanjut*).
5. **Dukungan Keputusan Berbasis AI (AI-Assisted Decision Support)**:
   - Pengelompokan pola belajar siswa tanpa pengawasan (*unsupervised clustering*).
   - Deteksi anomali perilaku belajar siswa (misal: nilai tinggi tapi sering bolos, atau penurunan mendadak).
   - Proyeksi tren nilai dan kehadiran menggunakan regresi linier.
6. **Pencetakan Rapor Siswa**: Menyusun dan mencetak lembar Rapor Hasil Belajar Kurikulum Merdeka dalam format siap cetak/PDF.

---

## 2. Arsitektur Perangkat Lunak

EduTrack dibangun menggunakan arsitektur modular berlapis:

```
┌─────────────────────────────────────────────────────────────┐
│                      Client Browser                         │
│            (Blade Templates + Tailwind CSS + Chart.js)      │
└──────────────────────────────┬──────────────────────────────┘
                               │ HTTP / HTTPS
┌──────────────────────────────▼──────────────────────────────┐
│                  Laravel 12 Application                     │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ HTTP Layer: Routes, Middleware (Auth, Role), FormReq  │  │
│  └───────────────────────────┬───────────────────────────┘  │
│  ┌───────────────────────────▼───────────────────────────┐  │
│  │ Controllers: Master, Nilai, Presensi, EWS, Tindak Lanjut │
│  └──────────────┬────────────────────────────┬───────────┘  │
│                 │                            │              │
│  ┌──────────────▼────────────┐ ┌─────────────▼───────────┐  │
│  │ EarlyWarningService       │ │ AiInsightService        │  │
│  │ (Rule-based risk scoring) │ │ (Data feature extractor)│  │
│  └──────────────┬────────────┘ └─────────────┬───────────┘  │
│                 │                            │              │
│  ┌──────────────▼────────────────────────────▼───────────┐  │
│  │ Eloquent Models & DB Layer (MySQL `sisfortes`)        │  │
│  └───────────────────────────────────────────┬───────────┘  │
└──────────────────────────────────────────────┼──────────────┘
                                               │ JSON / HTTP
                                ┌──────────────▼──────────────┐
                                │   FastAPI Python Service    │
                                │   (K-Means, IForest, OLS)   │
                                └─────────────────────────────┘
```

### A. Lapisan Backend (Laravel 12, PHP 8.2)
- **Model-View-Controller (MVC)** standar dengan tambahan **Service Layer** khusus untuk modul analitik:
  - `App\Services\EarlyWarning`: Mengatur evaluasi kriteria bobot risiko, agregasi skor risiko siswa, pencatatan log analisis, dan pembuatan notifikasi otomatis.
  - `App\Services\Ai`: Menangani ekstraksi matriks fitur dari data nilai dan kehadiran riil, mengirimkan payload ke microservice AI, dan menyimpan snapshot wawasan (*insight*) ke basis data.
- **Data-Level Access Control (DLAC)**: Keamanan berbasis baris (*row-level access*) ditegakkan di layer controller untuk memastikan Wali Kelas dan Guru Mapel tidak dapat melihat atau mengubah data di luar kewenangannya.

### B. Lapisan AI Microservice (Python FastAPI di `ai-service/`)
- Berjalan terpisah pada port 8001 (`http://127.0.0.1:8001`).
- Menerima payload data numerik anonim (hanya ID numerik siswa, nilai rata-rata, tren nilai, persentase kehadiran, dan jumlah alpha).
- Memanfaatkan:
  - **K-Means Clustering** dengan seleksi klaster optimal berbasis *Silhouette Score*.
  - **Isolation Forest + Robust Z-Score MAD** untuk deteksi anomali.
  - **Ordinary Least Squares (OLS) Linear Regression** untuk proyeksi nilai dan kehadiran beberapa periode ke depan.

### C. Lapisan Antarmuka (Frontend)
- Menggunakan Blade View terstruktur rapi di `resources/views/`.
- Styling menggunakan Tailwind CSS v4 dengan palet identitas sekolah EduTrack (Biru `#2F5CAB`, Jingga `#E04F26`).
- Visualisasi grafik interaktif menggunakan Chart.js untuk dashboard performa, analitik scatter plot klaster PCA 2D, dan grafik tren kehadiran.

---

## 3. Peran Pengguna dan Fitur Utama

| Peran (*Role*) | Deskripsi & Batas Akses | Fitur-Fitur Utama |
|---|---|---|
| **Operator** | Administrator sistem sekolah. Memiliki akses penuh terhadap seluruh data, konfigurasi sistem, dan operasional global. | • Kelola Data Master (Siswa, Guru, Kelas, Mapel, Ekskul, Semester/Tahun Ajaran Aktif)<br>• Input & Monitoring Nilai Semua Mapel<br>• Presensi Kelas & Ekskul Global<br>• Monitoring Rapor Seluruh Siswa<br>• Eksekusi & Konfigurasi Early Warning System (bobot kriteria)<br>• Manajemen Evaluasi Model AI<br>• Tindak Lanjut & Laporan Rekapitulasi |
| **Wali Kelas** | Guru yang bertugas sebagai pembina satu kelas spesifik (`id_wali_kelas` pada tabel `kelas`). Akses dibatasi ketat ke kelas binaan. | • Rekap Data Siswa Kelas Binaan<br>• Presensi Siswa Harian Kelas Binaan<br>• Presensi & Predikat Kegiatan Ekskul<br>• Input Prestasi Siswa Binaan<br>• Catatan Sikap Spiritual/Sosial & Status Kenaikan Kelas<br>• Cetak & Preview Lembar Rapor Siswa Binaan<br>• Monitoring Early Warning & Penanganan Tindak Lanjut Siswa Berisiko di Kelasnya |
| **Guru Mapel** | Guru pengajar mata pelajaran tertentu (`id_guru` pada tabel `mata_pelajaran`). | • Input Nilai Sumatif (TP1–TP8, NS, SAS, Nilai Akhir) untuk mata pelajaran yang diampu<br>• Perumusan Deskripsi Capaian Kompetensi Tertinggi dan Terendah<br>• Melihat Notifikasi Peringatan Terkait Siswa |

---

## 4. Struktur Direktori Proyek

```
edutrack/
├── AGENTS.md                  # Instruksi teknis dan protokol coding agent
├── PROJECT_CONTEXT.md         # Arsitektur dan konteks fungsional sistem
├── CURRENT_STATUS.md          # Status fitur selesai, pending, dan prioritas
├── DATABASE_SCHEMA.md         # Struktur tabel, relasi, view, dan constraint
│
├── ai-service/                # Python FastAPI microservice (Machine Learning)
│   ├── app/                   # Kode algoritma clustering, anomaly, trend, evaluator
│   ├── run.bat / run.sh       # Script inisialisasi microservice port 8001
│   └── requirements.txt       # Dependensi Python
│
├── app/
│   ├── Http/Controllers/     # Controller per modul (DataMaster, Nilai, EWS, dll.)
│   ├── Http/Middleware/      # RoleMiddleware (Operator, Wali Kelas, Guru Mapel)
│   ├── Http/Requests/        # Form Request Validasi
│   ├── Models/               # 21 Model Eloquent
│   └── Services/             # EarlyWarningService & AiInsightService
│
├── database/
│   ├── migrations/           # Riwayat migrasi skema database
│   └── seeders/              # Seeder data master, dummy, simulasi EWS, & semester aktif
│
├── public/                    # Entrypoint publik & asset build terkompilasi
├── resources/
│   ├── css/ & js/            # Source Tailwind CSS & JavaScript
│   └── views/                # Template Blade (auth, dashboard, master, nilai, rapor, ews)
├── routes/
│   ├── web.php               # Seluruh rute aplikasi berbasis peran
│   └── console.php           # Artisan command & penjadwalan console
└── tests/
    └── Feature/              # Pengujian integrasi & fungsionalitas (80+ tes)
```
