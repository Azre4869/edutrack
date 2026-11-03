<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ekstrakurikuler extends Model
{
    protected $table = 'ekstrakurikuler';
    protected $primaryKey = 'id_ekskul';
    public $timestamps = false;

    protected $fillable = [
        'nama_ekskul',
        'id_guru',
        'jadwal',
        'status',
    ];

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function nilaiEkstrakurikuler()
    {
        return $this->hasMany(NilaiEkstrakurikuler::class, 'id_ekskul', 'id_ekskul');
    }
}
