<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiHarian extends Model
{
    protected $table = 'nilai_harian';
    protected $primaryKey = 'id_nilai_harian';
    public $timestamps = false;

    protected $fillable = [
        'id_siswa',
        'id_mapel',
        'id_semester',
        'tanggal',
        'nilai',
        'keterangan',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function mataPelajaran()
    {
        return $this->belongsTo(MataPelajaran::class, 'id_mapel', 'id_mapel');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'id_semester', 'id_semester');
    }
}
