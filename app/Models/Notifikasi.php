<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';
    protected $primaryKey = 'id_notifikasi';
    public $timestamps = false;

    protected $fillable = [
        'id_analisis',
        'id_user',
        'tipe',
        'pesan',
        'metadata',
        'sudah_dibaca',
        'tanggal_kirim',
    ];

    protected $casts = [
        'metadata' => 'array',
        'sudah_dibaca' => 'boolean',
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
