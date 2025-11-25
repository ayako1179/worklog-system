@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/correction.css')}}">
@endsection

@section('content')
<div class="correction-container">
  <h2 class="title">申請一覧</h2>

  <!-- タブ -->
  <div class="correction-tabs">
    <a href="{{ route('correction.index', ['tab' => 'pending']) }}" class="tab-item {{ $tab === 'pending' ? 'active' : '' }}">
      承認待ち
    </a>

    <a href="{{ route('correction.index', ['tab' => 'approved']) }}" class="tab-item {{ $tab === 'approved' ? 'active' : '' }}">
      承認済み
    </a>
  </div>

  <!-- タブ下の水平線 -->
  <div class="correction-tabs-line"></div>

  <!-- テーブル -->
  <table class="correction-table">
    <thead>
      <tr>
        <th class="status-header">状態</th>
        <th>名前</th>
        <th>対象日時</th>
        <th>申請理由</th>
        <th>申請日時</th>
        <th>詳細</th>
      </tr>
    </thead>

    <tbody>
      @forelse($corrections as $correction)
      <tr>
        <td class="status-text">{{ $correction->approval_status === 'pending' ? '承認待ち' : '承認済み' }}</td>
        <td>{{ $correction->user->name }}</td>
        <td>{{ $correction->attendance->work_date->format('Y/m/d') }}</td>
        <td>{{ $correction->reason }}</td>
        <td>{{ $correction->created_at->format('Y/m/d') }}</td>

        <td>
          <a href="{{ route('attendance.detail.show', $correction->attendance_id) }}" class="table-detail-link">
            詳細
          </a>
        </td>
      </tr>
      @empty
      <tr>
        <td colspan="6" class="correction-empty">申請はありません</td>
      </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection