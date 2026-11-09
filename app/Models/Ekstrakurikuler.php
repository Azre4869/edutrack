<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

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

    /**
     * Invalidasi cache ekskul saat data berubah.
     */
    protected static function booted(): void
    {
        $invalidate = fn() => Cache::forget('ekskul_all');

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /**
     * Dapatkan semua ekstrakurikuler terurut (cached 30 menit).
     */
    public static function cachedAll()
    {
        return Cache::remember('ekskul_all', 1800, function () {
            return static::orderBy('nama_ekskul')->get();
        });
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function nilaiEkstrakurikuler()
    {
        return $this->hasMany(NilaiEkstrakurikuler::class, 'id_ekskul', 'id_ekskul');
    }
}
