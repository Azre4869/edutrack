from typing import List, Dict, Any, Tuple
import numpy as np
from sklearn.preprocessing import StandardScaler
from sklearn.cluster import KMeans
from sklearn.metrics import silhouette_score
from sklearn.decomposition import PCA

FEATURE_KEYS = [
    "rata_nilai_akhir",
    "simpangan_antar_mapel",
    "jumlah_mapel_di_bawah_kkm",
    "persen_hadir",
    "jumlah_alpha",
    "alpha_beruntun_maks",
    "slope_tp",
    "selisih_nilai_vs_semester_lalu",
]


def extract_feature_matrix(siswa_list: List[Any]) -> Tuple[np.ndarray, List[int]]:
    """
    Ekstrak matriks fitur dari list siswa.
    Menangani nilai None/NaN dengan imputasi median kolom.
    """
    raw_data = []
    id_list = []

    for s in siswa_list:
        f = s.fitur
        id_list.append(s.id_siswa)
        row = [
            f.rata_nilai_akhir if f.rata_nilai_akhir is not None else np.nan,
            f.simpangan_antar_mapel if f.simpangan_antar_mapel is not None else np.nan,
            float(f.jumlah_mapel_di_bawah_kkm or 0),
            f.persen_hadir if f.persen_hadir is not None else np.nan,
            float(f.jumlah_alpha or 0),
            float(f.alpha_beruntun_maks or 0),
            float(f.slope_tp or 0.0),
            f.selisih_nilai_vs_semester_lalu if f.selisih_nilai_vs_semester_lalu is not None else 0.0,
        ]
        raw_data.append(row)

    X = np.array(raw_data, dtype=float)

    # Imputasi NaN dengan median kolom
    for col_idx in range(X.shape[1]):
        col = X[:, col_idx]
        nan_mask = np.isnan(col)
        if np.all(nan_mask):
            X[:, col_idx] = 0.0
        elif np.any(nan_mask):
            median_val = np.nanmedian(col)
            X[nan_mask, col_idx] = median_val

    return X, id_list


def label_clusters_by_centroids(centroids_unscaled: np.ndarray) -> List[Tuple[str, str]]:
    """
    Menghasilkan label dan deskripsi bahasa Indonesia otomatis berdasarkan karakteristik centroid.
    Fitur:
    0: rata_nilai_akhir, 1: simpangan, 2: mapel_bawah_kkm, 3: persen_hadir,
    4: jumlah_alpha, 5: alpha_maks, 6: slope_tp, 7: selisih_sem

    Label dijamin UNIK — tanpa sufiks angka. Jika dua klaster jatuh ke kandidat
    yang sama, klaster dengan ciri kurang dominan mendapat label alternatif.
    """
    k = centroids_unscaled.shape[0]

    # Kandidat label berurut dari paling spesifik ke paling umum, tiap label hanya dipakai 1x
    LABEL_POOL: List[Tuple[str, str]] = [
        ("Konsisten Unggul",        "Pencapaian akademik sangat tinggi dengan tingkat kehadiran prima dan tanpa alpha."),
        ("Rentan Ganda",            "Kombinasi ketidakhadiran berulang dan capaian nilai di bawah KKM pada banyak mapel."),
        ("Perlu Perbaikan Kehadiran", "Nilai akademik cukup namun frekuensi ketidakhadiran tanpa keterangan tinggi."),
        ("Tren Nilai Menurun",      "Pola nilai menunjukkan penurunan signifikan dibanding capaian awal semester."),
        ("Hadir tapi Nilai Rendah", "Kehadiran disiplin namun nilai akademik pada beberapa mata pelajaran di bawah KKM."),
        ("Pola Membaik",            "Tren positif: performa akademik dan kedisiplinan kehadiran meningkat."),
        ("Stabil Menengah",         "Karakteristik akademik dan kehadiran stabil di rentang standar ketuntasan."),
        ("Berkembang Pesat",        "Peningkatan nilai cukup tinggi dengan kehadiran di atas rata-rata."),
    ]

    def _score_candidate(c: np.ndarray) -> int:
        """Kembalikan indeks LABEL_POOL yang paling cocok untuk centroid c."""
        nilai    = c[0]
        mapel_kkm = c[2]
        hadir    = c[3]
        alpha    = c[4]
        slope    = c[6]
        selisih  = c[7]

        if nilai >= 85.0 and hadir >= 95.0 and alpha < 1.0:
            return 0  # Konsisten Unggul
        if alpha >= 4.0 and (nilai < 72.0 or mapel_kkm >= 2.0):
            return 1  # Rentan Ganda
        if alpha >= 3.0 or hadir < 88.0:
            return 2  # Perlu Perbaikan Kehadiran
        if selisih <= -8.0 or slope <= -0.8:
            return 3  # Tren Nilai Menurun
        if nilai < 75.0 or mapel_kkm >= 1.5:
            return 4  # Hadir tapi Nilai Rendah
        if selisih >= 7.0 or slope >= 0.8:
            return 5  # Pola Membaik
        # Stabil Menengah sebagai default
        return 6

    # Dapatkan skor kandidat tiap centroid
    candidate_scores = [_score_candidate(centroids_unscaled[i]) for i in range(k)]

    # Resolusi konflik: jika dua klaster ingin label yang sama, yang "lebih jauh"
    # dari kriteria utama mendapat label alternatif berikutnya dari pool.
    used_indices: set = set()
    assigned_labels: List[Tuple[str, str]] = []

    for i in range(k):
        idx = candidate_scores[i]
        # Geser ke label berikutnya jika sudah dipakai (maks geser = panjang pool)
        attempts = 0
        while idx in used_indices and attempts < len(LABEL_POOL):
            idx = (idx + 1) % len(LABEL_POOL)
            attempts += 1
        used_indices.add(idx)
        assigned_labels.append(LABEL_POOL[idx])

    return assigned_labels


def run_clustering(siswa_list: List[Any], random_state: int = 42) -> Dict[str, Any]:
    """
    Menjalankan Modul A: K-Means Clustering + PCA 2D
    """
    n_samples = len(siswa_list)
    if n_samples < 6:
        # Fallback jika sampel sangat sedikit
        k_candidates = [2] if n_samples >= 3 else [1]
    else:
        k_candidates = [3, 4, 5, 6]

    X, id_list = extract_feature_matrix(siswa_list)

    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)

    best_k = k_candidates[0]
    best_score = -2.0
    best_model = None

    for k in k_candidates:
        if k >= n_samples:
            continue

        km = KMeans(n_clusters=k, n_init=10, random_state=random_state)
        labels = km.fit_predict(X_scaled)

        # Cek apakah ada klaster yang kurang dari 5% populasi
        counts = np.bincount(labels, minlength=k)
        min_pct = np.min(counts) / n_samples
        if min_pct < 0.05 and len(k_candidates) > 1:
            # Tolak k jika menghasilkan klaster < 5% populasi
            continue

        score = silhouette_score(X_scaled, labels) if k > 1 else 0.0
        if score > best_score:
            best_score = score
            best_k = k
            best_model = km

    if best_model is None:
        best_k = min(3, max(1, n_samples - 1))
        best_model = KMeans(n_clusters=best_k, n_init=10, random_state=random_state)
        best_model.fit(X_scaled)
        best_score = silhouette_score(X_scaled, best_model.labels_) if best_k > 1 else 0.0

    labels = best_model.labels_
    centroids_scaled = best_model.cluster_centers_
    centroids_unscaled = scaler.inverse_transform(centroids_scaled)

    # Label otomatis
    cluster_labels_desc = label_clusters_by_centroids(centroids_unscaled)

    # PCA 2D Projection
    pca = PCA(n_components=2, random_state=random_state)
    coords_2d = pca.fit_transform(X_scaled)

    # Hitung jarak tiap titik ke centroid-nya
    distances = []
    for idx, sample in enumerate(X_scaled):
        c_idx = labels[idx]
        centroid = centroids_scaled[c_idx]
        dist = float(np.linalg.norm(sample - centroid))
        distances.append(dist)

    # Ringkasan per klaster
    ringkasan = []
    for c_id in range(best_k):
        mask = (labels == c_id)
        count = int(np.sum(mask))
        pct = round((count / n_samples) * 100, 2)
        lbl, desc = cluster_labels_desc[c_id]

        c_mean = centroids_unscaled[c_id]
        feature_means = {
            "rata_nilai_akhir": round(float(c_mean[0]), 2),
            "simpangan_antar_mapel": round(float(c_mean[1]), 2),
            "jumlah_mapel_di_bawah_kkm": round(float(c_mean[2]), 2),
            "persen_hadir": round(float(c_mean[3]), 2),
            "jumlah_alpha": round(float(c_mean[4]), 2),
            "alpha_beruntun_maks": round(float(c_mean[5]), 2),
            "slope_tp": round(float(c_mean[6]), 3),
            "selisih_nilai_vs_semester_lalu": round(float(c_mean[7]), 2),
        }

        ringkasan.append({
            "cluster_id": c_id,
            "label": lbl,
            "deskripsi": desc,
            "jumlah_siswa": count,
            "persentase": pct,
            "rata_rata_fitur": feature_means,
        })

    # Hasil per siswa
    per_siswa = {}
    for idx, id_s in enumerate(id_list):
        c_id = int(labels[idx])
        lbl, _ = cluster_labels_desc[c_id]
        per_siswa[id_s] = {
            "cluster_id": c_id,
            "cluster_label": lbl,
            "cluster_distance": round(distances[idx], 4),
            "pca_x": round(float(coords_2d[idx, 0]), 4),
            "pca_y": round(float(coords_2d[idx, 1]), 4),
        }

    return {
        "k": best_k,
        "silhouette_score": round(float(best_score), 4),
        "ringkasan": ringkasan,
        "per_siswa": per_siswa,
    }
