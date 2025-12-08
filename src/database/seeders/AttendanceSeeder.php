<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('role', 'staff')->get();
        $startDate = Carbon::create(null, 11, 1);
        $endDate = Carbon::create(null, 11, 30);

        foreach ($users as $user) {
            $date = $startDate->copy();
            while ($date <= $endDate) {
                if (rand(1, 100) > 70) {
                    $date->addDay();
                    continue;
                }

                $workStartTime = '09:00:00';
                $workEndTime = '18:00:00';
                $breakCount = rand(0, 2);
                $breakTotalSeconds = 0;
                $breakTimes = [];

                if ($breakCount > 0) {
                    $breakStart = Carbon::createFromFormat('H:i:s', '12:00:00');

                    for ($i = 0; $i < $breakCount; $i++) {
                        $duration = collect([15, 30, 45, 60])->random();

                        $start = $breakStart->copy();
                        $end = $breakStart->copy()->addMinutes($duration);

                        $breakTotalSeconds += $start->diffInSeconds($end);
                        $breakTimes[] = [
                            'break_start' => $start->format('H:i:s'),
                            'break_end' => $end->format('H:i:s'),
                        ];
                        $breakStart->addMinutes($duration + rand(30, 60));
                        if ($breakStart->format('H:i') >= '18:00') break;
                    }
                }

                $totalWorkSeconds = (9 * 3600) - $breakTotalSeconds;

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date->format('Y-m-d'),
                    'work_start' => $workStartTime,
                    'work_end' => $workEndTime,
                    'status' => 'finished',
                    'total_break_time' => gmdate('H:i:s', $breakTotalSeconds),
                    'total_work_time' => gmdate('H:i:s', $totalWorkSeconds),
                    'note' => null,
                ]);

                foreach ($breakTimes as $bt) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $bt['break_start'],
                        'break_end' => $bt['break_end'],
                    ]);
                }

                $date->addDay();
            }
        }
    }
}
