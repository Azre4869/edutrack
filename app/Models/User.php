<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';
    protected $primaryKey = 'id_user';
    public $timestamps = false;

    protected $fillable = [
        'username',
        'password',
        'role',
        'id_guru',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function guru()
    {
        return $this->belongsTo(Guru::class, 'id_guru', 'id_guru');
    }

    public function isOperator(): bool
    {
        return $this->role === 'Operator';
    }

    public function isWaliKelas(): bool
    {
        return $this->role === 'Wali Kelas';
    }

    public function isGuruMapel(): bool
    {
        return $this->role === 'Guru Mapel';
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->guru) {
            return $this->guru->nama_guru;
        }
        if ($this->role === 'Operator') {
            return 'Admin Operator Website';
        }
        return $this->username;
    }

    /**
     * Mengembalikan Kelas yang dibina oleh user (khusus Wali Kelas).
     * Returns null jika user bukan Wali Kelas atau belum punya kelas binaan.
     */
    public function getKelasBinaan(): ?\App\Models\Kelas
    {
        if (!$this->isWaliKelas()) {
            return null;
        }
        return $this->guru ? $this->guru->kelasWali : null;
    }

    /**
     * Mengembalikan koleksi MataPelajaran yang diampu oleh user (khusus Guru Mapel).
     * Returns empty collection jika user bukan Guru Mapel.
     */
    public function getMataPelajaranAmpuan(): \Illuminate\Database\Eloquent\Collection
    {
        if (!$this->isGuruMapel()) {
            return \App\Models\MataPelajaran::whereNull('id_mapel')->get(); // empty collection
        }
        return $this->guru
            ? $this->guru->mataPelajaran()->orderBy('nama_mapel')->get()
            : \App\Models\MataPelajaran::whereNull('id_mapel')->get();
    }
}
