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
        'waktu_selesai',
        'pemicu',
        'jumlah_siswa_diproses',
        'total_berisiko',
        'status',
        'pesan_error',
        'id_user',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function analisisSiswa()
    {
        return $this->hasMany(AnalisisSiswa::class, 'id_log', 'id_log');
    }
}
