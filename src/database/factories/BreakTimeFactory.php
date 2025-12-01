<?php

namespace Database\Factories;

use App\Models\Attendance;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class BreakTimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $start = Carbon::today()->setTime(12, 0);

        return [
            'attendance_id' => Attendance::factory(),
            'break_start' => $start,
            'break_end' => null,
        ];
    }

    public function ended()
    {
        return $this->state(function (array $attributes) {
            return [
                'break_end' => Carbon::parse($attributes['break_start'])->addHour(),
            ];
        });
    }
}
