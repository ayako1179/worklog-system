<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Correction;
use App\Http\Requests\Attendance\AttendanceFixRequest;
use App\Models\CorrectionBreak;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->latest('id')
            ->first();

        $status = '勤務外';
        if ($todayAttendance) {
            switch ($todayAttendance->status) {
                case 'present':
                    $status = '出勤中';
                    break;
                case 'break':
                    $status = '休憩中';
                    break;
                case 'finished':
                    $status = '退勤済';
                    break;
            }
        }

        $now = Carbon::now();
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekdays[$now->dayOfWeek];

        return view('attendance.index', [
            'todayAttendance' => $todayAttendance,
            'status' => $status,
            'now' => $now,
            'weekday' => $weekday,
        ]);
    }

    public function start()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $today = $now->toDateString();

        $exists = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->exists();

        if ($exists) {
            return redirect()->route('home');
        }

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today,
            'work_start' => $now,
            'status' => 'present',
        ]);

        return redirect()->route('home');
    }

    public function end()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->first();

        if (!$attendance) {
            return redirect()->route('home');
        }

        $now = Carbon::now();
        $attendance->work_end = $now;

        $totalBreakSeconds = $attendance->breakTimes()
            ->whereNotNull('break_end')
            ->get()
            ->sum(function ($break) {
                return Carbon::parse($break->break_start)->diffInSeconds($break->break_end);
            });

        $workSeconds = Carbon::parse($attendance->work_start)->diffInSeconds($now) - $totalBreakSeconds;

        $attendance->update([
            'work_end' => $now,
            'total_break_time' => gmdate('H:i:s', $totalBreakSeconds),
            'total_work_time' => gmdate('H:i:s', $workSeconds),
            'status' => 'finished',
        ]);

        return redirect()->route('home');
    }

    public function list(Request $request)
    {
        $user = Auth::user();
        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month') . '-01')
            : Carbon::now();

        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        $attendances = Attendance::with('breakTimes')
            ->where('user_id', $user->id)
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->get()
            ->keyBy(function ($item) {
                return \Carbon\Carbon::parse($item->work_date)->toDateString();
            });

        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $dates = collect();
        for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
            $dates->push($date->copy());
        }

        return view('attendance.list', compact('attendances', 'currentMonth', 'prevMonth', 'nextMonth', 'dates'));
    }

    public function show($id)
    {
        $userId = auth()->id();
        $attendance = Attendance::where('id', $id)
            ->where('user_id', $userId)
            ->with(['breakTimes', 'corrections.correctionBreaks'])
            ->firstOrFail();
        $latestCorrection = $attendance->corrections()
            ->orderBy('created_at', 'desc')
            ->first();
        $isEditable = true;

        if ($latestCorrection && $latestCorrection->approval_status === 'pending') {
            $isEditable = false;
        }

        $breakTimes = $attendance->breakTimes;
        if ($latestCorrection && $latestCorrection->correctionBreaks->count() > 0) {
            $breakTimes = $latestCorrection->correctionBreaks;
        }

        $displayNote = $attendance->note;
        if ($latestCorrection && $latestCorrection->reason) {
            $displayNote = $latestCorrection->reason;
        }

        return view('attendance.detail', [
            'attendance' => $attendance,
            'breakTimes' => $breakTimes,
            'latestCorrection' => $latestCorrection,
            'isEditable' => $isEditable,
            'displayNote' => $displayNote,
        ]);
    }

    public function submit(AttendanceFixRequest $request, $id)
    {
        $userId = auth()->id();
        $attendance = Attendance::with('breakTimes')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();
        $attendance->update([
            'status' => 'pending'
        ]);

        $correctedStart = $request->work_start ?: $attendance->work_start;
        $correctedEnd = $request->work_end ?: $attendance->work_end;

        $correction = Correction::create([
            'user_id' => $userId,
            'attendance_id' => $attendance->id,
            'reason' => $request->note,
            'approval_status' => 'pending',
            'corrected_start' => $correctedStart,
            'corrected_end' => $correctedEnd,
        ]);

        foreach ($attendance->breakTimes as $i => $bt) {

            $start = $request->breaks[$i]['start'] ?? $bt->break_start;
            $end = $request->breaks[$i]['end'] ?? $bt->break_end;

            CorrectionBreak::create([
                'correction_id' => $correction->id,
                'break_start' => $start,
                'break_end' => $end,
            ]);
        }

        $newIndex = $attendance->breakTimes->count();

        if (
            !empty($request->breaks[$newIndex]['start']) ||
            !empty($request->breaks[$newIndex]['end'])
        ) {
            CorrectionBreak::create([
                'correction_id' => $correction->id,
                'break_start' => $request->breaks[$newIndex]['start'],
                'break_end' => $request->breaks[$newIndex]['end'],
            ]);
        }

        return redirect()->route('attendance.detail.show', $attendance->id);
    }
}
