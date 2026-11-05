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
        'nisn',
        'nama',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'agama',
        'status_anak',
        'anak_ke',
        'alamat',
        'no_telepon',
        'sekolah_asal',
        'kelas_diterima',
        'tanggal_diterima',
        'nama_ayah',
        'nama_ibu',
        'pekerjaan_ayah',
        'pekerjaan_ibu',
        'alamat_ortu',
        'telp_ortu',
        'nama_wali',
        'pekerjaan_wali',
        'alamat_wali',
        'telp_wali',
        'status_siswa',
        'id_kelas',
    ];

    public function getNoTelpAttribute()
    {
        return $this->attributes['no_telepon'] ?? '';
    }

    public function getStatusKeluargaAttribute()
    {
        return $this->attributes['status_anak'] ?? '';
    }

    public function getStatusDalamKeluargaAttribute()
    {
        return $this->attributes['status_anak'] ?? '';
    }

    public function setStatusDalamKeluargaAttribute($value)
    {
        $this->attributes['status_anak'] = $value;
    }

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

    public function catatanWali()
    {
        return $this->hasMany(CatatanWali::class, 'id_siswa', 'id_siswa');
    }

    public function aiInsights()
    {
        return $this->hasMany(AiInsight::class, 'id_siswa', 'id_siswa');
    }

    public function aiInsight()
    {
        return $this->hasOne(AiInsight::class, 'id_siswa', 'id_siswa')->latestOfMany('id');
    }
}
