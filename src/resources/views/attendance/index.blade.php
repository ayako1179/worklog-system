@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css')}}">
@endsection

@section('content')
<div class="attendance-container">
  <!-- ステータス表示 -->
  <div class="status-tag">
    <p>{{ $status }}</p>
  </div>

  <!-- 日付 -->
  <p class="date-text">{{ $now->format('Y年n月j日') }}({{ $weekday }})</p>

  <!-- 時刻 -->
  <p class="time-text">{{ $now->format('H:i') }}</p>

  <!-- ボタンエリア -->
  <div class="button-area">
    @if ($status === '勤務外')
    <!-- 出勤ボタン -->
    <form action="{{ route('attendance.start') }}" method="POST">
      @csrf
      <button class="btn black-btn" type="submit">出勤</button>
    </form>

    @elseif ($status === '出勤中')
    <!-- 退勤・休憩入ボタン -->
    <form action="{{ route('attendance.end') }}" method="POST">
      @csrf
      <button class="btn black-btn" type="submit">退勤</button>
    </form>
    <form action="{{ route('break.start') }}" method="POST">
      @csrf
      <button class="btn white-btn" type="submit">休憩入</button>
    </form>

    @elseif ($status === '休憩中')
    <!-- 休憩戻ボタン -->
    <form action="{{ route('break.end') }}" method="POST">
      @csrf
      <button class="btn white-btn" type="submit">休憩戻</button>
    </form>

    @elseif ($status === '退勤済')
    <!-- メッセージ表示 -->
    <p class="thanks-text">お疲れ様でした。</p>
    @endif
  </div>
</div>
@endsection