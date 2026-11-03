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
    ];

    public function nilaiEkstrakurikuler()
    {
        return $this->hasMany(NilaiEkstrakurikuler::class, 'id_ekskul', 'id_ekskul');
    }
}
