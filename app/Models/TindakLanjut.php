<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TindakLanjut extends Model
{
    protected $table = 'tindak_lanjut';
    protected $primaryKey = 'id_tindak_lanjut';
    public $timestamps = false;

    protected $fillable = [
        'id_analisis',
        'id_user',
        'tanggal',
        'waktu',
        'jenis_konseling',
        'peserta_diundang',
        'tempat_media',
        'tindakan',
        'catatan_konseling',
        'rencana_dukungan',
        'status',
        'tanggal_evaluasi',
        'hasil_evaluasi',
        'skor_efektivitas',
    ];

    public function analisisSiswa()
    {
        return $this->belongsTo(AnalisisSiswa::class, 'id_analisis', 'id_analisis');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
