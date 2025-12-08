@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/list.css')}}">
@endsection

@section('content')
<div class="list-container">
  <h2 class="title">勤怠一覧</h2>

  <div class="month-nav">
    <a href="{{ route('attendance.list', ['month' => $prevMonth]) }}" class="month-btn">
      <img src="{{ asset('images/arrow-left.png') }}" alt="前月" class="arrow-icon">
      前月
    </a>

    <div class="month-center">
      <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
      <span class="month-display">{{ $currentMonth->format('Y/m') }}</span>
    </div>

    <a href="{{ route('attendance.list', ['month' => $nextMonth]) }}" class="month-btn">
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
        <td>
          {{ $date->format('m/d') }}({{ ['日','月','火','水','木','金','土'][$date->dayOfWeek] }})
        </td>
        <td>
          {{ $attendance && $attendance->work_start ? \Carbon\Carbon::parse($attendance->work_start)->format('H:i') : '' }}
        </td>
        <td>
          {{ $attendance && $attendance->work_end ? \Carbon\Carbon::parse($attendance->work_end)->format('H:i') : '' }}
        </td>
        <td>
          {{ $attendance && $attendance->total_break_time ? \Carbon\Carbon::parse($attendance->total_break_time)->format('H:i') : '' }}
        </td>
        <td>
          {{ $attendance && $attendance->total_work_time ? \Carbon\Carbon::parse($attendance->total_work_time)->format('H:i') : '' }}
        </td>
        <td>
          <a href="{{ route('attendance.detail.show', ['id' => $attendance->id ?? 0, 'date' => $date->toDateString()]) }}" class="detail-btn">
            詳細
          </a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
@endsection