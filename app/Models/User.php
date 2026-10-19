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
}
