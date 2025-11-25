<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_id',
        'break_start',
        'break_end',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    public function correction()
    {
        return $this->belongsTo(Correction::class);
    }
}
