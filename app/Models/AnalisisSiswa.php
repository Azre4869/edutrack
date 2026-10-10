<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalisisSiswa extends Model
{
    protected $table = 'analisis_siswa';
    protected $primaryKey = 'id_analisis';
    public $timestamps = false;

    protected $fillable = [
        'id_siswa',
        'id_semester',
        'id_log',
        'tanggal_analisis',
        'status',
        'ringkasan',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'id_semester', 'id_semester');
    }

    public function log()
    {
        return $this->belongsTo(AnalisisLog::class, 'id_log', 'id_log');
    }

    public function kriteria()
    {
        return $this->belongsToMany(KriteriaAnalisis::class, 'analisis_kriteria', 'id_analisis', 'id_kriteria');
    }

    public function tindakLanjut()
    {
        return $this->hasMany(TindakLanjut::class, 'id_analisis', 'id_analisis');
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class, 'id_analisis', 'id_analisis');
    }
}
