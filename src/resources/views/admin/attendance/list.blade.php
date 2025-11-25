@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin-list.css')}}">
@endsection

@section('content')
<div class="list-container">
  <h2 class="title">
    {{ $currentDate->format('Y年n月j日') }}の勤怠
  </h2>

  <!-- 日付ナビ -->
  <div class="day-nav">
    <a href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}" class="day-btn">
      <img src="{{ asset('images/arrow-left.png') }}" alt="前日" class="arrow-icon">
      前日
    </a>

    <div class="day-center">
      <img src="{{ asset('images/calendar.png') }}" alt="カレンダー" class="calendar-icon">
      <span class="day-display">{{ $currentDate->format('Y/m/d') }}</span>
    </div>

    <a href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}" class="day-btn">
      翌日
      <img src="{{ asset('images/arrow-left.png') }}" alt="翌日" class="arrow-icon right-arrow">
    </a>
  </div>

  <!-- 勤怠テーブル -->
  <table class="attendance-table">
    <thead>
      <tr>
        <th>名前</th>
        <th>出勤</th>
        <th>退勤</th>
        <th>休憩</th>
        <th>合計</th>
        <th>詳細</th>
      </tr>
    </thead>

    <tbody>
      @forelse ($attendances as $attendance)
      @php
      $user = $attendance->user;
      @endphp

      <tr>
        <td>{{ $user->name }}</td>

        <td>{{ $attendance->work_start ? \Carbon\Carbon::parse($attendance->work_start)->format('H:i') : '' }}</td>

        <td>{{ $attendance->work_end ? \Carbon\Carbon::parse($attendance->work_end)->format('H:i') : '' }}</td>

        <td>{{ $attendance->total_break_time ? \Carbon\Carbon::parse($attendance->total_break_time)->format('H:i') : '' }}</td>

        <td>{{ $attendance->total_work_time ? \Carbon\Carbon::parse($attendance->total_work_time)->format('H:i') : '' }}</td>

        <td>
          <a href="{{ route('admin.attendance.show', $attendance->id) }}" class="detail-btn">
            詳細
          </a>
        </td>
      </tr>

      @empty
      <!-- <tr>
        <td colspan="6" class="empty">該当の勤怠情報はありません</td>
      </tr> -->
      @endforelse
    </tbody>
  </table>
</div>
@endsection