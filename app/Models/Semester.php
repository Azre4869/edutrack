<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    protected $table = 'semester';
    protected $primaryKey = 'id_semester';
    public $timestamps = false;

    protected $fillable = [
        'nama_semester',
        'tahun_ajaran',
    ];
}
