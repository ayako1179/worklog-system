@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css')}}">
@endsection

@section('content')
<div class="detail-container">

  <h2 class="title">勤怠詳細</h2>

  <!-- 管理者は常に修正可能 -->
  <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="POST">
    @csrf

    <table class="detail-table">

      <!-- 名前 -->
      <tr>
        <th>名前</th>
        <td class="value-cell">
          <span class="name-text">{{ $attendance->user->name }}</span>
        </td>
      </tr>

      <!-- 日付 -->
      @php
      $parsed = \Carbon\Carbon::parse($attendance->work_date);
      @endphp

      <tr>
        <th>日付</th>
        <td class="date-text">
          <span class="date-year">{{ $parsed->format('Y年') }}</span>
          <span class="date-day">{{ $parsed->format('n月j日') }}</span>
        </td>
      </tr>

      <input type="hidden" name="work_date" value="{{ optional($attendance->work_date)->format('Y-m-d') }}">

      <!-- 出勤・退勤 -->
      <tr>
        <th>出勤・退勤</th>
        <td class="value-cell value-column">

          <div class="error-message">
            <input type="time"
              name="work_start"
              class="time-input"
              value="{{ old('work_start', optional($attendance->work_start)->format('H:i')) }}">

            <span class="center">～</span>

            <input type="time"
              name="work_end"
              class="time-input"
              value="{{ old('work_end', optional($attendance->work_end)->format('H:i')) }}">
          </div>

          @error('work_time')
          <span class="error-text">{{ $message }}</span>
          @enderror

        </td>
      </tr>

      <!-- 既存休憩 -->
      @foreach($breakTimes as $index => $bt)
      <tr>
        <th>
          @if($index === 0)
          休憩
          @else
          休憩{{ $index + 1 }}
          @endif
        </th>
        <td class="value-cell value-column">

          <div class="error-message">
            <!-- <input type="time"
              name="breaks[{{ $index }}][start]"
              class="time-input"
              value="{{ optional($bt->break_start)->format('H:i') }}"> -->
            <input type="time"
              name="breaks[{{ $index }}][start]"
              class="time-input"
              value="{{ $bt->break_start ? \Carbon\Carbon::parse($bt->break_start)->format('H:i') : '' }}">

            <span class="center">～</span>

            <!-- <input type="time"
              name="breaks[{{ $index }}][end]"
              class="time-input"
              value="{{ optional($bt->break_end)->format('H:i') }}"> -->
            <input type="time"
              name="breaks[{{ $index }}][end]"
              class="time-input"
              value="{{ $bt->break_end ? \Carbon\Carbon::parse($bt->break_end)->format('H:i') : '' }}">
          </div>

          @php
          $breakError = $errors->first("breaks.$index.start") ?: $errors->first("breaks.$index.end");
          @endphp

          @if ($breakError)
          <div class="error-text">{{ $breakError }}</div>
          @endif
        </td>
      </tr>
      @endforeach

      <!-- 追加休憩（常に表示） -->
      <tr>
        <th>
          {{ $breakTimes->isEmpty() ? '休憩' : '休憩' . ($breakTimes->count() + 1) }}
        </th>
        <!-- <th>休憩{{ $breakTimes->count() + 1 }}</th> -->
        <td class="value-cell value-column">

          <div class="error-message">
            <input type="time" name="breaks[new][start]" class="time-input">
            <span class="center">～</span>
            <input type="time" name="breaks[new][end]" class="time-input">
          </div>

          @error('break_time')
          <span class="error-text">{{ $message }}</span>
          @enderror

        </td>
      </tr>

      <!-- 備考 -->
      <tr>
        <th>備考</th>
        <td class="value-cell">
          <div class="error">
            <textarea name="note" class="note-input">{{ old('note', $attendance->note) }}</textarea>
            @error('note')
            <span class="error-text">{{ $message }}</span>
            @enderror
          </div>
        </td>
      </tr>
    </table>

    <div class="submit-area">
      @if (session('success'))
      <p class="pending-message">{{ session('success') }}</p>
      @else
      <button class="submit-btn">修正</button>
      @endif
    </div>

  </form>
</div>
@endsection