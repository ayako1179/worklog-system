@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list.css')}}">
@endsection

@section('content')
<div class="list-container">
  <h2 class="title">{{ $staff->name }}さんの勤怠</h2>

  <div class="month-nav">
    <a href="{{ route('admin.attendance.staff', ['id' => $staff->id, 'month' => $prevMonth]) }}" class="month-btn">
      <img src="{{ asset('images/arrow-left.png') }}" alt="前月" class="arrow-icon">
      前月
    </a>

    <div class="month-center">
      <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
      <span class="month-display">{{ $currentMonth->format('Y/m') }}</span>
    </div>

    <a href="{{ route('admin.attendance.staff', ['id' => $staff->id, 'month' => $nextMonth]) }}" class="month-btn">
      翌月
      <img src="{{ asset('images/arrow-left.png') }}" alt="翌月" class="arrow-icon right-arrow">
    </a>
  </div>

  <table class="attendance-table">
    <thead>
      <tr>
        <th>日付</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($dates as $date)
      @php
      $workDate = $date->toDateString();
      $attendance = $attendances->get($workDate);
      @endphp
      <tr>
        <!-- 日付（常に表示） -->
        <td>
          {{ $date->format('m/d') }}({{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }})
        </td>

        <!-- 出勤 -->
        <td>
          {{ $attendance && $attendance->work_start ? \Carbon\Carbon::parse($attendance->work_start)->format('H:i') : '' }}
        </td>

        <!-- 退勤 -->
        <td>
          {{ $attendance && $attendance->work_end ? \Carbon\Carbon::parse($attendance->work_end)->format('H:i') : '' }}
        </td>

        <!-- 休憩 -->
        <td>
          {{ $attendance && $attendance->total_break_time ? \Carbon\Carbon::parse($attendance->total_break_time)->format('H:i') : '' }}
        </td>

        <!-- 合計 -->
        <td>
          {{ $attendance && $attendance->total_work_time ? \Carbon\Carbon::parse($attendance->total_work_time)->format('H:i') : '' }}
        </td>

        <!-- 詳細リンク（常に表示） -->
        <td>
          @if ($attendance)
          <a href="{{ route('admin.attendance.show', $attendance->id) }}" class="detail-btn">
            詳細
          </a>
          @else
            <span class="detail-btn">詳細</span>
          @endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- CSV出力ボタン -->
  <div class="csv-wrapper">
    <a href="{{ route('admin.csv', $staff->id) }}" class="csv-btn">
      CSV出力
    </a>
  </div>
</div>
@endsection