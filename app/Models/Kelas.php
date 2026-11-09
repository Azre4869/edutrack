<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Kelas extends Model
{
    protected $table = 'kelas';
    protected $primaryKey = 'id_kelas';
    public $timestamps = false;

    protected $fillable = [
        'nama_kelas',
        'tingkat',
        'tahun_ajaran',
        'id_wali_kelas',
    ];

    /**
     * Invalidasi cache kelas saat data berubah.
     */
    protected static function booted(): void
    {
        $invalidate = fn() => Cache::forget('kelas_all');

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /**
     * Dapatkan semua kelas terurut (cached 30 menit).
     */
    public static function cachedAll()
    {
        return Cache::remember('kelas_all', 1800, function () {
            return static::orderBy('nama_kelas')->get();
        });
    }

    public function waliKelas()
    {
        return $this->belongsTo(Guru::class, 'id_wali_kelas', 'id_guru');
    }

    public function siswa()
    {
        return $this->hasMany(Siswa::class, 'id_kelas', 'id_kelas');
    }
}
