from typing import List, Dict, Any, Optional
import os
import numpy as np
import pandas as pd
from sklearn.metrics import (
    adjusted_rand_score,
    precision_score,
    recall_score,
    f1_score,
    accuracy_score,
    confusion_matrix,
    mean_absolute_error,
)
from sklearn.tree import DecisionTreeClassifier, export_text
from sklearn.model_selection import StratifiedKFold, cross_val_score


def calculate_purity(y_true: List[str], y_pred: List[int]) -> float:
    """
    Menghitung purity score antara klaster dan persona ground-truth.
    """
    df = pd.DataFrame({"true": y_true, "pred": y_pred})
    contingency = df.groupby(["pred", "true"]).size().unstack(fill_value=0)
    purity = contingency.max(axis=1).sum() / len(y_true)
    return float(purity)


def evaluate_simulation(
    siswa_outputs: List[Dict[str, Any]],
    raw_siswa_inputs: List[Any],
    csv_path: Optional[str] = None,
    persona_map: Optional[Dict[int, str]] = None
) -> Dict[str, Any]:
    """
    Modul D: Evaluasi Komprehensif AI terhadap Persona Label & Baseline Statistik.
    Persona label HANYA digunakan di sini untuk audit/benchmark, tidak masuk ke fitur model.
    """
    if persona_map is None:
        if not csv_path:
            base_dir = os.path.dirname(os.path.dirname(__file__))
            local_csv = os.path.join(base_dir, "data", "persona_label.csv")
            monorepo_csv = os.path.join(base_dir, "..", "database", "seeders", "data", "persona_label.csv")
            csv_path = local_csv if os.path.exists(local_csv) else monorepo_csv

        if not os.path.exists(csv_path):
            return {"status": "skipped", "message": f"File persona label {csv_path} tidak ditemukan."}

        df_persona = pd.read_csv(csv_path)
        persona_map = dict(zip(df_persona["id_siswa"].astype(int), df_persona["persona"].astype(str)))

    # 1. Evaluasi Clustering vs Persona
    matched_true = []
    matched_pred = []
    output_by_id = {int(o["id_siswa"]): o for o in siswa_outputs}

    for id_s, persona in persona_map.items():
        id_int = int(id_s)
        if id_int in output_by_id:
            matched_true.append(persona)
            matched_pred.append(output_by_id[id_int]["cluster_id"])

    ari_score = float(adjusted_rand_score(matched_true, matched_pred)) if matched_true else 0.0
    purity = calculate_purity(matched_true, matched_pred) if matched_true else 0.0

    # 2. Evaluasi Anomali terhadap Persona P8 (Perubahan Mendadak) & P4/P6
    # Target utama deteksi anomali adalah siswa dengan profil perubahan mendadak (P8)
    y_true_anomaly_p8 = [1 if p == "P8" else 0 for p in matched_true]
    y_pred_anomaly = [1 if output_by_id[id_s]["is_anomaly"] else 0 for id_s in persona_map.keys() if id_s in output_by_id]

    prec_p8 = float(precision_score(y_true_anomaly_p8, y_pred_anomaly, zero_division=0))
    rec_p8 = float(recall_score(y_true_anomaly_p8, y_pred_anomaly, zero_division=0))
    f1_p8 = float(f1_score(y_true_anomaly_p8, y_pred_anomaly, zero_division=0))

    # Anomali komprehensif (P8 perubahan mendadak + P6 risiko ganda)
    y_true_anomaly_p8_p6 = [1 if p in ["P8", "P6"] else 0 for p in matched_true]
    prec_all = float(precision_score(y_true_anomaly_p8_p6, y_pred_anomaly, zero_division=0))
    rec_all = float(recall_score(y_true_anomaly_p8_p6, y_pred_anomaly, zero_division=0))
    f1_all = float(f1_score(y_true_anomaly_p8_p6, y_pred_anomaly, zero_division=0))

    # 3. Evaluasi Tren: MAE Prediksi Nilai vs Baseline Naive (Nilai Terakhir Tetap)
    mae_model_list = []
    mae_baseline_list = []

    for s in raw_siswa_inputs:
        deret_tp = s.deret.tp if s.deret else []
        if len(deret_tp) >= 4:
            train_tp = deret_tp[:-1]
            actual_next = deret_tp[-1]

            # Fit model linear tanpa titik terakhir
            x_fit = np.arange(len(train_tp))
            y_fit = np.array(train_tp, dtype=float)
            slope, intercept = np.polyfit(x_fit, y_fit, 1)
            pred_next = float(np.clip(slope * len(train_tp) + intercept, 0.0, 100.0))

            # Baseline naive: nilai terakhir adalah estimasi langkah berikutnya
            baseline_pred = float(train_tp[-1])

            mae_model_list.append(abs(pred_next - actual_next))
            mae_baseline_list.append(abs(baseline_pred - actual_next))

    avg_mae_model = float(np.mean(mae_model_list)) if mae_model_list else 0.0
    avg_mae_baseline = float(np.mean(mae_baseline_list)) if mae_baseline_list else 0.0

    return {
        "status": "success",
        "clustering": {
            "ari": round(ari_score, 4),
            "purity": round(purity, 4),
            "interpretasi_ari": "Mengukur kesesuaian klaster alami tanpa supervisi terhadap persona siswa.",
        },
        "anomali_p8": {
            "precision": round(prec_p8, 4),
            "recall": round(rec_p8, 4),
            "f1": round(f1_p8, 4),
        },
        "anomali_kritis": {
            "precision": round(prec_all, 4),
            "recall": round(rec_all, 4),
            "f1": round(f1_all, 4),
        },
        "tren_prediksi": {
            "mae_model": round(avg_mae_model, 3),
            "mae_baseline_naive": round(avg_mae_baseline, 3),
            "peningkatan_akurasi_pct": round(
                max(0.0, (avg_mae_baseline - avg_mae_model) / avg_mae_baseline * 100.0), 2
            ) if avg_mae_baseline > 0 else 0.0,
        },
    }
