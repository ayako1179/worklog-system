<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Attendance\AdminAttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function list(Request $request)
    {
        $date = $request->query('date', now()->toDateString());

        $currentDate = \Carbon\Carbon::parse($date);

        $prevDate = $currentDate->copy()->subDay()->toDateString();
        $nextDate = $currentDate->copy()->addDay()->toDateString();

        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $currentDate)
            ->orderBy('user_id')
            ->get();

        return view('admin.attendance.list', compact(
            'attendances',
            'currentDate',
            'prevDate',
            'nextDate'
        ));
    }

    // 勤怠詳細画面（管理者）
    public function show($id)
    {
        // 勤怠データ
        $attendance = \App\Models\Attendance::with(['user', 'breakTimes'])
            ->findOrFail($id);

        // 備考の表示用
        $displayNote = $attendance->note ?? '';

        // 休憩データ
        $breakTimes = $attendance->breakTimes;

        return view('admin.attendance.detail', compact(
            'attendance',
            'breakTimes',
            'displayNote'
        ));
    }

    public function update(AdminAttendanceUpdateRequest $request, $id)
    {
        // 対象勤怠を取得（休憩込み）
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        // 勤怠情報の更新
        $attendance->work_date = $request->work_date;
        $attendance->work_start = $request->work_start;
        $attendance->work_end = $request->work_end;
        $attendance->note = $request->note;
        $attendance->save();

        // 休憩の更新
        foreach ($attendance->breakTimes as $index => $bt) {

            if (isset($request->breaks[$index])) {

                $start = $request->breaks[$index]['start'] ?? null;
                $end = $request->breaks[$index]['end'] ?? null;

                $bt->break_start = $start;
                $bt->break_end = $end;
                $bt->save();
            }
        }

        // 新規休憩の追加
        if (isset($request->breaks['new'])) {

            $bs = $request->breaks['new']['start'] ?? null;
            $be = $request->breaks['new']['end'] ?? null;

            if ($bs && $be) {
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $bs,
                    'break_end' => $be,
                ]);
            }
        }

        return redirect()->back();
    }

    public function staffMonthlyList(Request $request, $id)
    {
        // 対象スタッフ
        $staff = User::where('id', $id)->firstOrFail();

        // 表示する月（指定がなければ現在月）
        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month') . '-01')
            : Carbon::now();

        // 前月・翌月ボタン用
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // 対象スタッフの当月の勤怠情報
        $attendances = Attendance::where('user_id', $id)
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->get()
            ->keyBy(function ($item) {
                return Carbon::parse($item->work_date)->toDateString();
            });

        if ($request->has('csv')) {
            return $this->exportCsv($staff, $attendances, $currentMonth);
        }

        // 月の日付リスト
        $dates = collect();
        $start = $currentMonth->copy()->startOfMonth();
        $end = $currentMonth->copy()->endOfMonth();
        for ($d = $start->copy(); $d <= $end; $d->addDay()) {
            $dates->push($d->copy());
        }

        return view(
            'admin.attendance.staff-month',
            compact('staff', 'attendances', 'currentMonth', 'prevMonth', 'nextMonth', 'dates')
        );
    }

    private function exportCsv($staff, $attendances, $currentMonth)
    {
        $fileName = "attendance_{$staff->id}_{$currentMonth->format('Y_m')}.csv";

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\""
        ];

        $columns = ['日付', '出勤', '退勤', '休憩合計', '勤務合計'];

        return new StreamedResponse(function () use ($attendances, $columns) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $columns);

            foreach ($attendances as $attendance) {

                $break = $attendance->total_break_time
                    ? substr($attendance->total_break_time, 0, 5)
                    : '';

                $work = $attendance->total_work_time
                    ? substr($attendance->total_work_time, 0, 5)
                    : '';

                fputcsv($handle, [
                    $attendance->work_date,
                    $attendance->work_start ? \Carbon\Carbon::parse($attendance->work_start)->format('H:i') : '',
                    $attendance->work_end ? \Carbon\Carbon::parse($attendance->work_end)->format('H:i') : '',
                    $break,
                    $work,
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
