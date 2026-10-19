<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    protected $table = 'guru';
    protected $primaryKey = 'id_guru';
    public $timestamps = false;

    protected $fillable = [
        'nip',
        'nama_guru',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id_guru', 'id_guru');
    }

    public function kelasWali()
    {
        return $this->hasOne(Kelas::class, 'id_wali_kelas', 'id_guru');
    }

    public function mataPelajaran()
    {
        return $this->hasMany(MataPelajaran::class, 'id_guru', 'id_guru');
    }
}
