<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Correction;
use App\Models\Attendance;
use App\Models\User;

class CorrectionFactory extends Factory
{
    protected $model = Correction::class;

    public function definition()
    {
        return [
            'attendance_id' => Attendance::factory(),
            'user_id' => User::factory(),
            'corrected_start' => '09:00:00',
            'corrected_end' => '18:00:00',
            'reason' => $this->faker->sentence(),
            'approval_status' => 'pending',
        ];
    }
}
