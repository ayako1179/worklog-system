@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/verify-email.css')}}">
@endsection

@section('content')
<div class="verify-container">
  <div class="verify-box">
    <p class="verify-message">
      登録していただいたメールアドレスに認証メールを送付しました。<br>
      メール認証を完了してください。
    </p>

    @if (session('status') == 'verification-link-sent')
    <div class="success-message">
      認証メールを再送しました。
    </div>
    @endif

    <a href="http://localhost:8025" class="btn--auth">
      認証はこちらから
    </a>

    <form method="POST" action="{{ route('verification.send') }}">
      @csrf
      <button type="submit" class="btn-link">
        認証メールを再送する
      </button>
    </form>
  </div>
</div>
@endsection