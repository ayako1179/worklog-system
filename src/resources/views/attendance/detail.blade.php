@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css')}}">
@endsection

@section('content')
<div class="detail-container">
  <h1 class="title">勤怠詳細</h1>

  <form action="{{ route('attendance.detail.submit', $attendance->id) }}" method="POST">
    @csrf

    <table class="detail-table">
      <tr>
        <th>名前</th>
        <td class="value-cell">
          <span class="name-text">{{ auth()->user()->name }}</span>
        </td>
      </tr>

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

      <tr>
        <th>出勤・退勤</th>
        <td class="value-cell value-column">
          @if ($isEditable)
          <div class="error-message">
            <input type="time" name="work_start" class="time-input" value="{{ old('work_start', $attendance->work_start ? \Carbon\Carbon::parse($attendance->work_start)->format('H:i') : '') }}">
            <span class="center">～</span>
            <input type="time" name="work_end" class="time-input" value="{{ old('work_end', $attendance->work_end ? \Carbon\Carbon::parse($attendance->work_end)->format('H:i') : '') }}">
          </div>
          @else
          <div class="display-group">
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

      @if ($isEditable)
      @php
      $newIndex = $breakTimes->count();
      @endphp
      <tr>
        <th>
          {{ $breakTimes->isEmpty() ? '休憩' : '休憩' . ($newIndex + 1) }}
        </th>
        <td class="value-cell value-column">
          <div class="error-message">
            <input type="time" name="breaks[{{ $newIndex }}][start]" class="time-input" value="{{ old("breaks.$newIndex.start") }}">
            <span class=" center">～</span>
            <input type="time" name="breaks[{{ $newIndex }}][end]" class="time-input" value="{{ old("breaks.$newIndex.end") }}">
          </div>

          @php
          $newBreakError = $errors->first("breaks.$newIndex.start") ?: $errors->first("breaks.$newIndex.end");
          @endphp

          @if($newBreakError)
          <span class="error-text">{{ $newBreakError }}</span>
          @endif
        </td>
      </tr>
      @endif

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