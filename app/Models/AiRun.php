<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiRun extends Model
{
    protected $table = 'ai_run';
    public $timestamps = false;

    protected $fillable = [
        'id_log',
        'id_semester',
        'model_version',
        'parameter',
        'metrik',
        'status',
        'dibuat_pada',
    ];

    protected $casts = [
        'parameter' => 'array',
        'metrik' => 'array',
        'dibuat_pada' => 'datetime',
    ];

    public function log()
    {
        return $this->belongsTo(AnalisisLog::class, 'id_log', 'id_log');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'id_semester', 'id_semester');
    }

    public function insights()
    {
        return $this->hasMany(AiInsight::class, 'id_run', 'id');
    }
}
