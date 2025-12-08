<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;

class CorrectionController extends Controller
{
    public function show($attendance_correct_request_id)
    {
        $correction = Correction::with([
            'user',
            'attendance.breakTimes',
            'correctionBreaks'
        ])->findOrFail($attendance_correct_request_id);

        return view('admin.corrections.approve', [
            'correction' => $correction,
            'attendance' => $correction->attendance,
            'breakTimes' => $correction->attendance->breakTimes,
            'requestedBreaks' => $correction->correctionBreaks,
        ]);
    }

    public function approve($attendance_correct_request_id)
    {
        $correction = Correction::with(['correctionBreaks'])->findOrFail($attendance_correct_request_id);
        $attendance = Attendance::with('breakTimes')->findOrFail($correction->attendance_id);

        $attendance->work_start = $correction->corrected_start ?? $attendance->work_start;
        $attendance->work_end = $correction->corrected_end ?? $attendance->work_end;
        $attendance->note = $correction->reason;
        $attendance->status = 'approved';
        $attendance->save();

        $attendance->breakTimes()->delete();
        foreach ($correction->correctionBreaks as $cb) {
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => $cb->break_start,
                'break_end' => $cb->break_end,
            ]);
        }

        $attendance->load('breakTimes');
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

        $attendance->total_break_time = sprintf('%02d:%02d:00', intdiv($totalBreakMinutes, 60), $totalBreakMinutes % 60);
        $attendance->total_work_time = sprintf('%02d:%02d:00', intdiv($totalWorkMinutes, 60), $totalWorkMinutes % 60);
        $attendance->save();

        $correction->approval_status = 'approved';
        $correction->save();

        return redirect()
            ->route('correction.index', ['tab' => 'pending']);
    }
}
