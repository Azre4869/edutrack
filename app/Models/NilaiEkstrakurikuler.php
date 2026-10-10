<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NilaiEkstrakurikuler extends Model
{
    protected $table = 'nilai_ekstrakurikuler';
    protected $primaryKey = 'id_nilai_ekskul';
    public $timestamps = false;

    protected $fillable = [
        'id_siswa',
        'id_ekskul',
        'id_semester',
        'predikat',
        'deskripsi',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function ekstrakurikuler()
    {
        return $this->belongsTo(Ekstrakurikuler::class, 'id_ekskul', 'id_ekskul');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'id_semester', 'id_semester');
    }
}
