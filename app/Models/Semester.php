<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Semester extends Model
{
    protected $table = 'semester';
    protected $primaryKey = 'id_semester';
    public $timestamps = false;

    protected $fillable = [
        'nama_semester',
        'tahun_ajaran',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Invalidasi semua cache semester saat data berubah.
     */
    protected static function booted(): void
    {
        $invalidate = fn() => Cache::forget('semester_all')
            | Cache::forget('semester_aktif')
            | Cache::forget('semester_tahun_ajaran_list');

        static::saved($invalidate);
        static::deleted($invalidate);
    }

    /**
     * Scope untuk semester aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Dapatkan semester aktif saat ini (cached 10 menit), fallback ke semester terbaru.
     */
    public static function aktif(): ?self
    {
        return Cache::remember('semester_aktif', 600, function () {
            return static::where('is_active', true)->first()
                ?? static::orderBy('id_semester', 'desc')->first();
        });
    }

    /**
     * Dapatkan semua semester terurut (cached 30 menit).
     */
    public static function cachedAll()
    {
        return Cache::remember('semester_all', 1800, function () {
            return static::orderBy('id_semester')->get();
        });
    }

    /**
     * Dapatkan daftar tahun ajaran unik yang terurut menurun (cached 30 menit).
     */
    public static function getTahunAjaranList()
    {
        return Cache::remember('semester_tahun_ajaran_list', 1800, function () {
            return static::distinct()->orderBy('tahun_ajaran', 'desc')->pluck('tahun_ajaran');
        });
    }
}
