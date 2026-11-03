<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatatanWali extends Model
{
    protected $table = 'catatan_wali';
    protected $primaryKey = 'id_catatan';
    public $timestamps = false;

    protected $fillable = [
        'id_siswa',
        'id_semester',
        'sikap_spiritual',
        'sikap_sosial',
        'catatan_wali',
        'status_kenaikan',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'id_siswa', 'id_siswa');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'id_semester', 'id_semester');
    }
}
