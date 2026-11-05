from typing import List, Dict, Any, Optional
import numpy as np


def analyze_trends(
    siswa_input: Any,
    kkm: float = 75.0,
    ambang_penurunan: float = 10.0
) -> Dict[str, Any]:
    """
    Modul C: Tren & Prediksi Linier
    Menghitung kemiringan (slope), prediksi nilai berikutnya, prediksi kehadiran, dan flag berpotensi_turun.
    """
    deret = siswa_input.deret
    f = siswa_input.fitur

    # 1. Deret Nilai (Prioritas: deret.tp lalu deret.harian)
    nilai_points = []
    if deret and deret.tp and len(deret.tp) >= 3:
        nilai_points = [float(v) for v in deret.tp]
    elif deret and deret.harian and len(deret.harian) >= 3:
        nilai_points = [float(p.v) for p in deret.harian]
    elif f.rata_nilai_akhir is not None:
        # Fallback nilai tunggal
        nilai_points = [float(f.rata_nilai_akhir)]

    # 2. Deret Kehadiran Mingguan
    hadir_points = []
    if deret and deret.hadir_mingguan and len(deret.hadir_mingguan) >= 3:
        hadir_points = [float(v) for v in deret.hadir_mingguan]
    elif f.persen_hadir is not None:
        hadir_points = [float(f.persen_hadir)]

    data_cukup = bool(len(nilai_points) >= 3 and len(hadir_points) >= 3)

    # 3. Regresi Linear Nilai
    if len(nilai_points) >= 3:
        x_val = np.arange(len(nilai_points))
        y_val = np.array(nilai_points, dtype=float)
        slope_val, intercept_val = np.polyfit(x_val, y_val, 1)

        # Prediksi nilai langkah berikutnya (x = len(nilai_points))
        next_x = len(nilai_points)
        raw_pred_nilai = float(slope_val * next_x + intercept_val)
        prediksi_nilai = max(0.0, min(100.0, raw_pred_nilai))
    else:
        # Jika data kurang dari 3 titik, jangan paksakan prediksi regresi
        slope_val = float(f.slope_tp or 0.0)
        prediksi_nilai = float(nilai_points[-1]) if nilai_points else (f.rata_nilai_akhir or 75.0)

    # 4. Regresi Linear Kehadiran
    if len(hadir_points) >= 3:
        x_hadir = np.arange(len(hadir_points))
        y_hadir = np.array(hadir_points, dtype=float)
        slope_hadir, intercept_hadir = np.polyfit(x_hadir, y_hadir, 1)

        next_x_hadir = len(hadir_points)
        raw_pred_hadir = float(slope_hadir * next_x_hadir + intercept_hadir)
        prediksi_kehadiran = max(0.0, min(100.0, raw_pred_hadir))
    else:
        slope_hadir = float(f.slope_kehadiran_mingguan or 0.0)
        prediksi_kehadiran = float(hadir_points[-1]) if hadir_points else (f.persen_hadir or 100.0)

    # 5. Penentuan Flag berpotensi_turun
    current_nilai = float(nilai_points[-1]) if nilai_points else (f.rata_nilai_akhir or 75.0)
    current_hadir = float(hadir_points[-1]) if hadir_points else (f.persen_hadir or 100.0)

    berpotensi_turun = False

    # Kondisi 1: Nilai saat ini tuntas (>= KKM), namun proyeksi berikutnya anjlok di bawah KKM (< KKM)
    if current_nilai >= kkm and prediksi_nilai < kkm:
        berpotensi_turun = True

    # Kondisi 2: Kemiringan negatif signifikan (< -1.5) dengan proyeksi penurunan melampaui ambang batas
    persen_penurunan_proyeksi = ((current_nilai - prediksi_nilai) / current_nilai * 100.0) if current_nilai > 0 else 0.0
    if slope_val <= -1.5 and persen_penurunan_proyeksi >= ambang_penurunan:
        berpotensi_turun = True

    # Kondisi 3: Kehadiran saat ini aman (>= 85%), namun proyeksi minggu depan merosot di bawah batas minimal (< 85%)
    if current_hadir >= 85.0 and prediksi_kehadiran < 85.0:
        berpotensi_turun = True

    # Kondisi 4: Slope kehadiran mingguan terjun bebas (< -2.0)
    if slope_hadir <= -2.0:
        berpotensi_turun = True

    return {
        "slope_nilai": round(float(slope_val), 3),
        "prediksi_nilai": round(float(prediksi_nilai), 2),
        "slope_kehadiran": round(float(slope_hadir), 3),
        "prediksi_kehadiran": round(float(prediksi_kehadiran), 2),
        "berpotensi_turun": berpotensi_turun,
        "data_cukup": data_cukup,
    }
