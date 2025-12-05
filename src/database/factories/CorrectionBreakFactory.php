<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\CorrectionBreak;
use App\Models\Correction;

class CorrectionBreakFactory extends Factory
{
    protected $model = CorrectionBreak::class;

    public function definition()
    {
        return [
            'correction_id' => Correction::factory(),
            'break_start' => '12:00:00',
            'break_end' => '13:00:00',
        ];
    }
}
