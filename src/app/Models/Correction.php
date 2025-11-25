<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Correction extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'corrected_start',
        'corrected_end',
        'reason',
        'approval_status',
    ];

    protected $casts = [
        'corrected_start' => 'datetime',
        'corrected_end' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function correctionBreaks()
    {
        return $this->hasMany(CorrectionBreak::class);
    }
}
