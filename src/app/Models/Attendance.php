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
        'work_start' => 'datetime:H:i:s',
        'work_end' => 'datetime:H:i:s',
    ];

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
