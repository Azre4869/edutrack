<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalisisLog extends Model
{
    protected $table = 'analisis_log';
    protected $primaryKey = 'id_log';
    public $timestamps = false;

    protected $fillable = [
        'waktu_jalan',
        'pemicu',
        'jumlah_siswa_diproses',
    ];

    public function analisisSiswa()
    {
        return $this->hasMany(AnalisisSiswa::class, 'id_log', 'id_log');
    }
}
