from typing import List, Optional, Dict, Any
from pydantic import BaseModel, Field


class HarianPoint(BaseModel):
    t: str
    v: float


class DeretData(BaseModel):
    tp: List[float] = Field(default_factory=list)
    harian: List[HarianPoint] = Field(default_factory=list)
    hadir_mingguan: List[float] = Field(default_factory=list)


class FiturData(BaseModel):
    rata_nilai_akhir: Optional[float] = None
    simpangan_antar_mapel: Optional[float] = None
    jumlah_mapel_di_bawah_kkm: Optional[int] = 0
    persen_hadir: Optional[float] = None
    jumlah_alpha: Optional[int] = 0
    alpha_beruntun_maks: Optional[int] = 0
    slope_tp: Optional[float] = 0.0
    selisih_nilai_vs_semester_lalu: Optional[float] = None
    slope_kehadiran_mingguan: Optional[float] = 0.0
    perubahan_kehadiran_3_minggu: Optional[float] = 0.0
    perubahan_nilai_harian_3_minggu: Optional[float] = 0.0


class SiswaInput(BaseModel):
    id_siswa: int
    kelas_id: int
    fitur: FiturData
    deret: Optional[DeretData] = Field(default_factory=DeretData)


class KriteriaInput(BaseModel):
    ambang_penurunan: float = 10.0
    ambang_alpha: float = 3.0


class AnalyzeRequest(BaseModel):
    semester_id: int
    kkm: float = 75.0
    contamination: float = 0.08
    kriteria: Optional[KriteriaInput] = Field(default_factory=KriteriaInput)
    siswa: List[SiswaInput]
    persona_labels: Optional[Dict[str, str]] = None


class AnomalyReason(BaseModel):
    fitur: str
    nilai: float
    rata_kelas: float
    z: float
    teks_penjelasan: str


class SiswaOutput(BaseModel):
    id_siswa: int
    cluster_id: int
    cluster_label: str
    cluster_distance: float
    pca_x: float
    pca_y: float
    anomaly_score: float
    is_anomaly: bool
    alasan: List[AnomalyReason] = Field(default_factory=list)
    slope_nilai: float
    prediksi_nilai: float
    slope_kehadiran: float
    prediksi_kehadiran: float
    berpotensi_turun: bool
    data_cukup: bool


class ClusterSummary(BaseModel):
    cluster_id: int
    label: str
    deskripsi: str
    jumlah_siswa: int
    persentase: float
    rata_rata_fitur: Dict[str, float]


class KlasterOverview(BaseModel):
    k: int
    silhouette_score: float
    ringkasan: List[ClusterSummary]


class AnalyzeResponse(BaseModel):
    model_version: str = "1.0.0-kmeans-iforest"
    waktu_proses: str
    status: str = "success"
    total_siswa: int
    klaster: KlasterOverview
    hasil: List[SiswaOutput]
