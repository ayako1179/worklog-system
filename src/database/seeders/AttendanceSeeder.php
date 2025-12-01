<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;
// use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 対象ユーザー（一般ユーザーのみ）
        $users = User::where('role', 'staff')->get();

        // 対象月：固定で10月
        $startDate = Carbon::create(null, 11, 1);
        $endDate = Carbon::create(null, 11, 30);

        foreach ($users as $user) {

            $date = $startDate->copy();
            while ($date <= $endDate) {

                // 出勤率 70%
                if (rand(1, 100) > 70) {
                    $date->addDay();
                    continue;
                }

                // 出勤・退勤時刻
                $workStartTime = '09:00:00';
                $workEndTime = '18:00:00';

                // 休憩回数 0～2 回
                $breakCount = rand(0, 2);

                // 休憩データ生成
                $breakTotalSeconds = 0;
                $breakTimes = [];

                if ($breakCount > 0) {
                    $breakStart = Carbon::createFromFormat('H:i:s', '12:00:00');

                    for ($i = 0; $i < $breakCount; $i++) {

                        // 休憩時間（分）
                        $duration = collect([15, 30, 45, 60])->random();

                        $start = $breakStart->copy();
                        $end = $breakStart->copy()->addMinutes($duration);

                        // 休憩時間の積算
                        $breakTotalSeconds += $start->diffInSeconds($end);

                        $breakTimes[] = [
                            'break_start' => $start->format('H:i:s'),
                            'break_end' => $end->format('H:i:s'),
                        ];

                        // 次の休憩開始位置を多少ずらす（30～60分後）
                        $breakStart->addMinutes($duration + rand(30, 60));
                        if ($breakStart->format('H:i') >= '18:00') break;
                    }
                }

                // 勤務合計 = (退勤 - 出勤) - 休憩合計
                $totalWorkSeconds = (9 * 3600) - $breakTotalSeconds;

                // 勤怠レコード作成
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

                // 休憩レコード作成
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
