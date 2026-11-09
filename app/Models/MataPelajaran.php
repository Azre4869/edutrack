<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MataPelajaran extends Model
{
    protected $table = 'mata_pelajaran';
    protected $primaryKey = 'id_mapel';
    public $timestamps = false;

    protected $fillable = [
        'nama_mapel',
        'id_guru',
    ];

    /**
     * Invalidasi cache mapel saat data berubah.
     */
    protected static function booted(): void
    {
        $invalidate = fn() => Cache::forget('mapel_all');

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /**
     * Dapatkan semua mata pelajaran terurut (cached 30 menit).
     */
    public static function cachedAll()
    {
        return Cache::remember('mapel_all', 1800, function () {
            return static::orderBy('nama_mapel')->get();
        });
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function nilai()
    {
        return $this->hasMany(Nilai::class, 'id_mapel', 'id_mapel');
    }
}
