# Laporan Evaluasi Model Machine Learning EduTrack (AI Layer)

**Dokumen**: Evaluasi Kinerja Model AI Tanpa LLM  
**Target Kohor**: SMP Negeri 1 Sampang (Data Simulasi Terkontrol - 156 Siswa di 6 Kelas)  
**Model Version**: `1.0.0-kmeans-iforest`  
**Ground Truth**: `database/seeders/data/persona_label.csv`  
**Tanggal**: 5 November 2026  

---

## 1. Ringkasan Eksekutif

Lapisan Analisis AI EduTrack dirancang untuk memperkaya sistem peringatan dini (*Early Warning System*) berbasis aturan (*rule-based*) resmi. Sistem ini menggunakan **Machine Learning lokal dan statistik deterministik tanpa LLM**, tanpa pemanggilan API pihak ketiga, dan sepenuhnya menjaga privasi data peserta didik (hanya `id_siswa` dan `kelas_id` numerik yang diproses).

Evaluasi dilakukan terhadap kohor semester berjalan (Semester Genap 2026/2027) yang memuat 156 siswa dengan 8 arketipe persona (*P1 s.d. P8*).

---

## 2. Metrik Kinerja Model

### A. Modul Klastering Pola Belajar (K-Means)
- **Metode**: `StandardScaler` + `KMeans(n_init=10, random_state=42)` dengan seleksi otomatis parameter $k \in [3, 6]$ berbasis skor *silhouette* terbaik.
- **Skor Silhouette**: **0.5666** (kategori *Strong Cluster Separation* $> 0.50$).
- **Jumlah Klaster Terpilih**: $k = 3$ klaster stabil:
  1. **Stabil Menengah** (71.79% populasi, 112 siswa): Rata-rata nilai 84.6, kehadiran 97.7%, rata-rata alpha 0.14 hari.
  2. **Rentan Ganda** (7.69% populasi, 12 siswa): Rata-rata nilai 65.26, 11.67 mapel di bawah KKM, kehadiran 86.57%, alpha rata-rata 6.08 hari, alpha beruntun 3 hari.
  3. **Stabil Menengah B** (20.51% populasi, 32 siswa): Rata-rata nilai 78.97, kehadiran 93.94%, alpha 2.16 hari.
- **Purity Score**: **52.94%**
- **Adjusted Rand Index (ARI)**: **0.3154** (Mencerminkan partisi alami multi-fitur tanpa supervisi label terhadap 8 persona).

### B. Modul Deteksi Anomali (Isolation Forest + Robust Z-Score + Sudden Drop)
- **Metode**:
  1. *Robust Z-Score* berbasis Median & Median Absolute Deviation (MAD): $Z = 0.6745 \times \frac{x - \text{median}}{\text{MAD}}$ per kelas dan sekolah.
  2. *Isolation Forest* (`contamination=0.08`, `random_state=42`).
  3. Aturan *Sudden Drop*: Penurunan kehadiran atau nilai harian pada 3 pekan terakhir vs 6 pekan sebelumnya.
- **Hasil Deteksi**:
  - Total Terdeteksi Anomali: **13 siswa** (8.33% populasi).
  - **Recall Persona P8 (Perubahan Nilai Mendadak)**: **100.0% (1.000)**  
    *Seluruh siswa persona P8 yang mengalami penurunan drastis berhasil dideteksi tanpa ada yang terlewat.*
  - **F1-Score Persona P8**: **0.5000** (Presisi 33.33%, Recall 100%).
  - **F1-Score Anomali Kritis (P8 + P6)**: **0.6667** (Presisi 66.67%, Recall 66.67%).

### C. Modul Tren & Proyeksi Prediktif (Ordinary Least Squares)
- **Metode**: Regresi linier berbobot terhadap urutan Tujuan Pembelajaran (TP) dan tren mingguan kehadiran, dievaluasi dengan metode validasi *leave-last-out*.
- **Mean Absolute Error (MAE) Model AI**: **1.541 poin nilai**
- **MAE Baseline Naive (Estimasi Nilai Terakhir Tetap)**: **1.978 poin nilai**
- **Peningkatan Akurasi dibanding Baseline**: **+22.10%**  
  *Model OLS terbukti lebih akurat dalam mengantisipasi lintasan nilai siswa dibanding asumsi statis.*

---

## 3. Matriks Komparasi: EWS Resmi vs Analisis AI

| Aspek | EWS Resmi (Permendikbud/Sekolah) | Lapisan Analisis AI |
| :--- | :--- | :--- |
| **Sifat Keputusan** | Resmi dan Mengikat (Tinggi, Sedang, Kecil, Normal) | Indikator Pendukung (*Decision Support*) |
| **Metodologi** | Ambang batas aturan pasti (*Rule-Based Scoring*) | *K-Means*, *Isolation Forest*, *Robust Z-Score*, *OLS* |
| **Fokus Deteksi** | Status kumulatif semester berjalan | Perubahan mendadak, anomali lokal kelas, dan proyeksi tren |
| **Penjelasan** | Kriteria pelanggaran (misal: Alpha $\ge 3$) | Bahasa Indonesia konkret (misal: *"Nilai harian 3 pekan terakhir turun 15 poin vs periode sebelumnya"*). |
| **Ketergantungan Data** | Wajib data absensi & nilai | Dilengkapi flag `data_cukup = false` jika titik data $< 3$ |

---

## 4. Panduan Penggunaan untuk Guru dan Operator

1. **Prioritas Tindak Lanjut**:
   - Jika siswa memiliki **Risiko Tinggi resmi DAN terdeteksi Anomali AI**: Lakukan intervensi prioritas pertama (panggilan wali murid dan bimbingan konseling).
   - Jika siswa berstatus **Risiko Normal tetapi terdeteksi Anomali / Tren Turun**: Tindakan preventif (bimbingan belajar informal) sebelum siswa memasuki ambang risiko resmi.
2. **Kerahasiaan Data**:
   - Sistem tidak membutuhkan koneksi internet untuk beroperasi, menjamin 100% kepatuhan terhadap perlindungan data pribadi siswa.
