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

        foreach ($attendances as $attendance) {
            $totalBreakMinutes = 0;

            foreach ($attendance->breakTimes as $bt) {
                if ($bt->break_start && $bt->break_end) {
                    $start = \Carbon\Carbon::parse($bt->break_start);
                    $end = \Carbon\Carbon::parse($bt->break_end);
                    $totalBreakMinutes += $start->diffInMinutes($end);
                }
            }

            $totalWorkMinutes = 0;
            if ($attendance->work_start && $attendance->work_end) {
                $start = \Carbon\Carbon::parse($attendance->work_start);
                $end = \Carbon\Carbon::parse($attendance->work_end);
                $totalWorkMinutes = $end->diffInMinutes($start) - $totalBreakMinutes;
            }

            $attendance->total_break_time = sprintf(
                '%02d:%02d:00',
                intdiv($totalBreakMinutes, 60),
                $totalBreakMinutes % 60
            );

            $attendance->total_work_time = sprintf(
                '%02d:%02d:00',
                intdiv($totalWorkMinutes, 60),
                $totalWorkMinutes % 60
            );
        }

        return view('admin.attendance.list', compact(
            'attendances',
            'currentDate',
            'prevDate',
            'nextDate'
        ));
    }

    public function show($id)
    {
        $attendance = Attendance::with([
            'user',
            'breakTimes',
            'corrections.correctionBreaks'
        ])->findOrFail($id);

        $latestCorrection = $attendance->corrections()
            ->orderBy('created_at', 'desc')
            ->first();

        $isPending = $latestCorrection && $latestCorrection->approval_status === 'pending';

        $displayNote = $isPending
            ? $latestCorrection->reason
            : ($attendance->note ?? '');

        if ($isPending && $latestCorrection) {
            $displayBreaks = $latestCorrection->correctionBreaks->map(function ($cb) {
                return [
                    'start' => $cb->break_start,
                    'end' => $cb->break_end,
                ];
            })->toArray();
        } else {
            $displayBreaks = $attendance->breakTimes->map(function ($bt) {
                return [
                    'start' => $bt->break_start,
                    'end' => $bt->break_end,
                ];
            })->toArray();
        }

        return view('admin.attendance.detail', compact(
            'attendance',
            'displayNote',
            'displayBreaks',
            'isPending'
        ));
    }

    public function update(AdminAttendanceUpdateRequest $request, $id)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        $latestCorrection = $attendance->corrections()
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestCorrection && $latestCorrection->approval_status === 'pending') {
            return redirect()
                ->route('admin.attendance.show', $attendance->id)
                ->with('error', '*承認待ちのため修正できません。');
        }

        $attendance->work_start = Carbon::parse("{$request->work_date} {$request->work_start}");
        $attendance->work_end = $request->work_end
            ? Carbon::parse("{$request->work_date} {$request->work_end}")
            : null;
        $attendance->note = $request->note;
        $attendance->save();

        $submittedBreaks = $request->breaks ?? [];

        foreach ($submittedBreaks as $index => $break) {
            if (isset($attendance->breakTimes[$index])) {
                $bt = $attendance->breakTimes[$index];

                $bt->break_start = !empty($break['start'])
                    ? Carbon::parse("{$request->work_date} {$break['start']}")->format('H:i:s')
                    : null;

                $bt->break_end = !empty($break['end'])
                    ? Carbon::parse("{$request->work_date} {$break['end']}")->format('H:i:s')
                    : null;

                $bt->save();
            } else {
                if (!empty($break['start']) && !empty($break['end'])) {
                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => Carbon::parse("{$request->work_date} {$break['start']}")->format('H:i:s'),
                        'break_end' => Carbon::parse("{$request->work_date} {$break['end']}")->format('H:i:s'),
                    ]);
                }
            }
        }

        $attendance->load('breakTimes');
        $totalBreakMinutes = 0;

        foreach ($attendance->breakTimes as $bt) {
            if ($bt->break_start && $bt->break_end) {
                $start = Carbon::parse($bt->break_start);
                $end = Carbon::parse($bt->break_end);
                $totalBreakMinutes += $start->diffInMinutes($end);
            }
        }

        $totalWorkMinutes = 0;

        if ($attendance->work_start && $attendance->work_end) {
            $start = Carbon::parse($attendance->work_start);
            $end = Carbon::parse($attendance->work_end);
            $totalWorkMinutes = $end->diffInMinutes($start) - $totalBreakMinutes;
        }

        $attendance->total_break_time = sprintf('%02d:%02d:00', intdiv($totalBreakMinutes, 60), $totalBreakMinutes % 60);
        $attendance->total_work_time = sprintf('%02d:%02d:00', intdiv($totalWorkMinutes, 60), $totalWorkMinutes % 60);
        $attendance->save();

        return redirect()
            ->route('admin.attendance.show', $attendance->id)
            ->with('success', '修正が完了しました');
    }

    public function staffMonthlyList(Request $request, $id)
    {
        $staff = User::where('id', $id)->firstOrFail();
        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month') . '-01')
            : Carbon::now();

        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

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
