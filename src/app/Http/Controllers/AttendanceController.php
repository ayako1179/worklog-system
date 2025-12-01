<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Correction;
use App\Http\Requests\Attendance\AttendanceFixRequest;
use App\Models\CorrectionBreak;
// use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today()->toDateString();

        // 今日の勤怠データを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->latest('id')
            ->first();

        // ステータス変更
        $status = '勤務外';
        if ($attendance) {
            switch ($attendance->status) {
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

        // 現在時刻・日本語の曜日（UI表示用）
        $now = Carbon::now();
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $weekday = $weekdays[$now->dayOfWeek];

        return view('attendance.index', compact('attendance', 'status', 'now', 'weekday'));
    }

    // 出勤処理
    public function start()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $today = $now->toDateString();
        // $today = Carbon::today()->toDateString();

        // 既に出勤記録がある場合は防止
        $exists = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->exists();

        if ($exists) {
            return redirect()->route('home');
        }

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => $today,
            // 'work_start' => Carbon::now(),
            'work_start' => $now,
            'status' => 'present',
        ]);

        return redirect()->route('home');
    }

    // 退勤処理
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

        // 退勤時刻をセット
        $attendance->work_end = $now;

        // 休憩時間の合計を計算
        $totalBreakSeconds = $attendance->breakTimes()
            ->whereNotNull('break_end')
            ->get()
            ->sum(function ($break) {
                return Carbon::parse($break->break_start)->diffInSeconds($break->break_end);
            });

        // 勤務時間を計算
        $workSeconds = Carbon::parse($attendance->work_start)->diffInSeconds($now) - $totalBreakSeconds;

        // attendancesテーブルを更新
        $attendance->update([
            'work_end' => $now,
            'total_break_time' => gmdate('H:i:s', $totalBreakSeconds),
            'total_work_time' => gmdate('H:i:s', $workSeconds),
            'status' => 'finished',
        ]);

        return redirect()->route('home');
    }

    // 勤怠一覧画面（一般ユーザー）
    public function list(Request $request)
    {
        $user = Auth::user();

        // 表示する月（指定がなければ現在月）
        $currentMonth = $request->query('month')
            ? Carbon::parse($request->query('month') . '-01')
            : Carbon::now();

        // 前月・翌月リンク用
        $prevMonth = $currentMonth->copy()->subMonth()->format('Y-m');
        $nextMonth = $currentMonth->copy()->addMonth()->format('Y-m');

        // 当月の全勤怠データ取得
        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->get()
            ->keyBy(function ($item) {
                return \Carbon\Carbon::parse($item->work_date)->toDateString();
            });

        // 月初と月末を計算
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();
        $dates = collect();
        for ($date = $startOfMonth->copy(); $date <= $endOfMonth; $date->addDay()) {
            $dates->push($date->copy());
        }

        return view('attendance.list', compact('attendances', 'currentMonth', 'prevMonth', 'nextMonth', 'dates'));
    }

    // 勤怠詳細画面（一般ユーザー）
    public function show($id)
    {
        $userId = auth()->id();

        $attendance = Attendance::where('id', $id)
            ->where('user_id', $userId)
            ->with(['breakTimes', 'corrections.correctionBreaks'])
            ->firstOrFail();

        // 最新の修正申請
        $latestCorrection = $attendance->corrections()
            ->orderBy('created_at', 'desc')
            ->first();

        // 編集可能か判定
        $isEditable = true;

        if ($latestCorrection && $latestCorrection->approval_status === 'pending') {
            $isEditable = false;
        }

        // 表示用 breakTimes （修正申請があれば上書き）
        $breakTimes = $attendance->breakTimes;
        if ($latestCorrection && $latestCorrection->correctionBreaks->count() > 0) {
            $breakTimes = $latestCorrection->correctionBreaks;
        }

        // 備考欄
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

    // 勤怠修正の申請処理
    public function submit(AttendanceFixRequest $request, $id)
    {
        $userId = auth()->id();

        // 既存の勤怠レコードのみ修正可能（新規作成は不可）
        $attendance = Attendance::with('breakTimes')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // 勤怠のステータスを承認待ちに変更 
        $attendance->update([
            'status' => 'pending'
        ]);

        // 出勤・退勤（修正なしなら元の値を保持）
        $correctedStart = $request->work_start ?: $attendance->work_start;
        $correctedEnd = $request->work_end ?: $attendance->work_end;

        // 修正申請レコード（corrections）を作成
        $correction = Correction::create([
            'user_id' => $userId,
            'attendance_id' => $attendance->id,
            'reason' => $request->note,
            'approval_status' => 'pending',
            'corrected_start' => $correctedStart,
            'corrected_end' => $correctedEnd,
        ]);

        // 修正申請に紐づく休憩時間を保存
        foreach ($attendance->breakTimes as $i => $bt) {

            $start = $request->breaks[$i]['start'] ?? $bt->break_start;
            $end = $request->breaks[$i]['end'] ?? $bt->break_end;

            CorrectionBreak::create([
                'correction_id' => $correction->id,
                'break_start' => $start,
                'break_end' => $end,
            ]);
        }

        if (!empty($request->breaks['new']['start']) || !empty($request->breaks['new']['end'])) {
            CorrectionBreak::create([
                'correction_id' => $correction->id,
                'break_start' => $request->breaks['new']['start'],
                'break_end' => $request->breaks['new']['end'],
            ]);
        }

        return redirect()->route('attendance.detail.show', $attendance->id);
    }
}
