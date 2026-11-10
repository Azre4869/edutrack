from datetime import datetime, timezone
import os
from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware

from app.schemas import (
    AnalyzeRequest,
    AnalyzeResponse,
    KlasterOverview,
    ClusterSummary,
    SiswaOutput,
    AnomalyReason,
)
from app.clustering import run_clustering
from app.anomaly import detect_anomalies
from app.trend import analyze_trends
from app.evaluator import evaluate_simulation

app = FastAPI(
    title="EduTrack Local AI Service",
    description="Layanan Analisis Prediktif & Machine Learning Lokal EduTrack (Tanpa LLM, Tanpa Internet, 100% Deterministik)",
    version="1.0.0",
)

# CORS untuk integrasi internal aman
app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://127.0.0.1:8000", "http://localhost:8000"],
    allow_credentials=True,
    allow_methods=["GET", "POST"],
    allow_headers=["*"],
)


@app.get("/health")
def health_check():
    """
    Endpoint pemantauan kesehatan service lokal.
    """
    return {
        "status": "ok",
        "service": "EduTrack Local AI Service",
        "version": "1.0.0",
        "timestamp": datetime.now(timezone.utc).isoformat(),
        "environment": "local-airgapped",
    }


@app.post("/analyze", response_model=AnalyzeResponse)
def analyze(req: AnalyzeRequest):
    """
    Endpoint utama Analisis AI EduTrack:
    - Modul A: K-Means Clustering + Auto Labeling + PCA 2D
    - Modul B: Deteksi Anomali Gabungan (Isolation Forest + Robust Z-Score + Sudden Drop)
    - Modul C: Tren & Prediksi Linier Nilai & Kehadiran
    """
    if not req.siswa:
        raise HTTPException(status_code=400, detail="Data siswa tidak boleh kosong.")

    # 1. Eksekusi Modul A (Clustering)
    clustering_res = run_clustering(req.siswa, random_state=42)
    per_siswa_cluster = clustering_res["per_siswa"]

    # 2. Eksekusi Modul B (Deteksi Anomali)
    anomaly_res = detect_anomalies(
        req.siswa,
        contamination=req.contamination,
        random_state=42
    )

    # 3. Eksekusi Modul C (Tren & Prediksi) & Gabungkan Hasil
    kkm_val = float(req.kkm or 75.0)
    ambang_turun = float(req.kriteria.ambang_penurunan if req.kriteria else 10.0)

    siswa_outputs: list[SiswaOutput] = []

    for s in req.siswa:
        id_s = s.id_siswa
        c_info = per_siswa_cluster.get(id_s, {
            "cluster_id": 0,
            "cluster_label": "Stabil",
            "cluster_distance": 0.0,
            "pca_x": 0.0,
            "pca_y": 0.0,
        })
        a_info = anomaly_res.get(id_s, {
            "anomaly_score": 0.0,
            "is_anomaly": False,
            "alasan": [],
        })
        t_info = analyze_trends(s, kkm=kkm_val, ambang_penurunan=ambang_turun)

        # Convert reasons
        reasons_pydantic = [
            AnomalyReason(
                fitur=r["fitur"],
                nilai=r["nilai"],
                rata_kelas=r["rata_kelas"],
                z=r["z"],
                teks_penjelasan=r["teks_penjelasan"],
            )
            for r in a_info.get("alasan", [])
        ]

        out = SiswaOutput(
            id_siswa=id_s,
            cluster_id=c_info["cluster_id"],
            cluster_label=c_info["cluster_label"],
            cluster_distance=c_info["cluster_distance"],
            pca_x=c_info["pca_x"],
            pca_y=c_info["pca_y"],
            anomaly_score=a_info["anomaly_score"],
            is_anomaly=a_info["is_anomaly"],
            alasan=reasons_pydantic,
            slope_nilai=t_info["slope_nilai"],
            prediksi_nilai=t_info["prediksi_nilai"],
            slope_kehadiran=t_info["slope_kehadiran"],
            prediksi_kehadiran=t_info["prediksi_kehadiran"],
            berpotensi_turun=t_info["berpotensi_turun"],
            data_cukup=t_info["data_cukup"],
        )
        siswa_outputs.append(out)

    # Format ringkasan klaster
    ringkasan_pydantic = [
        ClusterSummary(
            cluster_id=r["cluster_id"],
            label=r["label"],
            deskripsi=r["deskripsi"],
            jumlah_siswa=r["jumlah_siswa"],
            persentase=r["persentase"],
            rata_rata_fitur=r["rata_rata_fitur"],
        )
        for r in clustering_res["ringkasan"]
    ]

    klaster_overview = KlasterOverview(
        k=clustering_res["k"],
        silhouette_score=clustering_res["silhouette_score"],
        ringkasan=ringkasan_pydantic,
    )

    return AnalyzeResponse(
        model_version="1.0.0-kmeans-iforest",
        waktu_proses=datetime.now(timezone.utc).isoformat(),
        status="success",
        total_siswa=len(siswa_outputs),
        klaster=klaster_overview,
        hasil=siswa_outputs,
    )


@app.post("/evaluate")
def evaluate(req: AnalyzeRequest):
    """
    Endpoint evaluasi model (hanya saat dev mode aktif atau dipanggil penguji).
    Mengukur kesesuaian klaster vs ground-truth persona_label.csv serta performa deteksi anomali.
    """
    dev_mode = os.getenv("AI_DEV_MODE", "true").lower() in ["true", "1", "yes"]
    if not dev_mode:
        raise HTTPException(status_code=403, detail="Endpoint evaluasi dinonaktifkan di mode non-dev.")

    # Jalankan analisis terlebih dahulu
    analysis_res = analyze(req)
    siswa_outputs_raw = [o.model_dump() for o in analysis_res.hasil]

    base_dir = os.path.dirname(os.path.dirname(__file__))
    local_csv = os.path.join(base_dir, "data", "persona_label.csv")
    monorepo_csv = os.path.join(base_dir, "..", "database", "seeders", "data", "persona_label.csv")
    target_csv = local_csv if os.path.exists(local_csv) else monorepo_csv

    eval_result = evaluate_simulation(
        siswa_outputs=siswa_outputs_raw,
        raw_siswa_inputs=req.siswa,
        csv_path=target_csv
    )

    return {
        "status": eval_result.get("status", "success"),
        "waktu_evaluasi": datetime.now(timezone.utc).isoformat(),
        "klaster_overview": analysis_res.klaster.model_dump(),
        "clustering": eval_result.get("clustering", {}),
        "anomali_p8": eval_result.get("anomali_p8", {}),
        "anomali_kritis": eval_result.get("anomali_kritis", {}),
        "tren_prediksi": eval_result.get("tren_prediksi", {}),
        "evaluasi": eval_result,
    }
