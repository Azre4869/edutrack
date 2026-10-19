<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    protected $table = 'siswa';
    protected $primaryKey = 'id_siswa';
    public $timestamps = false;

    protected $fillable = [
        'nis',
        'nama',
        'jenis_kelamin',
        'id_kelas',
    ];

    public function getNamaSiswaAttribute()
    {
        return $this->attributes['nama'] ?? ($this->attributes['nama_siswa'] ?? '');
    }

    public function setNamaSiswaAttribute($value)
    {
        $this->attributes['nama'] = $value;
    }

    public function kelas()
    {
        return $this->belongsTo(Kelas::class, 'id_kelas', 'id_kelas');
    }

    public function nilai()
    {
        return $this->hasMany(Nilai::class, 'id_siswa', 'id_siswa');
    }

    public function nilaiHarian()
    {
        return $this->hasMany(NilaiHarian::class, 'id_siswa', 'id_siswa');
    }

    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'id_siswa', 'id_siswa');
    }

    public function nilaiEkstrakurikuler()
    {
        return $this->hasMany(NilaiEkstrakurikuler::class, 'id_siswa', 'id_siswa');
    }

    public function prestasi()
    {
        return $this->hasMany(Prestasi::class, 'id_siswa', 'id_siswa');
    }

    public function analisisSiswa()
    {
        return $this->hasMany(AnalisisSiswa::class, 'id_siswa', 'id_siswa');
    }
}
