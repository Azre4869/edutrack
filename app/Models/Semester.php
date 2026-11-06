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
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Scope untuk semester aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Dapatkan semester aktif saat ini, fallback ke semester terbaru.
     */
    public static function aktif(): ?self
    {
        return static::where('is_active', true)->first()
            ?? static::orderBy('id_semester', 'desc')->first();
    }

    /**
     * Dapatkan daftar tahun ajaran unik yang terurut menurun.
     */
    public static function getTahunAjaranList()
    {
        return static::distinct()->orderBy('tahun_ajaran', 'desc')->pluck('tahun_ajaran');
    }
}
