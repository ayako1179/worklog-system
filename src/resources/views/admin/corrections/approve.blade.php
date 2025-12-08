@extends('layouts.admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/approve.css')}}">
@endsection

@section('content')
<div class="approve-container">
  <h2 class="title">勤怠詳細</h2>

  <form action="{{ route('admin.approve', $correction->id) }}" method="POST">
    @csrf

    <table class="approve-table">
      <tr>
        <th>名前</th>
        <td class="value-cell">
          <span class="name-text">{{ $correction->user->name }}</span>
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
          <div class="approve-group">
            <span class="time-text">
              {{ $correction->corrected_start ? \Carbon\Carbon::parse($correction->corrected_start)->format('H:i') : '--:--' }}
            </span>
            <span class="center">～</span>
            <span class="time-text">
              {{ $correction->corrected_end ? \Carbon\Carbon::parse($correction->corrected_end)->format('H:i') : '--:--' }}
            </span>
          </div>
        </td>
      </tr>

      @foreach($requestedBreaks as $index => $rb)
      <tr>
        <th>
          @if($index === 0)
          休憩
          @else
          休憩{{ $index + 1 }}
          @endif
        </th>
        <td class="value-cell value-column">
          <div class="approve-group">
            <span class="time-text">
              {{ \Carbon\Carbon::parse($rb->break_start)->format('H:i') }}
            </span>
            <span class="center">～</span>
            <span class="time-text">
              {{ $rb->break_end ? \Carbon\Carbon::parse($rb->break_end)->format('H:i') : '--:--' }}
            </span>
          </div>
        </td>
      </tr>
      @endforeach

      <tr>
        <th>休憩{{ $requestedBreaks->count() + 1 }}</th>
        <td class="value-cell value-column">
          <div class="approve-group"></div>
        </td>
      </tr>

      <tr>
        <th>備考</th>
        <td class="value-cell">
          <div class="approve-group">
            <span class="approve-note">{{ $correction->reason }}</span>
          </div>
        </td>
      </tr>
    </table>

    <div class="submit-area">
      @if ($correction->approval_status === 'pending')
      <button class="submit-btn">承認</button>
      @else
      <button class="submit-btn is-disabled" disabled>承認済み</button>
      @endif
    </div>

  </form>
</div>
@endsection