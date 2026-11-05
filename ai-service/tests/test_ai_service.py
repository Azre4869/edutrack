import os
import sys

# Tambahkan ai-service ke PYTHONPATH
sys.path.insert(0, os.path.abspath(os.path.join(os.path.dirname(__file__), "..")))

import pytest
from fastapi.testclient import TestClient

from app.main import app
from app.schemas import AnalyzeRequest, SiswaInput, FiturData, DeretData, HarianPoint

client = TestClient(app)


def make_dummy_student(id_siswa: int, kelas_id: int = 1, nilai: float = 80.0, alpha: int = 0) -> dict:
    return {
        "id_siswa": id_siswa,
        "kelas_id": kelas_id,
        "fitur": {
            "rata_nilai_akhir": nilai,
            "simpangan_antar_mapel": 3.5,
            "jumlah_mapel_di_bawah_kkm": 0 if nilai >= 75 else 2,
            "persen_hadir": 98.0 if alpha == 0 else 88.0,
            "jumlah_alpha": alpha,
            "alpha_beruntun_maks": alpha,
            "slope_tp": 0.2,
            "selisih_nilai_vs_semester_lalu": 0.0,
            "slope_kehadiran_mingguan": 0.0,
            "perubahan_kehadiran_3_minggu": 0.0,
            "perubahan_nilai_harian_3_minggu": 0.0,
        },
        "deret": {
            "tp": [nilai - 2, nilai, nilai + 1, nilai],
            "harian": [
                {"t": "2026-09-01", "v": nilai},
                {"t": "2026-09-15", "v": nilai + 2},
                {"t": "2026-10-01", "v": nilai - 1},
                {"t": "2026-10-15", "v": nilai},
            ],
            "hadir_mingguan": [100.0, 100.0, 95.0, 100.0],
        },
    }


def test_health_endpoint():
    res = client.get("/health")
    assert res.status_code == 200
    data = res.json()
    assert data["status"] == "ok"
    assert "version" in data


def test_deterministic_analyze_output():
    """
    Uji Determinisme: Dua kali eksekusi dengan data yang sama wajib menghasilkan hasil identik.
    """
    students = [
        make_dummy_student(1, kelas_id=1, nilai=95.0, alpha=0),
        make_dummy_student(2, kelas_id=1, nilai=92.0, alpha=0),
        make_dummy_student(3, kelas_id=1, nilai=82.0, alpha=0),
        make_dummy_student(4, kelas_id=1, nilai=79.0, alpha=1),
        make_dummy_student(5, kelas_id=1, nilai=65.0, alpha=6),
        make_dummy_student(6, kelas_id=1, nilai=62.0, alpha=7),
        make_dummy_student(7, kelas_id=1, nilai=73.0, alpha=0),
        make_dummy_student(8, kelas_id=1, nilai=85.0, alpha=0),
    ]

    payload = {
        "semester_id": 2,
        "kkm": 75.0,
        "contamination": 0.08,
        "siswa": students,
    }

    res1 = client.post("/analyze", json=payload)
    assert res1.status_code == 200
    data1 = res1.json()

    res2 = client.post("/analyze", json=payload)
    assert res2.status_code == 200
    data2 = res2.json()

    # Cek cluster_id dan anomaly_score identik
    for idx in range(len(students)):
        h1 = data1["hasil"][idx]
        h2 = data2["hasil"][idx]
        assert h1["id_siswa"] == h2["id_siswa"]
        assert h1["cluster_id"] == h2["cluster_id"]
        assert h1["anomaly_score"] == h2["anomaly_score"]
        assert h1["is_anomaly"] == h2["is_anomaly"]
        assert h1["prediksi_nilai"] == h2["prediksi_nilai"]


def test_handling_sparse_data_less_than_three_points():
    """
    Uji jika data deret < 3 titik: data_cukup wajib bernilai false dan regresi tidak memaksakan prediksi.
    """
    sparse_student = {
        "id_siswa": 99,
        "kelas_id": 1,
        "fitur": {
            "rata_nilai_akhir": 75.0,
            "simpangan_antar_mapel": 0.0,
            "jumlah_mapel_di_bawah_kkm": 0,
            "persen_hadir": 95.0,
            "jumlah_alpha": 0,
        },
        "deret": {
            "tp": [75.0],  # hanya 1 titik data!
            "harian": [],
            "hadir_mingguan": [100.0],  # hanya 1 minggu!
        },
    }

    payload = {
        "semester_id": 2,
        "kkm": 75.0,
        "siswa": [sparse_student, make_dummy_student(1), make_dummy_student(2), make_dummy_student(3)],
    }

    res = client.post("/analyze", json=payload)
    assert res.status_code == 200
    data = res.json()

    student_res = next(s for s in data["hasil"] if s["id_siswa"] == 99)
    assert student_res["data_cukup"] is False
    assert 0.0 <= student_res["prediksi_nilai"] <= 100.0


def test_handling_nan_and_none_features():
    """
    Uji ketahanan terhadap nilai None/NaN pada fitur siswa.
    """
    nan_student = {
        "id_siswa": 88,
        "kelas_id": 1,
        "fitur": {
            "rata_nilai_akhir": None,
            "simpangan_antar_mapel": None,
            "persen_hadir": None,
            "selisih_nilai_vs_semester_lalu": None,
        },
        "deret": {
            "tp": [80.0, 82.0, 81.0],
            "harian": [],
            "hadir_mingguan": [100.0, 95.0, 95.0],
        },
    }

    payload = {
        "semester_id": 2,
        "kkm": 75.0,
        "siswa": [nan_student, make_dummy_student(1), make_dummy_student(2), make_dummy_student(3)],
    }

    res = client.post("/analyze", json=payload)
    assert res.status_code == 200
    data = res.json()
    assert len(data["hasil"]) == 4


def test_small_cohort_auto_k_adjustment():
    """
    Uji jika kohort kecil (misal 4 siswa): k clustering otomatis turun tanpa error.
    """
    students = [
        make_dummy_student(1, nilai=90.0),
        make_dummy_student(2, nilai=85.0),
        make_dummy_student(3, nilai=70.0),
        make_dummy_student(4, nilai=60.0),
    ]

    payload = {
        "semester_id": 2,
        "kkm": 75.0,
        "siswa": students,
    }

    res = client.post("/analyze", json=payload)
    assert res.status_code == 200
    data = res.json()
    assert data["klaster"]["k"] < 4
    assert len(data["hasil"]) == 4
