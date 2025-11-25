<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;
use Illuminate\Http\Request;

class CorrectionController extends Controller
{
    // public function index()
    // {
    //     return view('admin.correction.index');
    // }

    // 承認画面の表示
    public function show($attendance_correct_request_id)
    {
        // 修正申請
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

    // 承認処理
    public function approve($attendance_correct_request_id)
    {
        $correction = Correction::with(['correctionBreaks'])->findOrFail($attendance_correct_request_id);

        $attendance = Attendance::with('breakTimes')->findOrFail($correction->attendance_id);

        // 勤怠（出勤・退勤）の更新
        $attendance->work_start = $correction->corrected_start ?? $attendance->work_start;
        $attendance->work_end = $correction->corrected_end ?? $attendance->work_end;

        $attendance->note = $correction->reason;

        $attendance->status = 'approved';

        $attendance->save();

        // 休憩の更新（既存休憩を全部削除 → 申請された休憩を上書き）
        $attendance->breakTimes()->delete();
        foreach ($correction->correctionBreaks as $cb) {
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => $cb->break_start,
                'break_end' => $cb->break_end,
            ]);
        }

        // 修正申請を approved に
        $correction->approval_status = 'approved';
        $correction->save();

        // 完了 → 承認待ち一覧へ戻す
        return redirect()
            ->route('correction.index', ['tab' => 'pending']);
    }
}
