<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $date = Carbon::today();

        return [
            'user_id' => User::factory(),
            'work_date' => $date->toDateString(),
            'work_start' => '09:00:00',
            'work_end' => null,
            'status' => 'present',
            'total_break_time' => 0,
            'total_work_time' => 0,
            'note' => '',
        ];
    }

    public function present()
    {
        return $this->state([
            'status' => 'present',
            'work_start' => '09:00:00',
            'work_end' => null,
        ]);
    }

    public function break()
    {
        return $this->state([
            'status' => 'break',
            'work_start' => '09:00:00',
            'work_end' => null,
        ]);
    }

    public function finished()
    {
        return $this->state([
            'status' => 'finished',
            'work_start' => '09:00:00',
            'work_end' => '18:00:00',
        ]);
    }
}
