<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInsight extends Model
{
    protected $table = 'ai_insight';
    public $timestamps = false;

    protected $fillable = [
        'id_run',
        'id_siswa',
        'id_semester',
        'cluster_id',
        'cluster_label',
        'cluster_distance',
        'pca_x',
        'pca_y',
        'anomaly_score',
        'is_anomaly',
        'alasan',
        'slope_nilai',
        'prediksi_nilai',
        'slope_kehadiran',
        'prediksi_kehadiran',
        'berpotensi_turun',
        'data_cukup',
        'dihitung_pada',
    ];

    protected $casts = [
        'cluster_id' => 'integer',
        'cluster_distance' => 'float',
        'pca_x' => 'float',
        'pca_y' => 'float',
        'anomaly_score' => 'float',
        'is_anomaly' => 'boolean',
        'alasan' => 'array',
        'slope_nilai' => 'float',
        'prediksi_nilai' => 'float',
        'slope_kehadiran' => 'float',
        'prediksi_kehadiran' => 'float',
        'berpotensi_turun' => 'boolean',
        'data_cukup' => 'boolean',
        'dihitung_pada' => 'datetime',
    ];

    public function run()
    {
        return $this->belongsTo(AiRun::class, 'id_run', 'id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'id_semester', 'id_semester');
    }
}
