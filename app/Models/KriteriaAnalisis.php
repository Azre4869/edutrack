<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KriteriaAnalisis extends Model
{
    protected $table = 'kriteria_analisis';
    protected $primaryKey = 'id_kriteria';
    public $timestamps = false;

    protected $fillable = [
        'nama_kriteria',
        'jenis',
        'parameter',
        'nilai_ambang',
        'aktif',
    ];

    public function analisisSiswa()
    {
        return $this->belongsToMany(AnalisisSiswa::class, 'analisis_kriteria', 'id_kriteria', 'id_analisis');
    }
}
