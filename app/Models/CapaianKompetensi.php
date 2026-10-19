<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapaianKompetensi extends Model
{
    protected $table = 'capaian_kompetensi';
    protected $primaryKey = 'id_capaian';
    public $timestamps = false;

    protected $fillable = [
        'id_nilai',
        'deskripsi_1',
        'deskripsi_2',
    ];

    public function nilai()
    {
        return $this->belongsTo(Nilai::class, 'id_nilai', 'id_nilai');
    }
}
