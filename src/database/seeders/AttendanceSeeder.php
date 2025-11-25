<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;
use App\Models\CorrectionBreak;
use Illuminate\Support\Carbon;

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
        $now = Carbon::now();
        $startDate = $now->copy()->subMonths(2)->startOfMonth();
        $endDate = $now->copy()->yesterday();

        foreach ($users as $user) {
            $dates = collect();
            $date = $startDate->copy();

            while ($date <= $endDate) {
                $dates->push($date->copy());
                $date->addDay();
            }

            $attendancesByMonth = [];

            foreach ($dates as $day) {
                if (rand(1, 100) > 75) continue;

                $workStart = $day->copy()->setTime(9, 0);
                $workEnd = $day->copy()->setTime(18, 0);
                $totalBreakMinutes = collect([60, 75, 90])->random();
                $totalWorkMinutes = 540 - $totalBreakMinutes;

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $day->toDateString(),
                    'work_start' => $workStart,
                    'work_end' => $workEnd,
                    'status' => 'finished',
                    'total_break_time' => gmdate('H:i:s', $totalBreakMinutes * 60),
                    'total_work_time' => gmdate('H:i:s', $totalWorkMinutes * 60),
                    'note' => null,
                ]);

                // 複数休憩をランダム生成（1～3件）
                $breakCount = rand(1, 3);
                $breakStart = $workStart->copy()->addHours(1);

                for ($i = 0; $i < $breakCount; $i++) {
                    $duration = collect([10, 15, 30, 60])->random();
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStart->copy(),
                        'break_end' => $breakStart->copy()->addMinutes($duration),
                    ]);
                    $breakStart->addMinutes($duration + 30);
                }

                // 生成した休憩データから合計休憩を計算
                $breakRecords = BreakTime::where('attendance_id', $attendance->id)->get();

                $totalBreakMinutes = $breakRecords->reduce(function ($carry, $bt) {
                    return $carry + Carbon::parse($bt->break_start)
                        ->diffInMinutes(Carbon::parse($bt->break_end));
                }, 0);

                $attendance->update([
                    'total_break_time' => gmdate('H:i:s', $totalBreakMinutes * 60),
                ]);

                // 月単位で勤怠記録を保存（修正申請で使用）
                $monthKey = $day->format('Y-m');
                $attendancesByMonth[$monthKey][] = $attendance;
            }

            // 修正申請（各月1～5件ランダム）
            foreach ($attendancesByMonth as $month => $attendanceList) {
                $count = min(rand(1, 5), count($attendanceList));
                $targets = collect($attendanceList)->random($count);

                foreach ($targets as $target) {
                    $approval = 'approved';

                    $target->update(['status' => 'pending']);

                    $correctedStart = Carbon::parse($target->work_start)->subMinutes(rand(10, 30));
                    $correctedEnd = Carbon::parse($target->work_end)->addMinutes(rand(10, 30));

                    $approval = collect(['pending', 'approved'])->random();

                    $correction = Correction::create([
                        'attendance_id' => $target->id,
                        'user_id' => $user->id,
                        'corrected_start' => $correctedStart,
                        'corrected_end' => $correctedEnd,
                        'reason' => collect([
                            '遅延のため',
                            '体調不良',
                            '打刻忘れ',
                        ])->random(),
                        'approval_status' => $approval,
                    ]);

                    // 修正用休憩（例として12:30～13:00）
                    CorrectionBreak::create([
                        'correction_id' => $correction->id,
                        'break_start' => $correctedStart->copy()->addHours(3),
                        'break_end' => $correctedStart->copy()->addHours(3)->addMinutes(30),
                    ]);
                }
            }
        }
    }
}
