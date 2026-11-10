from typing import List, Dict, Any, Tuple
import numpy as np
from sklearn.ensemble import IsolationForest
from sklearn.preprocessing import RobustScaler

ANOMALY_FEATURE_NAMES = {
    "rata_nilai_akhir": "Rata-rata Nilai Akhir",
    "jumlah_alpha": "Jumlah Ketidakhadiran Alpha",
    "perubahan_nilai_harian_3_minggu": "Penurunan Nilai Harian 3 Minggu Terakhir",
    "perubahan_kehadiran_3_minggu": "Penurunan Kehadiran 3 Minggu Terakhir",
    "jumlah_mapel_di_bawah_kkm": "Mata Pelajaran di Bawah KKM",
    "alpha_beruntun_maks": "Alpha Beruntun Terpanjang",
}


def compute_robust_z_scores(values: np.ndarray) -> np.ndarray:
    """
    Menghitung robust z-score: 0.6745 * (x - median) / MAD
    """
    median = np.median(values)
    mad = np.median(np.abs(values - median))
    if mad > 1e-6:
        return 0.6745 * (values - median) / mad
    std = np.std(values)
    if std > 1e-6:
        return (values - median) / std
    return np.zeros_like(values)


def detect_anomalies(
    siswa_list: List[Any],
    contamination: float = 0.08,
    random_state: int = 42
) -> Dict[int, Dict[str, Any]]:
    """
    Modul B: Deteksi Anomali Gabungan
    (Robust Z-score per kelas & sekolah + Isolation Forest + Aturan Perubahan Mendadak)
    """
    n_samples = len(siswa_list)
    if n_samples == 0:
        return {}

    # Matriks fitur khusus anomali
    raw_rows = []
    siswa_by_id = {}
    kelas_map = {}

    for s in siswa_list:
        f = s.fitur
        siswa_by_id[s.id_siswa] = s
        kelas_map.setdefault(s.kelas_id, []).append(s.id_siswa)

        row = [
            f.rata_nilai_akhir if f.rata_nilai_akhir is not None else 75.0,
            float(f.jumlah_alpha or 0),
            float(f.perubahan_nilai_harian_3_minggu or 0.0),
            float(f.perubahan_kehadiran_3_minggu or 0.0),
            float(f.jumlah_mapel_di_bawah_kkm or 0),
            float(f.alpha_beruntun_maks or 0),
        ]
        raw_rows.append(row)

    X_mat = np.array(raw_rows, dtype=float)

    # 1. Isolation Forest
    # Pastikan contamination valid sesuai ukuran sampel
    safe_contam = min(0.4, max(0.01, float(contamination)))
    if n_samples < 10:
        safe_contam = 0.1

    iso_forest = IsolationForest(
        n_estimators=100,
        contamination=safe_contam,
        random_state=random_state
    )
    iso_forest.fit(X_mat)
    raw_scores = -iso_forest.score_samples(X_mat)
    iso_preds = iso_forest.predict(X_mat)  # -1 for anomaly, 1 for normal

    # Normalisasi anomaly score ke 0.0 - 1.0
    min_s = float(np.min(raw_scores))
    max_s = float(np.max(raw_scores))
    score_range = max_s - min_s if max_s > min_s else 1.0
    norm_scores = (raw_scores - min_s) / score_range

    # 2. Hitung statistik dan rata-rata per kelas & sekolah
    school_z_matrix = np.zeros_like(X_mat)
    for col in range(X_mat.shape[1]):
        school_z_matrix[:, col] = compute_robust_z_scores(X_mat[:, col])

    # Rata-rata per kelas untuk pembanding yang informatif
    class_means = {}
    for c_id, s_ids in kelas_map.items():
        indices = [i for i, s in enumerate(siswa_list) if s.id_siswa in s_ids]
        class_means[c_id] = np.mean(X_mat[indices], axis=0)

    feature_col_names = list(ANOMALY_FEATURE_NAMES.keys())

    results = {}
    for idx, s in enumerate(siswa_list):
        id_siswa = s.id_siswa
        c_id = s.kelas_id
        c_mean = class_means.get(c_id, np.mean(X_mat, axis=0))

        is_iforest_flagged = bool(iso_preds[idx] == -1)
        z_scores = school_z_matrix[idx]
        val_vector = X_mat[idx]

        # 3. Evaluasi alasan konkret dan z-score >= 2.5
        reasons = []
        has_high_z = False

        # Cek perubahan mendadak (sudden drop)
        delta_harian = val_vector[2]  # perubahan_nilai_harian_3_minggu
        delta_hadir = val_vector[3]   # perubahan_kehadiran_3_minggu

        sudden_drop_triggered = False
        if delta_harian <= -15.0:
            sudden_drop_triggered = True
            reasons.append({
                "fitur": "perubahan_nilai_harian_3_minggu",
                "nilai": round(float(delta_harian), 2),
                "rata_kelas": round(float(c_mean[2]), 2),
                "z": round(float(z_scores[2]), 2),
                "teks_penjelasan": f"Nilai harian anjlok drastis sebesar {abs(delta_harian):.1f} poin pada 3 minggu terakhir dibanding performa sebelumnya.",
            })

        if delta_hadir <= -15.0:
            sudden_drop_triggered = True
            reasons.append({
                "fitur": "perubahan_kehadiran_3_minggu",
                "nilai": round(float(delta_hadir), 2),
                "rata_kelas": round(float(c_mean[3]), 2),
                "z": round(float(z_scores[3]), 2),
                "teks_penjelasan": f"Tingkat kehadiran merosot {abs(delta_hadir):.1f}% secara mendadak dalam 3 minggu terakhir.",
            })

        # Cek fitur lainnya yang |z| >= 2.5
        for col_i, f_name in enumerate(feature_col_names):
            z_val = z_scores[col_i]
            cur_val = val_vector[col_i]
            avg_k = c_mean[col_i]

            if abs(z_val) >= 2.2:
                has_high_z = True
                # Hindari duplikasi jika sudah dicatat di sudden drop
                if f_name in ["perubahan_nilai_harian_3_minggu", "perubahan_kehadiran_3_minggu"] and sudden_drop_triggered:
                    continue

                if f_name == "jumlah_alpha" and cur_val >= 3:
                    teks = f"Akumulasi ketidakhadiran tanpa keterangan (Alpha) mencapai {int(cur_val)} hari (rata-rata kelas: {avg_k:.1f} hari)."
                elif f_name == "alpha_beruntun_maks" and cur_val >= 2:
                    teks = f"Terdapat pola alpha beruntun selama {int(cur_val)} hari kerja berturut-turut."
                elif f_name == "jumlah_mapel_di_bawah_kkm" and cur_val >= 2:
                    teks = f"Terdapat {int(cur_val)} mata pelajaran dengan capaian di bawah standar KKM (rata-rata kelas: {avg_k:.1f} mapel)."
                elif f_name == "rata_nilai_akhir" and z_val <= -2.0:
                    teks = f"Rata-rata nilai akhir ({cur_val:.1f}) terpaut sangat rendah dibanding rata-rata kelas ({avg_k:.1f})."
                else:
                    teks = f"Penyimpangan signifikan pada indikator {ANOMALY_FEATURE_NAMES[f_name]} (nilai: {cur_val:.1f}, z-score: {z_val:.2f})."

                reasons.append({
                    "fitur": f_name,
                    "nilai": round(float(cur_val), 2),
                    "rata_kelas": round(float(avg_k), 2),
                    "z": round(float(z_val), 2),
                    "teks_penjelasan": teks,
                })

        # 4. Kriteria final anomali
        # is_anomaly = true jika ada aturan perubahan mendadak (sudden drop / P8) ATAU (IsolationForest menandai DAN ada z-score deviasi)
        is_anomaly = bool(sudden_drop_triggered or (is_iforest_flagged and has_high_z))

        # Jika is_anomaly tapi belum ada reason terlampir, sertakan reason dengan z tertinggi
        if is_anomaly and not reasons:
            max_col = int(np.argmax(np.abs(z_scores)))
            f_n = feature_col_names[max_col]
            reasons.append({
                "fitur": f_n,
                "nilai": round(float(val_vector[max_col]), 2),
                "rata_kelas": round(float(c_mean[max_col]), 2),
                "z": round(float(z_scores[max_col]), 2),
                "teks_penjelasan": f"Pola data secara multidimensi menyimpang signifikan (fitur deviasi tertinggi: {ANOMALY_FEATURE_NAMES[f_n]}).",
            })

        results[id_siswa] = {
            "anomaly_score": round(float(norm_scores[idx]), 4),
            "is_anomaly": is_anomaly,
            "alasan": reasons,
        }

    return results
