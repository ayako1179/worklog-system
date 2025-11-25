<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'work_start',
        'work_end',
        'status',
        'total_break_time',
        'total_work_time',
        'note',
    ];

    protected $casts = [
        'work_date' => 'date',
        // 'work_start' => 'datetime:H:i',
        // 'work_end' => 'datetime:H:i',
        // 'total_break_time' => 'datetime:H:i:s',
        // 'total_work_time' => 'datetime:H:i:s',
    ];

    public function getWorkStartAttribute($value)
    {
        if ($value === null) return null;
        return Carbon::parse($value)->format('H:i');
    }

    public function getWorkEndAttribute($value)
    {
        if ($value === null) return null;
        return Carbon::parse($value)->format('H:i');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function corrections()
    {
        return $this->hasMany(Correction::class);
    }
}
