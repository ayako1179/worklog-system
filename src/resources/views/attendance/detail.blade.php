@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css')}}">
@endsection

@section('content')
<div class="detail-container">

  <h2 class="title">勤怠詳細</h2>

  <form action="{{ route('attendance.detail.submit', $attendance->id) }}" method="POST">
    @csrf

    <table class="detail-table">

      <!-- 名前 -->
      <tr>
        <th>名前</th>
        <td class="value-cell">
          <span class="name-text">{{ auth()->user()->name }}</span>
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

      <!-- 出勤・退勤 -->
      <tr>
        <th>出勤・退勤</th>
        <td class="value-cell value-column">

          <!-- 入力可能 -->
          @if ($isEditable)
          <div class="error-message">
            <input type="time" name="work_start" class="time-input" value="{{ old('work_start', $attendance->work_start ? \Carbon\Carbon::parse($attendance->work_start)->format('H:i') : '') }}">

            <span class="center">～</span>

            <input type="time" name="work_end" class="time-input" value="{{ old('work_end', $attendance->work_end ? \Carbon\Carbon::parse($attendance->work_end)->format('H:i') : '') }}">
          </div>
          @else
          <div class="display-group">
            <!-- 表示のみ -->
            <span class="disabled-text">
              {{ $attendance->work_start ? \Carbon\Carbon::parse($attendance->work_start)->format('H:i') : '--:--' }}
            </span>
            <span class="center-disabled">～</span>
            <span class="disabled-text">
              {{ $attendance->work_end ? \Carbon\Carbon::parse($attendance->work_end)->format('H:i') : '--:--' }}
            </span>
          </div>
          @endif

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

          @if ($isEditable)
          <div class="error-message">
            <input type="time" name="breaks[{{ $index }}][start]" class="time-input" value="{{ old("breaks.$index.start", \Carbon\Carbon::parse($bt->break_start)->format('H:i')) }}">

            <span class="center">～</span>

            <input type="time" name="breaks[{{ $index }}][end]" class="time-input" value="{{ old("breaks.$index.end", \Carbon\Carbon::parse($bt->break_end)->format('H:i')) }}">
          </div>
          @else
          <div class="display-group">
            <span class="disabled-text">
              {{ \Carbon\Carbon::parse($bt->break_start)->format('H:i') }}
            </span>
            <span class="center-disabled">～</span>
            <span class="disabled-text">
              {{ \Carbon\Carbon::parse($bt->break_end)->format('H:i') }}
            </span>
          </div>
          @endif

          @php
          $breakError = $errors->first("breaks.$index.start");
          if (!$breakError) {
          $breakError = $errors->first("breaks.$index.end");
          }
          @endphp

          @if ($breakError)
          <div class="error-text">{{ $breakError }}</div>
          @endif
        </td>
      </tr>
      @endforeach

      <!-- 追加休憩（常に表示） -->
      @if ($isEditable)
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
      @endif

      <!-- 備考 -->
      <tr>
        <th>備考</th>
        <td class="value-cell">

          @if ($isEditable)
          <div class="error">
            <textarea name="note" class="note-input">{{ old('note', $displayNote) }}</textarea>
            @error('note')
            <span class="error-text">{{ $message }}</span>
            @enderror
            @else
            <div class="display-group">
              <span class="note-disabled">{{ $displayNote }}</span>
            </div>
          </div>
          @endif
        </td>
      </tr>
    </table>

    @php
    $isPending = $latestCorrection && $latestCorrection->approval_status === 'pending';
    @endphp

    <div class="submit-area">
      @if ($isPending)
      <p class="pending-message">*承認待ちのため修正できません。</p>
      @else
      <button class="submit-btn">修正</button>
      @endif
    </div>

  </form>
</div>
@endsection